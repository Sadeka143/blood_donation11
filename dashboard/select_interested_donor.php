<?php
session_start();

include '../config/db.php';
include '../functions/log_activity.php';
include '../functions/eligibility_check.php';
include '../functions/blood_compatibility.php';

// Check blood bank login
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'blood_bank'){
    header("Location: ../auth/login.php");
    exit();
}

// Validate request_id and donor_id
if(!isset($_GET['request_id']) || !is_numeric($_GET['request_id']) || !isset($_GET['donor_id']) || !is_numeric($_GET['donor_id'])){
    header("Location: blood_bank_approved.php");
    exit();
}

$request_id = (int)$_GET['request_id'];
$donor_id = (int)$_GET['donor_id'];
$blood_bank_id = (int)$_SESSION['user_id'];
$blood_bank_name = !empty($_SESSION['institution_name']) ? $_SESSION['institution_name'] : ($_SESSION['name'] ?? 'Central Blood Bank Network');

// Get approved request details
$request_stmt = mysqli_prepare($conn, "
    SELECT 
        id,
        recipient_id,
        blood_group,
        status,
        approved_by,
        assigned_blood_bank_id
    FROM blood_requests
    WHERE id = ?
    LIMIT 1
");
mysqli_stmt_bind_param($request_stmt, "i", $request_id);
mysqli_stmt_execute($request_stmt);
$request_result = mysqli_stmt_get_result($request_stmt);

if(mysqli_num_rows($request_result) !== 1){
    mysqli_stmt_close($request_stmt);
    header("Location: blood_bank_approved.php");
    exit();
}

$request = mysqli_fetch_assoc($request_result);
mysqli_stmt_close($request_stmt);

// Request must still be approved
if($request['status'] !== 'approved'){
    header("Location: blood_bank_approved.php");
    exit();
}

// Check request ownership if assigned/approved blood bank exists
if(
    (!empty($request['approved_by']) && (int)$request['approved_by'] !== $blood_bank_id) ||
    (!empty($request['assigned_blood_bank_id']) && (int)$request['assigned_blood_bank_id'] !== $blood_bank_id)
){
    header("Location: blood_bank_approved.php");
    exit();
}

$recipient_id = (int)$request['recipient_id'];
$needed_blood_group = $request['blood_group'];

// Ensure donor interest exists and is still interested
$interest_stmt = mysqli_prepare($conn, "
    SELECT 
        di.id AS interest_id,
        di.status AS interest_status,
        u.id AS donor_id,
        u.name AS donor_name,
        u.blood_group AS donor_blood_group,
        u.date_of_birth,
        u.weight_kg,
        u.availability,
        u.account_status,
        u.status AS user_status
    FROM donor_interests di
    JOIN users u ON di.donor_id = u.id
    WHERE di.request_id = ? 
      AND di.donor_id = ? 
      AND di.status = 'interested'
      AND u.role = 'donor'
    LIMIT 1
");
mysqli_stmt_bind_param($interest_stmt, "ii", $request_id, $donor_id);
mysqli_stmt_execute($interest_stmt);
$interest_result = mysqli_stmt_get_result($interest_stmt);

if(mysqli_num_rows($interest_result) !== 1){
    mysqli_stmt_close($interest_stmt);
    header("Location: interested_donors.php?id=" . $request_id . "&select=not_found");
    exit();
}

$donor = mysqli_fetch_assoc($interest_result);
mysqli_stmt_close($interest_stmt);

$donor_name = $donor['donor_name'];
$donor_blood_group = $donor['donor_blood_group'];

// Block inactive/deactivated donor account
if(($donor['account_status'] ?? 'active') !== 'active' || ($donor['user_status'] ?? 'active') !== 'active'){
    header("Location: interested_donors.php?id=" . $request_id . "&select=inactive");
    exit();
}

// Re-check donor eligibility before selection
$eligibility = syncDonorAvailabilityStatus($conn, $donor_id);

// Block donor if not eligible
if(empty($eligibility['eligible'])){
    $target = "interested_donors.php?id=" . $request_id . "&select=not_eligible";

    if(!empty($eligibility['next_eligible_date'])){
        $target .= "&next_eligible_date=" . urlencode($eligibility['next_eligible_date']);
    }

    header("Location: " . $target);
    exit();
}

// Block donor if unavailable after eligibility sync
if(($eligibility['availability'] ?? 'not_available') !== 'available'){
    header("Location: interested_donors.php?id=" . $request_id . "&select=unavailable");
    exit();
}

// Re-check blood compatibility on server side
$compatible_recipient_groups = getCompatibleRecipientGroups($donor_blood_group);

if(!in_array($needed_blood_group, $compatible_recipient_groups, true)){
    header("Location: interested_donors.php?id=" . $request_id . "&select=incompatible");
    exit();
}

// Start transaction for safe update
mysqli_begin_transaction($conn);

try{

    // Update request to matched only if it is still approved and belongs to this blood bank
    $update_request_stmt = mysqli_prepare($conn, "
        UPDATE blood_requests
        SET 
            status = 'matched',
            matched_donor_id = ?,
            assigned_blood_bank_id = ?,
            fulfillment_source = 'donor'
        WHERE id = ?
          AND status = 'approved'
          AND (assigned_blood_bank_id IS NULL OR assigned_blood_bank_id = ?)
          AND (approved_by IS NULL OR approved_by = ?)
    ");
    mysqli_stmt_bind_param(
        $update_request_stmt,
        "iiiii",
        $donor_id,
        $blood_bank_id,
        $request_id,
        $blood_bank_id,
        $blood_bank_id
    );
    mysqli_stmt_execute($update_request_stmt);

    if(mysqli_stmt_affected_rows($update_request_stmt) !== 1){
        mysqli_stmt_close($update_request_stmt);
        throw new Exception("Request update failed.");
    }

    mysqli_stmt_close($update_request_stmt);

    // Mark selected donor interest as selected
    $select_stmt = mysqli_prepare($conn, "
        UPDATE donor_interests
        SET status = 'selected'
        WHERE request_id = ? 
          AND donor_id = ? 
          AND status = 'interested'
    ");
    mysqli_stmt_bind_param($select_stmt, "ii", $request_id, $donor_id);
    mysqli_stmt_execute($select_stmt);

    if(mysqli_stmt_affected_rows($select_stmt) !== 1){
        mysqli_stmt_close($select_stmt);
        throw new Exception("Selected donor status update failed.");
    }

    mysqli_stmt_close($select_stmt);

    // Reject other interested donors for the same request
    $reject_stmt = mysqli_prepare($conn, "
        UPDATE donor_interests
        SET status = 'rejected'
        WHERE request_id = ? 
          AND donor_id != ? 
          AND status = 'interested'
    ");
    mysqli_stmt_bind_param($reject_stmt, "ii", $request_id, $donor_id);
    mysqli_stmt_execute($reject_stmt);
    mysqli_stmt_close($reject_stmt);

    // Save activity log
    logActivity(
        $conn,
        $blood_bank_id,
        'blood_bank',
        'select_interested_donor',
        "Blood bank {$blood_bank_name} selected interested donor #{$donor_id} ({$donor_name}) for request #{$request_id}."
    );

    // Notify selected donor
    $message_donor = "You have been selected by the blood bank for blood request #{$request_id}. Please wait for appointment scheduling.";
    $notif_stmt1 = mysqli_prepare($conn, "INSERT INTO notifications (user_id, message) VALUES (?, ?)");
    mysqli_stmt_bind_param($notif_stmt1, "is", $donor_id, $message_donor);
    mysqli_stmt_execute($notif_stmt1);
    mysqli_stmt_close($notif_stmt1);

    // Notify recipient
    $message_recipient = "Your blood request #{$request_id} has been matched with an interested donor by the blood bank.";
    $notif_stmt2 = mysqli_prepare($conn, "INSERT INTO notifications (user_id, message) VALUES (?, ?)");
    mysqli_stmt_bind_param($notif_stmt2, "is", $recipient_id, $message_recipient);
    mysqli_stmt_execute($notif_stmt2);
    mysqli_stmt_close($notif_stmt2);

    // Commit all updates together
    mysqli_commit($conn);

    header("Location: blood_bank_matched.php?select=success");
    exit();

} catch(Exception $e){

    // Rollback if any update fails
    mysqli_rollback($conn);

    header("Location: interested_donors.php?id=" . $request_id . "&select=error");
    exit();
}
?>
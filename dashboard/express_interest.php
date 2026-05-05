<?php
session_start();

include '../config/db.php';
include '../functions/log_activity.php';
include '../functions/eligibility_check.php';
include '../functions/blood_compatibility.php';

// Check donor login
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'donor'){
    header("Location: ../auth/login.php");
    exit();
}

$donor_id = (int)$_SESSION['user_id'];
$donor_name = $_SESSION['name'] ?? 'Donor';

// Validate request id
if(!isset($_GET['id']) || !is_numeric($_GET['id'])){
    header("Location: donor_compatible_requests.php?result=invalid_request");
    exit();
}

$request_id = (int)$_GET['id'];

// Get donor details from database
$donor_stmt = mysqli_prepare($conn, "
    SELECT 
        id,
        name,
        blood_group,
        availability,
        account_status,
        status
    FROM users
    WHERE id = ? AND role = 'donor'
    LIMIT 1
");
mysqli_stmt_bind_param($donor_stmt, "i", $donor_id);
mysqli_stmt_execute($donor_result = $donor_stmt);
$donor_result = mysqli_stmt_get_result($donor_stmt);

if(mysqli_num_rows($donor_result) !== 1){
    mysqli_stmt_close($donor_stmt);
    header("Location: ../auth/login.php");
    exit();
}

$donor = mysqli_fetch_assoc($donor_result);
mysqli_stmt_close($donor_stmt);

// Block inactive donor account
if(($donor['account_status'] ?? 'active') !== 'active' || ($donor['status'] ?? 'active') !== 'active'){
    header("Location: donor_compatible_requests.php?result=inactive");
    exit();
}

// Sync and check donor eligibility
$eligibility = syncDonorAvailabilityStatus($conn, $donor_id);

if(!$eligibility['eligible']){
    $target = "donor_compatible_requests.php?result=unavailable";

    if(!empty($eligibility['next_eligible_date'])){
        $target .= "&next_eligible_date=" . urlencode($eligibility['next_eligible_date']);
    }

    header("Location: " . $target);
    exit();
}

// Check donor availability after sync
if(($eligibility['availability'] ?? 'not_available') !== 'available'){
    header("Location: donor_compatible_requests.php?result=unavailable");
    exit();
}

// Get blood request details
$request_stmt = mysqli_prepare($conn, "
    SELECT 
        r.id,
        r.recipient_id,
        r.blood_group,
        r.status,
        r.assigned_blood_bank_id,
        r.approved_by,
        u.name AS recipient_name
    FROM blood_requests r
    JOIN users u ON r.recipient_id = u.id
    WHERE r.id = ?
    LIMIT 1
");
mysqli_stmt_bind_param($request_stmt, "i", $request_id);
mysqli_stmt_execute($request_stmt);
$request_result = mysqli_stmt_get_result($request_stmt);

if(mysqli_num_rows($request_result) !== 1){
    mysqli_stmt_close($request_stmt);
    header("Location: donor_compatible_requests.php?result=invalid_request");
    exit();
}

$request = mysqli_fetch_assoc($request_result);
mysqli_stmt_close($request_stmt);

// Request must still be approved
if($request['status'] !== 'approved'){
    header("Location: donor_compatible_requests.php?result=request_not_available");
    exit();
}

// Server-side blood compatibility check
$donor_blood_group = $donor['blood_group'] ?? '';
$request_blood_group = $request['blood_group'] ?? '';
$compatible_groups = getCompatibleRecipientGroups($donor_blood_group);

if(!in_array($request_blood_group, $compatible_groups, true)){
    header("Location: donor_compatible_requests.php?result=incompatible");
    exit();
}

// Prevent duplicate interest
$check_stmt = mysqli_prepare($conn, "
    SELECT id
    FROM donor_interests
    WHERE donor_id = ? AND request_id = ?
    LIMIT 1
");
mysqli_stmt_bind_param($check_stmt, "ii", $donor_id, $request_id);
mysqli_stmt_execute($check_stmt);
mysqli_stmt_store_result($check_stmt);

if(mysqli_stmt_num_rows($check_stmt) > 0){
    mysqli_stmt_close($check_stmt);
    header("Location: donor_compatible_requests.php?result=already_interested");
    exit();
}

mysqli_stmt_close($check_stmt);

// Insert donor interest
$stmt = mysqli_prepare($conn, "
    INSERT INTO donor_interests (request_id, donor_id, status)
    VALUES (?, ?, 'interested')
");
mysqli_stmt_bind_param($stmt, "ii", $request_id, $donor_id);

if(mysqli_stmt_execute($stmt)){
    mysqli_stmt_close($stmt);

    // Save activity log
    logActivity(
        $conn,
        $donor_id,
        'donor',
        'express_interest',
        "Donor {$donor_name} expressed interest in request #{$request_id}."
    );

    // Notify assigned blood bank if available
    $blood_bank_user_id = 0;

    if(!empty($request['assigned_blood_bank_id'])){
        $blood_bank_user_id = (int)$request['assigned_blood_bank_id'];
    } elseif(!empty($request['approved_by'])){
        $blood_bank_user_id = (int)$request['approved_by'];
    }

    if($blood_bank_user_id > 0){
        $message_bank = "Donor {$donor_name} expressed interest in blood request #{$request_id}.";

        $notif_stmt = mysqli_prepare($conn, "INSERT INTO notifications (user_id, message) VALUES (?, ?)");
        mysqli_stmt_bind_param($notif_stmt, "is", $blood_bank_user_id, $message_bank);
        mysqli_stmt_execute($notif_stmt);
        mysqli_stmt_close($notif_stmt);
    }

    header("Location: donor_compatible_requests.php?result=interested");
    exit();

} else {
    mysqli_stmt_close($stmt);
    header("Location: donor_compatible_requests.php?result=error");
    exit();
}
?>
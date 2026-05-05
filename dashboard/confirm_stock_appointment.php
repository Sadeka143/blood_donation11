<?php
session_start();
include '../config/db.php';
include '../functions/log_activity.php';
include '../functions/eligibility_check.php';

// Only donor can confirm own stock appointment
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'donor'){
    header("Location: ../auth/login.php");
    exit();
}

// Validate stock request id
if(!isset($_GET['id']) || !is_numeric($_GET['id'])){
    header("Location: donor_appointments.php");
    exit();
}

$stock_request_id = (int)$_GET['id'];
$donor_id = (int)$_SESSION['user_id'];
$donor_name = $_SESSION['name'] ?? 'Donor';

// Get stock donation request and make sure it belongs to logged-in donor
$stmt = mysqli_prepare($conn, "
    SELECT id, donor_id, blood_bank_user_id, branch_id, scheduled_date, status
    FROM stock_donation_requests
    WHERE id = ? AND donor_id = ?
    LIMIT 1
");
mysqli_stmt_bind_param($stmt, "ii", $stock_request_id, $donor_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if(mysqli_num_rows($result) !== 1){
    mysqli_stmt_close($stmt);
    header("Location: donor_appointments.php");
    exit();
}

$request = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

// Only scheduled stock appointments can be confirmed
if($request['status'] !== 'scheduled'){
    header("Location: donor_appointments.php");
    exit();
}

// Server-side eligibility block to prevent direct URL confirmation
$eligibility = syncDonorAvailabilityStatus($conn, $donor_id);
$canConfirm = ($eligibility['eligible'] === true && ($eligibility['availability'] ?? '') === 'available');

if(!$canConfirm){
    header("Location: donor_appointments.php?stock=blocked");
    exit();
}

// Confirm stock donation appointment
$update_stmt = mysqli_prepare($conn, "
    UPDATE stock_donation_requests
    SET status = 'confirmed'
    WHERE id = ? AND donor_id = ? AND status = 'scheduled'
");
mysqli_stmt_bind_param($update_stmt, "ii", $stock_request_id, $donor_id);
mysqli_stmt_execute($update_stmt);
$updated = mysqli_stmt_affected_rows($update_stmt);
mysqli_stmt_close($update_stmt);

if($updated === 1){
    // Save donor activity log
    logActivity(
        $conn,
        $donor_id,
        'donor',
        'confirm_stock_appointment',
        "Donor {$donor_name} confirmed stock donation appointment request #{$stock_request_id}."
    );

    // Notify blood bank
    $message_bank = "A donor confirmed the scheduled stock donation appointment (#{$stock_request_id}).";
    $notif_stmt = mysqli_prepare($conn, "INSERT INTO notifications (user_id, message) VALUES (?, ?)");
    mysqli_stmt_bind_param($notif_stmt, "is", $request['blood_bank_user_id'], $message_bank);
    mysqli_stmt_execute($notif_stmt);
    mysqli_stmt_close($notif_stmt);
}

header("Location: donor_appointments.php?stock=confirmed");
exit();
?>
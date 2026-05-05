<?php
session_start();
include '../config/db.php';
include '../functions/log_activity.php';

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'donor'){
    header("Location: ../auth/login.php");
    exit();
}

if(!isset($_GET['id']) || !is_numeric($_GET['id'])){
    header("Location: donor_appointments.php");
    exit();
}

$stock_request_id = (int) $_GET['id'];
$donor_id = $_SESSION['user_id'];
$donor_name = $_SESSION['name'];

$stmt = mysqli_prepare($conn, "
    SELECT id, donor_id, blood_bank_user_id, branch_id, scheduled_date, status
    FROM stock_donation_requests
    WHERE id = ? AND donor_id = ?
");
mysqli_stmt_bind_param($stmt, "ii", $stock_request_id, $donor_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if(mysqli_num_rows($result) != 1){
    mysqli_stmt_close($stmt);
    header("Location: donor_appointments.php");
    exit();
}

$request = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if(!in_array($request['status'], ['scheduled','confirmed'])){
    header("Location: donor_appointments.php");
    exit();
}

$update_stmt = mysqli_prepare($conn, "
    UPDATE stock_donation_requests
    SET status = 'cancelled'
    WHERE id = ? AND donor_id = ?
");
mysqli_stmt_bind_param($update_stmt, "ii", $stock_request_id, $donor_id);
mysqli_stmt_execute($update_stmt);
mysqli_stmt_close($update_stmt);

logActivity(
    $conn,
    $donor_id,
    'donor',
    'decline_stock_appointment',
    "Donor {$donor_name} declined stock donation appointment request #{$stock_request_id}."
);

$message_bank = "A donor declined the scheduled stock donation appointment (#{$stock_request_id}). Please schedule another slot if needed.";
$notif_stmt = mysqli_prepare($conn, "INSERT INTO notifications (user_id, message) VALUES (?, ?)");
mysqli_stmt_bind_param($notif_stmt, "is", $request['blood_bank_user_id'], $message_bank);
mysqli_stmt_execute($notif_stmt);
mysqli_stmt_close($notif_stmt);

header("Location: donor_appointments.php?stock=declined");
exit();
?>
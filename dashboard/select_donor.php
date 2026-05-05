<?php
session_start();
include '../config/db.php';
include '../functions/log_activity.php';

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'blood_bank'){
    header("Location: ../auth/login.php");
    exit();
}

if(!isset($_GET['request_id']) || !isset($_GET['donor_id'])){
    header("Location: blood_bank_dashboard.php");
    exit();
}

$request_id = (int) $_GET['request_id'];
$donor_id = (int) $_GET['donor_id'];
$blood_bank_id = $_SESSION['user_id'];
$blood_bank_name = !empty($_SESSION['institution_name']) ? $_SESSION['institution_name'] : $_SESSION['name'];

/* Get request */
$request_stmt = mysqli_prepare($conn, "SELECT recipient_id, status FROM blood_requests WHERE id = ?");
mysqli_stmt_bind_param($request_stmt, "i", $request_id);
mysqli_stmt_execute($request_stmt);
$request_result = mysqli_stmt_get_result($request_stmt);

if(mysqli_num_rows($request_result) != 1){
    mysqli_stmt_close($request_stmt);
    header("Location: blood_bank_dashboard.php");
    exit();
}

$request = mysqli_fetch_assoc($request_result);
mysqli_stmt_close($request_stmt);

if($request['status'] != 'approved'){
    header("Location: blood_bank_dashboard.php");
    exit();
}

$recipient_id = $request['recipient_id'];

/* Check donor exists */
$donor_stmt = mysqli_prepare($conn, "SELECT id, name FROM users WHERE id = ? AND role = 'donor'");
mysqli_stmt_bind_param($donor_stmt, "i", $donor_id);
mysqli_stmt_execute($donor_stmt);
$donor_result = mysqli_stmt_get_result($donor_stmt);

if(mysqli_num_rows($donor_result) != 1){
    mysqli_stmt_close($donor_stmt);
    header("Location: match_donor.php?id=".$request_id);
    exit();
}

$donor = mysqli_fetch_assoc($donor_result);
mysqli_stmt_close($donor_stmt);

/* Update request */
$matched_status = 'matched';
$update_stmt = mysqli_prepare($conn, "
    UPDATE blood_requests
    SET status = ?, matched_donor_id = ?, assigned_blood_bank_id = ?
    WHERE id = ?
");
mysqli_stmt_bind_param($update_stmt, "siii", $matched_status, $donor_id, $blood_bank_id, $request_id);
mysqli_stmt_execute($update_stmt);
mysqli_stmt_close($update_stmt);

/* Log activity */
logActivity(
    $conn,
    $blood_bank_id,
    'blood_bank',
    'select_donor',
    "Blood bank {$blood_bank_name} selected donor #{$donor_id} ({$donor['name']}) for request #{$request_id}."
);

/* Notify donor */
$message_donor = "You have been selected by the blood bank for a blood donation request.";
$notif_stmt1 = mysqli_prepare($conn, "INSERT INTO notifications (user_id, message) VALUES (?, ?)");
mysqli_stmt_bind_param($notif_stmt1, "is", $donor_id, $message_donor);
mysqli_stmt_execute($notif_stmt1);
mysqli_stmt_close($notif_stmt1);

/* Notify recipient */
$message_recipient = "Your blood request has been matched with a donor by the blood bank.";
$notif_stmt2 = mysqli_prepare($conn, "INSERT INTO notifications (user_id, message) VALUES (?, ?)");
mysqli_stmt_bind_param($notif_stmt2, "is", $recipient_id, $message_recipient);
mysqli_stmt_execute($notif_stmt2);
mysqli_stmt_close($notif_stmt2);

header("Location: blood_bank_matched.php");
exit();
?>
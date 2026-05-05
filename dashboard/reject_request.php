<?php
session_start();
include '../config/db.php';
include '../functions/log_activity.php';

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'blood_bank'){
    header("Location: ../auth/login.php");
    exit();
}

if($_SERVER['REQUEST_METHOD'] !== 'POST'){
    header("Location: blood_bank_pending.php?result=error&message=" . urlencode("Invalid request method."));
    exit();
}

$blood_bank_id = (int)$_SESSION['user_id'];
$request_id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$rejection_reason = trim($_POST['rejection_reason'] ?? '');

if($request_id <= 0){
    header("Location: blood_bank_pending.php?result=error&message=" . urlencode("Invalid request ID."));
    exit();
}

if(strlen($rejection_reason) < 8){
    header("Location: blood_bank_pending.php?result=error&message=" . urlencode("Please provide a valid rejection reason."));
    exit();
}

$get_stmt = mysqli_prepare($conn, "
    SELECT id, recipient_id, status
    FROM blood_requests
    WHERE id = ?
    LIMIT 1
");
mysqli_stmt_bind_param($get_stmt, "i", $request_id);
mysqli_stmt_execute($get_stmt);
$get_result = mysqli_stmt_get_result($get_stmt);

if(mysqli_num_rows($get_result) !== 1){
    mysqli_stmt_close($get_stmt);
    header("Location: blood_bank_pending.php?result=error&message=" . urlencode("Request not found."));
    exit();
}

$request = mysqli_fetch_assoc($get_result);
mysqli_stmt_close($get_stmt);

if($request['status'] !== 'pending_review'){
    header("Location: blood_bank_pending.php?result=error&message=" . urlencode("This request has already been processed."));
    exit();
}

mysqli_begin_transaction($conn);

try{
    $update_stmt = mysqli_prepare($conn, "
        UPDATE blood_requests
        SET status = 'rejected',
            assigned_blood_bank_id = ?,
            rejection_reason = ?
        WHERE id = ? AND status = 'pending_review'
        LIMIT 1
    ");
    mysqli_stmt_bind_param($update_stmt, "isi", $blood_bank_id, $rejection_reason, $request_id);
    mysqli_stmt_execute($update_stmt);

    if(mysqli_stmt_affected_rows($update_stmt) <= 0){
        mysqli_stmt_close($update_stmt);
        throw new Exception("Failed to reject the request.");
    }
    mysqli_stmt_close($update_stmt);

    $notification_message = "Your blood request #{$request_id} was rejected. Reason: {$rejection_reason}";
    $notif_stmt = mysqli_prepare($conn, "
        INSERT INTO notifications (user_id, message, is_read)
        VALUES (?, ?, 0)
    ");
    mysqli_stmt_bind_param($notif_stmt, "is", $request['recipient_id'], $notification_message);
    mysqli_stmt_execute($notif_stmt);
    mysqli_stmt_close($notif_stmt);

    logActivity(
        $conn,
        $blood_bank_id,
        'blood_bank',
        'reject_request',
        "Blood bank rejected request #{$request_id}. Reason: {$rejection_reason}"
    );

    mysqli_commit($conn);

    header("Location: blood_bank_pending.php?result=rejected&request_id=" . $request_id);
    exit();

}catch(Exception $e){
    mysqli_rollback($conn);
    header("Location: blood_bank_pending.php?result=error&message=" . urlencode($e->getMessage()));
    exit();
}
?>
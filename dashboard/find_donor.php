<?php
session_start();
include '../config/db.php';

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'blood_bank'){
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

if(!isset($_GET['id']) || !is_numeric($_GET['id'])){
    header("Location: blood_bank_pending.php?result=error&message=Invalid request ID");
    exit();
}

$request_id = (int)$_GET['id'];

$request_stmt = mysqli_prepare($conn, "
    SELECT *
    FROM blood_requests
    WHERE id = ? AND status = 'pending_review'
    LIMIT 1
");
mysqli_stmt_bind_param($request_stmt, "i", $request_id);
mysqli_stmt_execute($request_stmt);
$request_result = mysqli_stmt_get_result($request_stmt);

if(mysqli_num_rows($request_result) !== 1){
    mysqli_stmt_close($request_stmt);
    header("Location: blood_bank_pending.php?result=error&message=Request not found or already processed");
    exit();
}

$request = mysqli_fetch_assoc($request_result);
mysqli_stmt_close($request_stmt);

mysqli_begin_transaction($conn);

try{
    $update_stmt = mysqli_prepare($conn, "
        UPDATE blood_requests
        SET status = 'approved',
            assigned_blood_bank_id = ?,
            matched_donor_id = NULL
        WHERE id = ?
        LIMIT 1
    ");
    mysqli_stmt_bind_param($update_stmt, "ii", $user_id, $request_id);
    mysqli_stmt_execute($update_stmt);

    if(mysqli_stmt_affected_rows($update_stmt) < 0){
        mysqli_stmt_close($update_stmt);
        throw new Exception("Request could not be moved to donor matching.");
    }
    mysqli_stmt_close($update_stmt);

    $message = "Your blood request #{$request_id} has been approved for donor matching.";
    $notif_stmt = mysqli_prepare($conn, "INSERT INTO notifications (user_id, message, is_read) VALUES (?, ?, 0)");
    mysqli_stmt_bind_param($notif_stmt, "is", $request['recipient_id'], $message);
    mysqli_stmt_execute($notif_stmt);
    mysqli_stmt_close($notif_stmt);

    $log_desc = "Blood bank moved request #{$request_id} to donor matching.";
    $log_stmt = mysqli_prepare($conn, "
        INSERT INTO activity_logs (user_id, user_role, action_type, description, created_at)
        VALUES (?, 'blood_bank', 'find_donor', ?, NOW())
    ");
    mysqli_stmt_bind_param($log_stmt, "is", $user_id, $log_desc);
    mysqli_stmt_execute($log_stmt);
    mysqli_stmt_close($log_stmt);

    mysqli_commit($conn);

    header("Location: blood_bank_pending.php?result=donor&request_id={$request_id}");
    exit();
}catch(Exception $e){
    mysqli_rollback($conn);
    header("Location: blood_bank_pending.php?result=error&message=" . urlencode($e->getMessage()));
    exit();
}
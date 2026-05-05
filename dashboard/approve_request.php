<?php
session_start();
include '../config/db.php';
include '../functions/log_activity.php';
include '../functions/stock_helper.php';

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'blood_bank'){
    header("Location: ../auth/login.php");
    exit();
}

if(!isset($_GET['id']) || !is_numeric($_GET['id'])){
    header("Location: blood_bank_pending.php?result=error&message=" . urlencode("Invalid request ID."));
    exit();
}

$request_id = (int) $_GET['id'];
$blood_bank_id = (int) $_SESSION['user_id'];

/*
    This file now works ONLY as:
    Fulfill from Stock handler

    Donor matching is handled separately by find_donor.php
*/

/* Get request + recipient */
$get_stmt = mysqli_prepare($conn, "
    SELECT id, recipient_id, blood_group, quantity, city, zipcode, status
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

$data = mysqli_fetch_assoc($get_result);

if($data['status'] !== 'pending_review'){
    mysqli_stmt_close($get_stmt);
    header("Location: blood_bank_pending.php?result=error&message=" . urlencode("This request has already been processed."));
    exit();
}

$recipient_id = (int)$data['recipient_id'];
$blood_group = $data['blood_group'];
$quantity = (int)$data['quantity'];
$city = $data['city'] ?? '';
$zipcode = $data['zipcode'] ?? '';

mysqli_begin_transaction($conn);

try{
    $stock_branch = findNearestBranchWithStock(
        $conn,
        $blood_bank_id,
        $blood_group,
        $quantity,
        $city,
        $zipcode
    );

    if(!$stock_branch){
        throw new Exception("No suitable stock is available for this request.");
    }

    $fulfilled_branch_id = (int)$stock_branch['id'];
    $branch_name = $stock_branch['branch_name'];

    $update_stmt = mysqli_prepare($conn, "
        UPDATE blood_requests
        SET status = 'completed',
            approved_by = ?,
            assigned_blood_bank_id = ?,
            approved_at = NOW(),
            fulfilled_branch_id = ?,
            fulfillment_source = 'stock',
            stock_units_used = ?
        WHERE id = ? AND status = 'pending_review'
    ");
    mysqli_stmt_bind_param(
        $update_stmt,
        "iiiii",
        $blood_bank_id,
        $blood_bank_id,
        $fulfilled_branch_id,
        $quantity,
        $request_id
    );
    mysqli_stmt_execute($update_stmt);

    if(mysqli_stmt_affected_rows($update_stmt) <= 0){
        mysqli_stmt_close($update_stmt);
        throw new Exception("Failed to update the request.");
    }
    mysqli_stmt_close($update_stmt);

    if(!reduceBranchStock($conn, $fulfilled_branch_id, $blood_group, $quantity)){
        throw new Exception("Failed to reduce stock units.");
    }

    logActivity(
        $conn,
        $blood_bank_id,
        'blood_bank',
        'approve_request_stock_fulfilled',
        "Central Blood Bank Network reviewed request #{$request_id} and fulfilled it from stock."
    );

    $message = "Your blood request has been reviewed and fulfilled from available stock at {$branch_name}.";
    $notif_stmt = mysqli_prepare($conn, "INSERT INTO notifications (user_id, message) VALUES (?, ?)");
    mysqli_stmt_bind_param($notif_stmt, "is", $recipient_id, $message);
    mysqli_stmt_execute($notif_stmt);
    mysqli_stmt_close($notif_stmt);

    mysqli_commit($conn);
    mysqli_stmt_close($get_stmt);

    header("Location: blood_bank_pending.php?result=stock&request_id=" . $request_id . "&branch=" . urlencode($branch_name));
    exit();

} catch(Exception $e){
    mysqli_rollback($conn);
    mysqli_stmt_close($get_stmt);

    header("Location: blood_bank_pending.php?result=error&message=" . urlencode($e->getMessage()));
    exit();
}
?>
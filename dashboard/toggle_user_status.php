<?php
// ======================================
// TOGGLE USER STATUS
// Purpose:
// - Admin can activate/deactivate users
// - Save admin action in activity_logs
// ======================================

session_start();
include '../config/db.php';

if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin'){
    header("Location: ../auth/login.php");
    exit();
}

if(!isset($_GET['id']) || !is_numeric($_GET['id'])){
    header("Location: admin_users.php?result=error");
    exit();
}

$current_admin_id = (int) $_SESSION['user_id'];
$target_user_id = (int) $_GET['id'];

if($current_admin_id === $target_user_id){
    header("Location: admin_users.php?result=self_blocked");
    exit();
}

// Get target user
$stmt = mysqli_prepare($conn, "SELECT id, name, email, role, status FROM users WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $target_user_id);
mysqli_stmt_execute($result = $stmt);
$user_result = mysqli_stmt_get_result($stmt);

if(mysqli_num_rows($user_result) !== 1){
    mysqli_stmt_close($stmt);
    header("Location: admin_users.php?result=not_found");
    exit();
}

$user = mysqli_fetch_assoc($user_result);
mysqli_stmt_close($stmt);

$new_status = ($user['status'] === 'inactive') ? 'active' : 'inactive';
$admin_name = $_SESSION['name'] ?? 'Admin';

mysqli_begin_transaction($conn);

try{
    // Update both status and account_status for compatibility with your system
    $stmt = mysqli_prepare($conn, "
        UPDATE users 
        SET status = ?, account_status = ?
        WHERE id = ?
    ");
    mysqli_stmt_bind_param($stmt, "ssi", $new_status, $new_status, $target_user_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    // Save activity log
    $action_type = "toggle_user_status";
    $admin_role = "admin";
    $description = "Admin {$admin_name} changed status of {$user['role']} user {$user['name']} ({$user['email']}, ID {$target_user_id}) to {$new_status}.";

    $stmt = mysqli_prepare($conn, "
        INSERT INTO activity_logs (user_id, user_role, action_type, description)
        VALUES (?, ?, ?, ?)
    ");
    mysqli_stmt_bind_param($stmt, "isss", $current_admin_id, $admin_role, $action_type, $description);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    mysqli_commit($conn);
    header("Location: admin_users.php?result=status_updated");
    exit();

} catch(Exception $e){
    mysqli_rollback($conn);
    header("Location: admin_users.php?result=error");
    exit();
}
?>
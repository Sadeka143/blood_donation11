<?php
// ======================================
// DELETE USER
// Purpose:
// - Allow admin to delete a user even if linked records exist
// - Require valid delete reason
// - Save delete action in activity_logs
// - Prevent admin from deleting own account
// ======================================

session_start();
include '../config/db.php';

// Only admin can delete users
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// Only POST request allowed
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: admin_users.php?result=error");
    exit();
}

$current_admin_id = (int) $_SESSION['user_id'];
$target_user_id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
$delete_reason = isset($_POST['delete_reason']) ? trim($_POST['delete_reason']) : "";

if ($target_user_id <= 0) {
    header("Location: admin_users.php?result=error");
    exit();
}

if ($current_admin_id === $target_user_id) {
    header("Location: admin_users.php?result=self_blocked");
    exit();
}

if ($delete_reason === "" || strlen($delete_reason) < 5) {
    header("Location: admin_users.php?result=reason_required");
    exit();
}

// Get target user details before deleting
$user_stmt = mysqli_prepare($conn, "SELECT id, name, email, role, institution_name FROM users WHERE id = ?");
mysqli_stmt_bind_param($user_stmt, "i", $target_user_id);
mysqli_stmt_execute($user_stmt);
$user_result = mysqli_stmt_get_result($user_stmt);

if (mysqli_num_rows($user_result) !== 1) {
    mysqli_stmt_close($user_stmt);
    header("Location: admin_users.php?result=not_found");
    exit();
}

$user = mysqli_fetch_assoc($user_result);
mysqli_stmt_close($user_stmt);

$target_name = $user['name'] ?: $user['institution_name'];
$target_email = $user['email'];
$target_role = $user['role'];

// Get admin name for activity log
$admin_name = $_SESSION['name'] ?? 'Admin';

mysqli_begin_transaction($conn);

try {
    // Delete password reset tokens by email
    $stmt = mysqli_prepare($conn, "DELETE FROM password_resets WHERE email = ?");
    mysqli_stmt_bind_param($stmt, "s", $target_email);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    // Remove appointment reschedule records linked to this user
    $stmt = mysqli_prepare($conn, "
        DELETE arr FROM appointment_reschedule_requests arr
        LEFT JOIN appointments a ON arr.appointment_id = a.id
        WHERE arr.donor_id = ?
           OR arr.recipient_id = ?
           OR arr.blood_bank_id = ?
           OR a.donor_id = ?
           OR a.recipient_id = ?
           OR a.blood_bank_id = ?
    ");
    mysqli_stmt_bind_param($stmt, "iiiiii", $target_user_id, $target_user_id, $target_user_id, $target_user_id, $target_user_id, $target_user_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    // Delete appointments linked to this user
    $stmt = mysqli_prepare($conn, "
        DELETE FROM appointments
        WHERE donor_id = ? OR recipient_id = ? OR blood_bank_id = ?
    ");
    mysqli_stmt_bind_param($stmt, "iii", $target_user_id, $target_user_id, $target_user_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    // Delete donor interest records
    $stmt = mysqli_prepare($conn, "DELETE FROM donor_interests WHERE donor_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $target_user_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    // Delete stock donation requests linked to donor or blood bank user
    $stmt = mysqli_prepare($conn, "
        DELETE FROM stock_donation_requests
        WHERE donor_id = ? OR blood_bank_user_id = ?
    ");
    mysqli_stmt_bind_param($stmt, "ii", $target_user_id, $target_user_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    // Delete donation records linked to donor or blood bank user
    $stmt = mysqli_prepare($conn, "
        DELETE FROM donations
        WHERE donor_id = ? OR blood_bank_id = ?
    ");
    mysqli_stmt_bind_param($stmt, "ii", $target_user_id, $target_user_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    // Delete recipient blood requests, but keep admin/blood-bank references safe
    $stmt = mysqli_prepare($conn, "DELETE FROM blood_requests WHERE recipient_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $target_user_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    // Remove references where user was approver, matched donor, or assigned blood bank
    $stmt = mysqli_prepare($conn, "
        UPDATE blood_requests
        SET approved_by = CASE WHEN approved_by = ? THEN NULL ELSE approved_by END,
            matched_donor_id = CASE WHEN matched_donor_id = ? THEN NULL ELSE matched_donor_id END,
            assigned_blood_bank_id = CASE WHEN assigned_blood_bank_id = ? THEN NULL ELSE assigned_blood_bank_id END
    ");
    mysqli_stmt_bind_param($stmt, "iii", $target_user_id, $target_user_id, $target_user_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    // Delete notifications for this user
    $stmt = mysqli_prepare($conn, "DELETE FROM notifications WHERE user_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $target_user_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    // Keep old logs but detach deleted user ID
    $stmt = mysqli_prepare($conn, "UPDATE activity_logs SET user_id = NULL WHERE user_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $target_user_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    // Insert admin delete activity log BEFORE deleting user
    $description = "Admin {$admin_name} deleted {$target_role} user {$target_name} ({$target_email}, ID {$target_user_id}). Reason: {$delete_reason}";
    $action_type = "delete_user";
    $admin_role = "admin";

    $stmt = mysqli_prepare($conn, "
        INSERT INTO activity_logs (user_id, user_role, action_type, description)
        VALUES (?, ?, ?, ?)
    ");
    mysqli_stmt_bind_param($stmt, "isss", $current_admin_id, $admin_role, $action_type, $description);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    // Finally delete user
    $stmt = mysqli_prepare($conn, "DELETE FROM users WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $target_user_id);
    mysqli_stmt_execute($stmt);

    if (mysqli_stmt_affected_rows($stmt) !== 1) {
        throw new Exception("User delete failed.");
    }

    mysqli_stmt_close($stmt);

    mysqli_commit($conn);
    header("Location: admin_users.php?result=deleted");
    exit();

} catch (Exception $e) {
    mysqli_rollback($conn);
    header("Location: admin_users.php?result=error");
    exit();
}
?>
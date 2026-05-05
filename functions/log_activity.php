<?php
function logActivity($conn, $userId, $userRole, $actionType, $description) {
    $stmt = mysqli_prepare($conn, "INSERT INTO activity_logs (user_id, user_role, action_type, description) VALUES (?, ?, ?, ?)");

    if(!$stmt){
        return false;
    }

    mysqli_stmt_bind_param($stmt, "isss", $userId, $userRole, $actionType, $description);
    $success = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    return $success;
}
?>
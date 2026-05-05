<?php
// ======================================
// RESET PASSWORD PAGE
// Purpose:
// - Verify reset token
// - Let user enter new password
// - Update user's password securely
// - Delete used token
// ======================================

include("../config/db.php");

$error = "";
$success = "";
$token = trim($_GET['token'] ?? $_POST['token'] ?? '');

// ======================================
// Validate token existence early
// ======================================
if($token == ""){
    $error = "Invalid or missing reset token.";
}

// ======================================
// Find reset request by token
// ======================================
$reset_row = null;

if($error == ""){
    $check_stmt = mysqli_prepare($conn, "
        SELECT *
        FROM password_resets
        WHERE token = ?
        LIMIT 1
    ");
    mysqli_stmt_bind_param($check_stmt, "s", $token);
    mysqli_stmt_execute($check_stmt);
    $check_result = mysqli_stmt_get_result($check_stmt);

    if(mysqli_num_rows($check_result) !== 1){
        $error = "Invalid reset token.";
    } else {
        $reset_row = mysqli_fetch_assoc($check_result);

        if(strtotime($reset_row['expires_at']) < time()){
            $error = "This reset link has expired.";
        }
    }

    mysqli_stmt_close($check_stmt);
}

// ======================================
// Handle reset submission
// ======================================
if($_SERVER["REQUEST_METHOD"] == "POST" && $error == ""){
    $new_password = $_POST["new_password"] ?? "";
    $confirm_password = $_POST["confirm_password"] ?? "";

    if($new_password == "" || $confirm_password == ""){
        $error = "Please fill in both password fields.";
    } elseif(strlen($new_password) < 6){
        $error = "Password must be at least 6 characters long.";
    } elseif($new_password !== $confirm_password){
        $error = "Passwords do not match.";
    } else {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

        // Update password in users table
        $update_stmt = mysqli_prepare($conn, "
            UPDATE users
            SET password = ?
            WHERE email = ?
            LIMIT 1
        ");
        mysqli_stmt_bind_param($update_stmt, "ss", $hashed_password, $reset_row['email']);

        if(mysqli_stmt_execute($update_stmt)){
            // Delete token after successful use
            $delete_stmt = mysqli_prepare($conn, "DELETE FROM password_resets WHERE email = ?");
            mysqli_stmt_bind_param($delete_stmt, "s", $reset_row['email']);
            mysqli_stmt_execute($delete_stmt);
            mysqli_stmt_close($delete_stmt);

            $success = "Password reset successful! You can now login with your new password.";
        } else {
            $error = "Failed to update password.";
        }

        mysqli_stmt_close($update_stmt);
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Reset Password - Blood Donation Management System</title>
    <link rel="stylesheet" href="../dashboard/assets/style.css">
</head>

<body class="register-page-body">

<div class="register-page-wrap">

    <div class="register-card">

        <!--
            Brand section
        -->
        <div class="auth-brand-block">
            <div class="logo auth-logo">🩸</div>
            <h2 class="auth-system-title">Blood Donation Management System</h2>
            <p class="auth-system-subtitle">Donate Blood, Save Lives</p>
        </div>

        <!--
            Heading box
        -->
        <div class="login-header-box">
            <h2>Reset Password</h2>
            <p>Set a new password for your account</p>
        </div>

        <!--
            Error / success messages
        -->
        <?php if($error != ""){ ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php } ?>

        <?php if($success != ""){ ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php } ?>

        <!--
            Reset form only if not successful
        -->
        <?php if($success == "" && $error != "Invalid or missing reset token." && $error != "Invalid reset token." && $error != "This reset link has expired."){ ?>
            <form method="POST">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

                <label>New Password</label>
                <input
                    type="password"
                    name="new_password"
                    class="input"
                    placeholder="Enter new password"
                    required
                >

                <label>Confirm New Password</label>
                <input
                    type="password"
                    name="confirm_password"
                    class="input"
                    placeholder="Confirm new password"
                    required
                >

                <button type="submit" class="btn btn-primary btn-full">
                    Reset Password
                </button>
            </form>
        <?php } ?>

        <p class="auth-text-row">
            <a href="login.php" class="link">← Back to Login</a>
        </p>

    </div>
</div>

</body>
</html>
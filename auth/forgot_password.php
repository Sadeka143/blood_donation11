<?php
// ======================================
// FORGOT PASSWORD PAGE
// Purpose:
// - Accept user's email
// - Generate secure reset token
// - Save token with expiry
// - Show demo reset link on screen
// ======================================

include("../config/db.php");

$error = "";
$success = "";
$reset_link = "";

// ======================================
// Handle forgot password request
// ======================================
if($_SERVER["REQUEST_METHOD"] == "POST"){
    $email = trim($_POST["email"] ?? "");

    if($email == ""){
        $error = "Please enter your registered email address.";
    } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)){
        $error = "Please enter a valid email address.";
    } else {
        // Check if email exists
        $check_stmt = mysqli_prepare($conn, "SELECT id, name FROM users WHERE email = ? LIMIT 1");
        mysqli_stmt_bind_param($check_stmt, "s", $email);
        mysqli_stmt_execute($check_stmt);
        $check_result = mysqli_stmt_get_result($check_stmt);

        if(mysqli_num_rows($check_result) !== 1){
            $error = "No account found with this email address.";
        } else {
            // Delete old reset requests for this email
            $delete_stmt = mysqli_prepare($conn, "DELETE FROM password_resets WHERE email = ?");
            mysqli_stmt_bind_param($delete_stmt, "s", $email);
            mysqli_stmt_execute($delete_stmt);
            mysqli_stmt_close($delete_stmt);

            // Generate secure token
            $token = bin2hex(random_bytes(32));
            $expires_at = date('Y-m-d H:i:s', strtotime('+30 minutes'));

            // Save token
            $insert_stmt = mysqli_prepare($conn, "
                INSERT INTO password_resets (email, token, expires_at)
                VALUES (?, ?, ?)
            ");
            mysqli_stmt_bind_param($insert_stmt, "sss", $email, $token, $expires_at);

            if(mysqli_stmt_execute($insert_stmt)){
                $success = "Password reset link generated successfully.";

                // Demo-friendly reset link
                $reset_link = "http://localhost/blood_donation/auth/reset_password.php?token=" . urlencode($token);
            } else {
                $error = "Failed to generate reset link.";
            }

            mysqli_stmt_close($insert_stmt);
        }

        mysqli_stmt_close($check_stmt);
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Forgot Password - Blood Donation Management System</title>
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
            <h2>Forgot Password</h2>
            <p>Enter your registered email to reset your password</p>
        </div>

        <!--
            Alerts
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
            Forgot password form
        -->
        <form method="POST">
            <label>Email</label>
            <input
                type="email"
                name="email"
                class="input"
                placeholder="Enter your registered email"
                required
                value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
            >

            <button type="submit" class="btn btn-primary btn-full">
                Generate Reset Link
            </button>
        </form>

        <!--
            Demo reset link display
            For localhost / academic demo use
        -->
        <?php if($reset_link != ""){ ?>
            <div class="reset-link-box">
                <strong>Demo Reset Link:</strong><br>
                <a href="<?php echo htmlspecialchars($reset_link); ?>" class="link break-link">
                    <?php echo htmlspecialchars($reset_link); ?>
                </a>
                <p class="small-muted">This link will expire in 30 minutes.</p>
            </div>
        <?php } ?>

        <p class="auth-text-row">
            <a href="login.php" class="link">← Back to Login</a>
        </p>

    </div>
</div>

</body>
</html>
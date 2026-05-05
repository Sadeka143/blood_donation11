<?php
// ==============================
// LOGIN PAGE CONTROLLER
// Purpose:
// - Authenticate user
// - Check email, password, role
// - Block inactive/deactivated accounts
// - Redirect to correct dashboard
// ==============================

session_start();
include("../config/db.php");

$error = "";

// Preserve selected role from URL or POST
$selected_role = $_GET['role'] ?? ($_POST['role'] ?? '');

// Handle login form submission
if($_SERVER["REQUEST_METHOD"] == "POST"){

    // Collect and clean form data
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";
    $role = trim($_POST["role"] ?? "");

    // Allowed roles only
    $allowed_roles = ['donor', 'recipient', 'blood_bank', 'admin'];

    if($email === "" || $password === "" || $role === ""){
        $error = "Please enter email, password and role.";
    } elseif(!in_array($role, $allowed_roles)){
        $error = "Invalid role selected.";
    } else {

        // Find user by email and selected role
        $stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE email = ? AND role = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "ss", $email, $role);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if(mysqli_num_rows($result) == 1){
            $user = mysqli_fetch_assoc($result);

            // Verify hashed password
            if(password_verify($password, $user['password'])){

                // Check both account_status and status columns
                $account_status = $user['account_status'] ?? 'active';
                $user_status = $user['status'] ?? 'active';

                // Block inactive/deactivated users before creating session
                if($account_status !== 'active' || $user_status !== 'active'){
                    $error = "Your account is inactive. Please contact the administrator.";
                } else {

                    // Create session only for active users
                    $_SESSION["user_id"] = $user["id"];
                    $_SESSION["name"] = $user["name"];
                    $_SESSION["role"] = $user["role"];

                    // Redirect based on role
                    if($role == "donor"){
                        header("Location: ../dashboard/donor_dashboard.php");
                        exit();
                    } elseif($role == "recipient"){
                        header("Location: ../dashboard/recipient_dashboard.php");
                        exit();
                    } elseif($role == "blood_bank"){
                        header("Location: ../dashboard/blood_bank_dashboard.php");
                        exit();
                    } elseif($role == "admin"){
                        header("Location: ../dashboard/admin_dashboard.php");
                        exit();
                    }
                }

            } else {
                $error = "Incorrect password.";
            }

        } else {
            $error = "No account found with this email and role.";
        }

        mysqli_stmt_close($stmt);
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login - Blood Donation Management System</title>

    <!-- Main stylesheet -->
    <link rel="stylesheet" href="../dashboard/assets/style.css">
</head>

<body class="login-page-body">

    <div class="login-page-wrap">

        <div class="login-card">

            <div class="auth-brand-block">
                <div class="logo auth-logo">🩸</div>
                <h2 class="auth-system-title">Blood Donation Management System</h2>
                <p class="auth-system-subtitle">Donate Blood, Save Lives</p>
            </div>

            <div class="login-header-box">
                <h2>Login</h2>
                <p>Access your account</p>
            </div>

            <?php if($error != ""){ ?>
                <div class="alert alert-error">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php } ?>

            <form method="POST">

                <label>Email</label>
                <input
                    type="email"
                    name="email"
                    class="input"
                    placeholder="Enter your email"
                    required
                    value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                >

                <label>Password</label>
                <input
                    type="password"
                    name="password"
                    class="input"
                    placeholder="Enter your password"
                    required
                >

                <label>Role</label>
                <select name="role" class="select" required>
                    <option value="">Select Role</option>
                    <option value="donor" <?php if($selected_role == 'donor') echo 'selected'; ?>>Donor</option>
                    <option value="recipient" <?php if($selected_role == 'recipient') echo 'selected'; ?>>Recipient</option>
                    <option value="blood_bank" <?php if($selected_role == 'blood_bank') echo 'selected'; ?>>Blood Bank</option>
                    <option value="admin" <?php if($selected_role == 'admin') echo 'selected'; ?>>Admin</option>
                </select>

                <button type="submit" class="btn btn-primary btn-full">
                    Login
                </button>
            </form>

            <p class="auth-text-row auth-forgot-row">
                <a href="forgot_password.php" class="link">Forgot Password?</a>
            </p>

            <p class="auth-text-row">
                Don’t have an account?
                <a href="register.php" class="link">Register here</a>
            </p>

            <p class="auth-text-row">
                <a href="index.php" class="link">← Back to role selection</a>
            </p>

        </div>
    </div>

</body>
</html>
<?php
# ==================================
# Recipient profile controller
# ==================================
session_start();
include '../config/db.php';
include '../functions/network_helper.php';

# --------------------------------
# Secure access: only recipient role
# --------------------------------
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'recipient'){
    header("Location: ../auth/login.php");
    exit();
}

$recipient_id = $_SESSION['user_id'];

# ------------------------------------
# Notification badge count for topbar
# ------------------------------------
$notif_stmt = mysqli_prepare($conn, "SELECT COUNT(*) AS total FROM notifications WHERE user_id = ? AND is_read = 0");
mysqli_stmt_bind_param($notif_stmt, "i", $recipient_id);
mysqli_stmt_execute($notif_stmt);
$notif_result = mysqli_stmt_get_result($notif_stmt);
$notif_data = mysqli_fetch_assoc($notif_result);
$notif_count = $notif_data['total'];
mysqli_stmt_close($notif_stmt);

# ----------------------------------------
# Update profile form processing
# ----------------------------------------
if(isset($_POST['update_profile'])){
    $name = trim($_POST['name']);
    $contact_number = trim($_POST['contact_number']);
    $address_line = trim($_POST['address_line']);
    $city = trim($_POST['city']);
    $zipcode = trim($_POST['zipcode']);

    $location_parts = array_filter([$address_line, $city, $zipcode], function($v){
        return $v !== '';
    });
    $location = implode(', ', $location_parts);

    if($name == ""){
        $error = "Name is required.";
    } else {
        $update_stmt = mysqli_prepare($conn, "
            UPDATE users
            SET name = ?, contact_number = ?, location = ?, address_line = ?, city = ?, zipcode = ?
            WHERE id = ?
        ");
        mysqli_stmt_bind_param($update_stmt, "ssssssi", $name, $contact_number, $location, $address_line, $city, $zipcode, $recipient_id);

        if(mysqli_stmt_execute($update_stmt)){
            $_SESSION['name'] = $name;
            $success = "Profile updated successfully.";
        } else {
            $error = "Failed to update profile: " . mysqli_error($conn);
        }

        mysqli_stmt_close($update_stmt);
    }
}

# ----------------------------------------
# Fetch current recipient profile details
# ----------------------------------------
$profile_stmt = mysqli_prepare($conn, "
    SELECT name, email, contact_number, blood_group, location, address_line, city, zipcode
    FROM users
    WHERE id = ?
");
mysqli_stmt_bind_param($profile_stmt, "i", $recipient_id);
mysqli_stmt_execute($profile_stmt);
$profile_result = mysqli_stmt_get_result($profile_stmt);
$profile = mysqli_fetch_assoc($profile_result);
mysqli_stmt_close($profile_stmt);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Recipient Profile</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>

<!-- =========================
     Full-width top navigation
========================= -->
<div class="topbar topbar-full">
    <div class="container">
        <div class="topbar-header">
            <div class="topbar-left">
                <div class="logo">🩸</div>
                <div class="brand-text">
                    <h2><?php echo htmlspecialchars(getSystemName()); ?></h2>
                    <p><?php echo htmlspecialchars(getSystemTagline()); ?></p>
                </div>
            </div>

            <div class="topbar-right">
                <a href="notifications.php" class="notify" title="Notifications">
                    🔔
                    <span class="notify-badge"><?php echo $notif_count; ?></span>
                </a>
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?> (Recipient)</span>
                <a class="btn btn-logout" href="../auth/logout.php">Logout</a>
            </div>
        </div>

        <div class="topbar-menu">
            <a href="recipient_dashboard.php">Dashboard</a>
            <a href="create_request.php">Create Request</a>
            <a href="recipient_requests.php">My Requests</a>
            <a href="recipient_appointments.php">Appointments</a>
            <a href="recipient_profile.php" class="active">Profile</a>
            <a href="notifications.php">Notifications</a>
        </div>
    </div>
</div>

<div class="container">

    <!-- =========================
         Page hero section
    ========================= -->
    <div class="hero">
        <h1>Recipient Profile</h1>
        <p>Update your personal and detailed address information for request management.</p>
    </div>

    <!-- =========================
         Profile form card
    ========================= -->
    <div class="card">
        <h3>Profile Information</h3>

        <?php
        if(isset($success)) echo "<div class='alert alert-success'>" . htmlspecialchars($success) . "</div>";
        if(isset($error)) echo "<div class='alert alert-error'>" . htmlspecialchars($error) . "</div>";
        ?>

        <form method="POST" class="form">
            <label>Full Name</label>
            <input class="input" type="text" name="name" value="<?php echo htmlspecialchars($profile['name'] ?? ''); ?>" required>

            <label>Email</label>
            <input class="input" type="email" value="<?php echo htmlspecialchars($profile['email'] ?? ''); ?>" disabled>

            <label>Contact Number</label>
            <input class="input" type="text" name="contact_number" value="<?php echo htmlspecialchars($profile['contact_number'] ?? ''); ?>">

            <label>Blood Group</label>
            <input class="input" type="text" value="<?php echo htmlspecialchars($profile['blood_group'] ?? ''); ?>" disabled>

            <label>Address</label>
            <input class="input" type="text" name="address_line" value="<?php echo htmlspecialchars($profile['address_line'] ?? ''); ?>">

            <label>City</label>
            <input class="input" type="text" name="city" value="<?php echo htmlspecialchars($profile['city'] ?? ''); ?>">

            <label>Zipcode</label>
            <input class="input" type="text" name="zipcode" value="<?php echo htmlspecialchars($profile['zipcode'] ?? ''); ?>">

            <button class="btn btn-primary" type="submit" name="update_profile">Update Profile</button>
        </form>
    </div>

</div>

<!-- =========================
     Full-width footer section
========================= -->
<div class="site-footer site-footer-full">
    <div class="container">
        <div class="footer-grid">
            <div class="footer-col">
                <h4>About Us</h4>
                <p><?php echo htmlspecialchars(renderFooterDescription()); ?></p>
            </div>
            <div class="footer-col">
                <h4>Contact</h4>
                <p>Email: support@bloodsystem.com</p>
                <p>Location: Copenhagen, Denmark</p>
                <p>Emergency Phone: +4512345678</p>
            </div>
            <div class="footer-col">
                <h4>Quick Information</h4>
                <p><a href="terms.php">Terms and Conditions</a></p>
                <p><a href="faq.php">FAQ</a></p>
                <p><a href="notifications.php">Notifications</a></p>
            </div>
        </div>

        <div class="footer-bottom">
            © 2026 <?php echo htmlspecialchars(renderFooterTitle()); ?> • Final Year Project Prototype
        </div>
    </div>
</div>

</body>
</html>
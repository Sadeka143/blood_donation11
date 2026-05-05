<?php
session_start();
include '../config/db.php';
include '../functions/eligibility_check.php';

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'donor'){
    header("Location: ../auth/login.php");
    exit();
}

$donor_id = $_SESSION['user_id'];

$eligibility = syncDonorAvailabilityStatus($conn, $donor_id);

$notif_stmt = mysqli_prepare($conn, "SELECT COUNT(*) AS total FROM notifications WHERE user_id = ? AND is_read = 0");
mysqli_stmt_bind_param($notif_stmt, "i", $donor_id);
mysqli_stmt_execute($notif_stmt);
$notif_result = mysqli_stmt_get_result($notif_stmt);
$notif_data = mysqli_fetch_assoc($notif_result);
$notif_count = $notif_data['total'];
mysqli_stmt_close($notif_stmt);

if(isset($_POST['update_profile'])){
    $name = trim($_POST['name']);
    $contact_number = trim($_POST['contact_number']);
    $address_line = trim($_POST['address_line']);
    $city = trim($_POST['city']);
    $zipcode = trim($_POST['zipcode']);
    $weight_kg = ($_POST['weight_kg'] !== '') ? (float) $_POST['weight_kg'] : null;
    $availability = trim($_POST['availability']);

    $location_parts = array_filter([$address_line, $city, $zipcode], function($v){
        return $v !== '';
    });
    $location = implode(', ', $location_parts);

    if($name == ""){
        $error = "Name is required.";
    } elseif($weight_kg !== null && $weight_kg <= 0){
        $error = "Weight must be greater than 0.";
    } else {
        if(!$eligibility['eligible']){
            $availability = 'not_available';
        } elseif(!in_array($availability, ['available', 'not_available'])){
            $error = "Please select a valid availability status.";
        }

        if(!isset($error)){
            $update_stmt = mysqli_prepare($conn, "
                UPDATE users
                SET name = ?, contact_number = ?, location = ?, address_line = ?, city = ?, zipcode = ?, weight_kg = ?, availability = ?
                WHERE id = ?
            ");
            mysqli_stmt_bind_param($update_stmt, "ssssssdsi", $name, $contact_number, $location, $address_line, $city, $zipcode, $weight_kg, $availability, $donor_id);

            if(mysqli_stmt_execute($update_stmt)){
                $_SESSION['name'] = $name;
                $success = "Profile updated successfully.";
            } else {
                $error = "Failed to update profile: " . mysqli_error($conn);
            }

            mysqli_stmt_close($update_stmt);
        }
    }

    $eligibility = syncDonorAvailabilityStatus($conn, $donor_id);
}

$profile_stmt = mysqli_prepare($conn, "
    SELECT name, email, contact_number, blood_group, location, address_line, city, zipcode, date_of_birth, weight_kg, availability
    FROM users
    WHERE id = ?
");
mysqli_stmt_bind_param($profile_stmt, "i", $donor_id);
mysqli_stmt_execute($profile_stmt);
$profile_result = mysqli_stmt_get_result($profile_stmt);
$profile = mysqli_fetch_assoc($profile_result);
mysqli_stmt_close($profile_stmt);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Donor Profile</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>

<div class="topbar topbar-full">
    <div class="container">
        <div class="topbar-header">
            <div class="topbar-left">
                <div class="logo">🩸</div>
                <div class="brand-text">
                    <h2>Blood Donation Management System</h2>
                    <p>Donate Blood, Save Lives</p>
                </div>
            </div>

            <div class="topbar-right">
                <a href="notifications.php" class="notify" title="Notifications">
                    🔔
                    <span class="notify-badge"><?php echo $notif_count; ?></span>
                </a>
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?> (Donor)</span>
                <a class="btn btn-logout" href="../auth/logout.php">Logout</a>
            </div>
        </div>

        <div class="topbar-menu">
            <a href="donor_dashboard.php">Dashboard</a>
            <a href="donor_compatible_requests.php">Compatible Requests</a>
            <a href="donor_interested_requests.php">Interested Requests</a>
            <a href="donate_to_stock.php">Donate to Stock</a>
            <a href="donor_appointments.php">Appointments</a>
            <a href="donor_history.php">History</a>
            <a href="donor_profile.php" class="active">Profile</a>
            <a href="notifications.php">Notifications</a>
        </div>
    </div>
</div>

<div class="container">
    <div class="hero">
        <h1>Donor Profile</h1>
        <p>Update your donor information, availability, weight, and detailed location.</p>
    </div>

    <div class="summary-grid">
        <div class="summary-card">
            <h3>Eligibility Status</h3>
            <?php if($eligibility['eligible']) { ?>
                <div class="alert alert-success">
                    You are currently eligible to donate blood.
                </div>
            <?php } else { ?>
                <div class="alert alert-error">
                    You are currently not eligible to donate blood.<br>
                    Reason: <?php echo htmlspecialchars($eligibility['reason']); ?>
                    <?php if(!empty($eligibility['next_eligible_date'])) { ?>
                        <br>Next Eligible Date: <?php echo htmlspecialchars($eligibility['next_eligible_date']); ?>
                    <?php } ?>
                </div>
            <?php } ?>

            <p><strong>Blood Group:</strong> <?php echo htmlspecialchars($profile['blood_group'] ?? ''); ?></p>
            <p><strong>Date of Birth:</strong> <?php echo htmlspecialchars($profile['date_of_birth'] ?? ''); ?></p>
            <p><strong>Weight:</strong> <?php echo htmlspecialchars($profile['weight_kg'] ?? ''); ?> kg</p>
            <p><strong>Availability:</strong> <?php echo htmlspecialchars($profile['availability'] ?? ''); ?></p>
        </div>

        <div class="summary-card">
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

                <label>Address</label>
                <input class="input" type="text" name="address_line" value="<?php echo htmlspecialchars($profile['address_line'] ?? ''); ?>">

                <label>City</label>
                <input class="input" type="text" name="city" value="<?php echo htmlspecialchars($profile['city'] ?? ''); ?>">

                <label>Zipcode</label>
                <input class="input" type="text" name="zipcode" value="<?php echo htmlspecialchars($profile['zipcode'] ?? ''); ?>">

                <label>Weight (kg)</label>
                <input class="input" type="number" step="0.1" name="weight_kg" value="<?php echo htmlspecialchars($profile['weight_kg'] ?? ''); ?>">

                <label>Availability</label>
                <select class="select" name="availability" required <?php echo !$eligibility['eligible'] ? 'disabled' : ''; ?>>
                    <option value="available" <?php if(($profile['availability'] ?? '') == 'available') echo 'selected'; ?>>Available</option>
                    <option value="not_available" <?php if(($profile['availability'] ?? '') == 'not_available') echo 'selected'; ?>>Not Available</option>
                </select>

                <?php if(!$eligibility['eligible']) { ?>
                    <input type="hidden" name="availability" value="not_available">
                    <div class="small-muted locked-note">
                        Availability is temporarily locked by the system until your next eligible date.
                    </div>
                <?php } ?>

                <button class="btn btn-primary" type="submit" name="update_profile">Update Profile</button>
            </form>
        </div>
    </div>
</div>

<div class="site-footer site-footer-full">
    <div class="container">
        <div class="footer-grid">
            <div class="footer-col">
                <h4>About Us</h4>
                <p>Blood Donation Management System is a final year project prototype designed to simulate donor, recipient, blood bank, branch coordination, stock monitoring, donor matching, and appointment management.</p>
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
            © 2026 Blood Donation Management System • Final Year Project Prototype
        </div>
    </div>
</div>

</body>
</html>
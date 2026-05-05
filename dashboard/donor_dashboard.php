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

$stmt = mysqli_prepare($conn, "
    SELECT name, blood_group, date_of_birth, weight_kg, availability, location
    FROM users
    WHERE id = ?
");
mysqli_stmt_bind_param($stmt, "i", $donor_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$donor = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

$notif_stmt = mysqli_prepare($conn, "SELECT COUNT(*) AS total FROM notifications WHERE user_id = ? AND is_read = 0");
mysqli_stmt_bind_param($notif_stmt, "i", $donor_id);
mysqli_stmt_execute($notif_stmt);
$notif_result = mysqli_stmt_get_result($notif_stmt);
$notif_data = mysqli_fetch_assoc($notif_result);
$notif_count = $notif_data['total'];
mysqli_stmt_close($notif_stmt);

$total_donations = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM donations WHERE donor_id = $donor_id"))['total'];
$total_appointments = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM appointments WHERE donor_id = $donor_id"))['total'];
$confirmed_appointments = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM appointments WHERE donor_id = $donor_id AND status = 'confirmed'"))['total'];
$stock_requests = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM stock_donation_requests WHERE donor_id = $donor_id"))['total'];
?>
<!DOCTYPE html>
<html>
<head>
    <title>Donor Dashboard</title>
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
            <a href="donor_dashboard.php" class="active">Dashboard</a>
            <a href="donor_compatible_requests.php">Compatible Requests</a>
            <a href="donor_interested_requests.php">Interested Requests</a>
            <a href="donate_to_stock.php">Donate to Stock</a>
            <a href="donor_appointments.php">Appointments</a>
            <a href="donor_history.php">History</a>
            <a href="donor_profile.php">Profile</a>
            <a href="notifications.php">Notifications</a>
        </div>
    </div>
</div>

<div class="container">
    <div class="hero">
        <h1>Donor Dashboard</h1>
        <p>Participate in request-based donation or donate directly to nearby branch stock.</p>
    </div>

    <div class="stats">
        <a class="stat-link-card" href="donor_history.php">
            <div class="stat-card">
                <div class="stat-title">Total Donations</div>
                <div class="stat-value"><?php echo $total_donations; ?></div>
            </div>
        </a>

        <a class="stat-link-card" href="donor_appointments.php">
            <div class="stat-card">
                <div class="stat-title">Appointments</div>
                <div class="stat-value"><?php echo $total_appointments; ?></div>
            </div>
        </a>

        <a class="stat-link-card" href="donor_appointments.php">
            <div class="stat-card">
                <div class="stat-title">Confirmed</div>
                <div class="stat-value"><?php echo $confirmed_appointments; ?></div>
            </div>
        </a>

        <a class="stat-link-card" href="donate_to_stock.php">
            <div class="stat-card">
                <div class="stat-title">Stock Requests</div>
                <div class="stat-value"><?php echo $stock_requests; ?></div>
            </div>
        </a>
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
                    <?php echo htmlspecialchars($eligibility['reason']); ?>
                    <?php if(!empty($eligibility['next_eligible_date'])) { ?>
                        <br><strong>Next Eligible Date:</strong> <?php echo htmlspecialchars($eligibility['next_eligible_date']); ?>
                    <?php } ?>
                </div>
            <?php } ?>
        </div>

        <div class="summary-card">
            <h3>Profile Summary</h3>
            <p>
                <strong>Blood Group:</strong> <?php echo htmlspecialchars($donor['blood_group'] ?? ''); ?><br>
                <strong>Location:</strong> <?php echo htmlspecialchars($donor['location'] ?? ''); ?><br>
                <strong>Availability:</strong> <?php echo htmlspecialchars($donor['availability'] ?? ''); ?><br>
                <strong>Weight:</strong> <?php echo htmlspecialchars($donor['weight_kg'] ?? ''); ?> kg
            </p>
            <a class="btn btn-primary dashboard-action-btn" href="donor_profile.php">Manage Profile</a>
        </div>
    </div>

    <div class="summary-grid donor-action-grid">
        <div class="summary-card action-summary-card">
            <h3>Request-Based Donation</h3>
            <p>View compatible recipient requests and express interest to support a specific case.</p>
            <a class="btn btn-primary dashboard-action-btn" href="donor_compatible_requests.php">Open Compatible Requests</a>
        </div>

        <div class="summary-card action-summary-card">
            <h3>Donate to Stock</h3>
            <p>Request a general donation appointment so your donation increases stock at a nearby branch.</p>
            <a class="btn btn-primary dashboard-action-btn" href="donate_to_stock.php">Donate to Stock</a>
        </div>
    </div>
</div>

<div class="site-footer site-footer-full">
    <div class="container">
        <div class="footer-grid">
            <div class="footer-col">
                <h4>About Us</h4>
                <p>Blood Donation Management System is a final year project prototype that simulates donor, recipient, blood bank, branch coordination, stock monitoring, donor matching, and appointment management.</p>
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
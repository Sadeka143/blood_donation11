<?php
# ==============================
# Recipient dashboard controller
# ==============================
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

# --------------------------
# Logged-in recipient details
# --------------------------
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

# --------------------------------------------
# Stats cards data for recipient dashboard page
# --------------------------------------------
$total_requests = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM blood_requests WHERE recipient_id = $recipient_id"))['total'];
$approved_requests = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM blood_requests WHERE recipient_id = $recipient_id AND status = 'approved'"))['total'];
$scheduled_requests = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM blood_requests WHERE recipient_id = $recipient_id AND status = 'scheduled'"))['total'];
$completed_requests = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM blood_requests WHERE recipient_id = $recipient_id AND status = 'completed'"))['total'];
$stock_fulfilled = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM blood_requests WHERE recipient_id = $recipient_id AND fulfillment_source = 'stock'"))['total'];

# -----------------------------------------
# Latest appointment summary card information
# -----------------------------------------
$appointment_stmt = mysqli_prepare($conn, "
    SELECT a.*
    FROM appointments a
    JOIN blood_requests r ON a.request_id = r.id
    WHERE r.recipient_id = ?
    ORDER BY a.appointment_date DESC
    LIMIT 1
");
mysqli_stmt_bind_param($appointment_stmt, "i", $recipient_id);
mysqli_stmt_execute($appointment_stmt);
$appointment_result = mysqli_stmt_get_result($appointment_stmt);
$latest_appointment = mysqli_fetch_assoc($appointment_result);
mysqli_stmt_close($appointment_stmt);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Recipient Dashboard</title>
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

        <!-- =========================
             Recipient menu navigation
        ========================= -->
        <div class="topbar-menu">
            <a href="recipient_dashboard.php" class="active">Dashboard</a>
            <a href="create_request.php">Create Request</a>
            <a href="recipient_requests.php">My Requests</a>
            <a href="recipient_appointments.php">Appointments</a>
            <a href="recipient_profile.php">Profile</a>
            <a href="notifications.php">Notifications</a>
        </div>
    </div>
</div>

<div class="container">

    <!-- =========================
         Hero / heading section
    ========================= -->
    <div class="hero">
        <h1>Recipient Dashboard</h1>
        <p>Create blood requests, monitor stock-based fulfillment or donor-based progress, and review appointment updates from your recipient panel.</p>
    </div>

    <!-- =========================
         Clickable stats cards
    ========================= -->
    <div class="stats">
        <a class="stat-link-card" href="recipient_requests.php">
            <div class="stat-card">
                <div class="stat-title">Total Requests</div>
                <div class="stat-value"><?php echo $total_requests; ?></div>
            </div>
        </a>

        <a class="stat-link-card" href="recipient_requests.php">
            <div class="stat-card">
                <div class="stat-title">Approved</div>
                <div class="stat-value"><?php echo $approved_requests; ?></div>
            </div>
        </a>

        <a class="stat-link-card" href="recipient_appointments.php">
            <div class="stat-card">
                <div class="stat-title">Scheduled</div>
                <div class="stat-value"><?php echo $scheduled_requests; ?></div>
            </div>
        </a>

        <a class="stat-link-card" href="recipient_requests.php">
            <div class="stat-card">
                <div class="stat-title">Completed</div>
                <div class="stat-value"><?php echo $completed_requests; ?></div>
            </div>
        </a>

        <a class="stat-link-card" href="recipient_requests.php">
            <div class="stat-card">
                <div class="stat-title">Fulfilled by Stock</div>
                <div class="stat-value"><?php echo $stock_fulfilled; ?></div>
            </div>
        </a>
    </div>

    <!-- =========================
         Summary cards section
    ========================= -->
    <div class="summary-grid">
        <div class="summary-card">
            <h3>Create Request</h3>
            <p>New requests are checked against nearby branch stock first before entering donor matching workflow.</p>
            <a class="btn btn-primary" href="create_request.php">Create Request</a>
        </div>

        <div class="summary-card">
            <h3>Request Monitoring</h3>
            <p>Track whether your request was fulfilled from stock or continued through donor coordination.</p>
            <a class="btn btn-primary" href="recipient_requests.php">View Requests</a>
        </div>

        <div class="summary-card">
            <h3>Latest Appointment</h3>
            <?php if($latest_appointment){ ?>
                <p>
                    <strong>Date:</strong> <?php echo htmlspecialchars($latest_appointment['appointment_date']); ?><br>
                    <strong>Location:</strong> <?php echo htmlspecialchars($latest_appointment['appointment_location']); ?><br>
                    <strong>Status:</strong> <?php echo htmlspecialchars($latest_appointment['status']); ?>
                </p>
            <?php } else { ?>
                <p>No appointment updates available yet.</p>
            <?php } ?>
        </div>

        <div class="summary-card">
            <h3>Appointments</h3>
            <p>Review branch-based appointment updates scheduled when donor-based donation is required.</p>
            <a class="btn btn-primary" href="recipient_appointments.php">View Appointments</a>
        </div>
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
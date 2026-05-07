<?php
// Start session and connect required files
session_start();
include '../config/db.php';
include '../functions/network_helper.php';
include '../functions/stock_helper.php';
include '../functions/admin_notification_helper.php';

// Allow only admin users to access this page
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin'){
    header("Location: ../auth/login.php");
    exit();
}
// Store logged-in admin id and notification count
$user_id = $_SESSION['user_id'];
$notif_count = getAdminNotificationCount($conn);

// Count all user types for admin statistics(stats card)
$total_users = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM users"))['total'];
$total_donors = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM users WHERE role='donor'"))['total'];
$total_recipients = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM users WHERE role='recipient'"))['total'];
$total_blood_banks = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM users WHERE role='blood_bank'"))['total'];

// Count blood request information
$total_requests = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM blood_requests"))['total'];
$total_pending_requests = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM blood_requests WHERE status='pending_review'"))['total'];
$total_urgent_requests = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM blood_requests WHERE urgency='urgent' AND status IN ('pending_review','approved','matched','scheduled')"))['total'];

// Count request fulfillment sources
$total_stock_fulfilled = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM blood_requests WHERE fulfillment_source='stock'"))['total'];
$total_donor_fulfilled = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM blood_requests WHERE fulfillment_source='donor'"))['total'];

// Count donation records
$total_donations = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM donations"))['total'];
$total_stock_donations = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM donations WHERE donation_type='stock_donation'"))['total'];

// Count appointments and activity logs
$total_appointments = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM appointments"))['total'];
$total_logs = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM activity_logs"))['total'];

// Count branch and stock information
$total_branches = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM branches WHERE is_active = 1"))['total'];
$total_stock_units = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(units_available),0) AS total FROM blood_stock"))['total'];
$total_stock_requests = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM stock_donation_requests"))['total'];
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>

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
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?> (Admin)</span>
                <a class="btn btn-logout" href="../auth/logout.php">Logout</a>
            </div>
        </div>
        <!-- Admin menu links -->
        <div class="topbar-menu">
            <a href="admin_dashboard.php" class="active">Dashboard</a>
            <a href="admin_users.php">Users</a>
            <a href="admin_requests.php">Requests</a>
            <a href="admin_donations.php">Donations</a>
            <a href="admin_appointments.php">Appointments</a>
            <a href="admin_branches.php">Branches</a>
            <a href="admin_stock_requests.php">Stock Requests</a>
            <a href="admin_activity_logs.php">Activity Logs</a>
            <a href="notifications.php">Notifications</a>
        </div>
    </div>
</div>

<div class="container">

        <!-- Dashboard introduction section -->
    <div class="hero">
        <h1>Admin Dashboard</h1>
        <p>Monitor users, network branches, request fulfillment source, stock activity, appointments, donations, and audit logs from one central admin panel.</p>
    </div>

    <!-- Urgent request alert message -->
    <?php if($total_urgent_requests > 0){ ?>
        <div class="alert-banner-urgent">
            <h3>Emergency System Alert</h3>
            <p>There are currently <strong><?php echo $total_urgent_requests; ?></strong> urgent active request(s) across the platform requiring priority attention.</p>
        </div>
    <?php } ?>
    <!-- Admin statistics cards -->
    <div class="stats">
                <!-- Urgent request statistics -->
        <a class="stat-link-card" href="admin_requests.php?urgency=urgent">
            <div class="stat-card urgent-stat-card">
                <div class="stat-title">Urgent Requests</div>
                <div class="stat-value"><?php echo $total_urgent_requests; ?></div>
            </div>
        </a>
        <!-- User statistics -->
        <a class="stat-link-card" href="admin_users.php">
            <div class="stat-card">
                <div class="stat-title">Total Users</div>
                <div class="stat-value"><?php echo $total_users; ?></div>
            </div>
        </a>

        <a class="stat-link-card" href="admin_users.php?role=donor">
            <div class="stat-card">
                <div class="stat-title">Donors</div>
                <div class="stat-value"><?php echo $total_donors; ?></div>
            </div>
        </a>

        <a class="stat-link-card" href="admin_users.php?role=recipient">
            <div class="stat-card">
                <div class="stat-title">Recipients</div>
                <div class="stat-value"><?php echo $total_recipients; ?></div>
            </div>
        </a>

        <a class="stat-link-card" href="admin_users.php?role=blood_bank">
            <div class="stat-card">
                <div class="stat-title">Blood Banks</div>
                <div class="stat-value"><?php echo $total_blood_banks; ?></div>
            </div>
        </a>
        <!-- Blood request statistics -->
        <a class="stat-link-card" href="admin_requests.php">
            <div class="stat-card">
                <div class="stat-title">Total Requests</div>
                <div class="stat-value"><?php echo $total_requests; ?></div>
            </div>
        </a>

        <a class="stat-link-card" href="admin_requests.php?request_status=pending_review">
            <div class="stat-card">
                <div class="stat-title">Pending Review</div>
                <div class="stat-value"><?php echo $total_pending_requests; ?></div>
            </div>
        </a>

        <!-- Fulfillment source statistics -->
        <a class="stat-link-card" href="admin_requests.php?fulfillment_source=stock">
            <div class="stat-card">
                <div class="stat-title">Stock Fulfilled</div>
                <div class="stat-value"><?php echo $total_stock_fulfilled; ?></div>
            </div>
        </a>

        <a class="stat-link-card" href="admin_requests.php?fulfillment_source=donor">
            <div class="stat-card">
                <div class="stat-title">Donor Fulfilled</div>
                <div class="stat-value"><?php echo $total_donor_fulfilled; ?></div>
            </div>
        </a>

        <!-- Donation statistics -->
        <a class="stat-link-card" href="admin_donations.php">
            <div class="stat-card">
                <div class="stat-title">Total Donations</div>
                <div class="stat-value"><?php echo $total_donations; ?></div>
            </div>
        </a>

        <a class="stat-link-card" href="admin_donations.php?donation_type=stock_donation">
            <div class="stat-card">
                <div class="stat-title">Stock Donations</div>
                <div class="stat-value"><?php echo $total_stock_donations; ?></div>
            </div>
        </a>

        <!-- Appointment and branch statistics -->
        <a class="stat-link-card" href="admin_appointments.php">
            <div class="stat-card">
                <div class="stat-title">Appointments</div>
                <div class="stat-value"><?php echo $total_appointments; ?></div>
            </div>
        </a>

        <a class="stat-link-card" href="admin_branches.php">
            <div class="stat-card">
                <div class="stat-title">Branches</div>
                <div class="stat-value"><?php echo $total_branches; ?></div>
            </div>
        </a>

        <a class="stat-link-card" href="admin_branches.php">
            <div class="stat-card">
                <div class="stat-title">Stock Units</div>
                <div class="stat-value"><?php echo $total_stock_units; ?></div>
            </div>
        </a>

        <!-- Stock request and log statistics -->
        <a class="stat-link-card" href="admin_stock_requests.php">
            <div class="stat-card">
                <div class="stat-title">Stock Requests</div>
                <div class="stat-value"><?php echo $total_stock_requests; ?></div>
            </div>
        </a>

        <a class="stat-link-card" href="admin_activity_logs.php">
            <div class="stat-card">
                <div class="stat-title">Activity Logs</div>
                <div class="stat-value"><?php echo $total_logs; ?></div>
            </div>
        </a>
    </div>
    
    <div class="summary-grid">
        <div class="summary-card">
            <h3>Request Oversight</h3>
            <p>Monitor whether requests were fulfilled from stock or through donor-based coordination across the network.</p>
            <a class="btn btn-primary" href="admin_requests.php">Open Requests</a>
        </div>

        <div class="summary-card">
            <h3>Network Branches and Stock</h3>
            <p>Track the branch-enabled blood bank model through total branches, total stock units, stock-group breakdown, and stock donation volume.</p>
            <a class="btn btn-primary" href="admin_branches.php">View Branch Stock Details</a>
        </div>

        <div class="summary-card">
            <h3>Appointments</h3>
            <p>Review branch-based appointments and distinguish request-based appointments from stock donation activity.</p>
            <a class="btn btn-primary" href="admin_appointments.php">Open Appointments</a>
        </div>

        <div class="summary-card">
            <h3>Donations</h3>
            <p>See donation type, assigned branch, and blood bank-level coordination history from the donations page.</p>
            <a class="btn btn-primary" href="admin_donations.php">Open Donations</a>
        </div>

        <div class="summary-card">
            <h3>User Management</h3>
            <p>Manage donors, recipients, blood banks, and administrators from one central admin interface.</p>
            <a class="btn btn-primary" href="admin_users.php">Open Users</a>
        </div>
        
        <!-- Audit log management card -->
        <div class="summary-card">
            <h3>Audit Logs</h3>
            <p>Trace request review, stock fulfillment, donor selection, appointment scheduling, and donation completion logs.</p>
            <a class="btn btn-primary" href="admin_activity_logs.php">Open Activity Logs</a>
        </div>
    </div>
</div>

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
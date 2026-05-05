<?php
session_start();
include '../config/db.php';
include '../functions/network_helper.php';
include '../functions/stock_helper.php';

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'blood_bank'){
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$notif_stmt = mysqli_prepare($conn, "SELECT COUNT(*) AS total FROM notifications WHERE user_id = ? AND is_read = 0");
mysqli_stmt_bind_param($notif_stmt, "i", $user_id);
mysqli_stmt_execute($notif_stmt);
$notif_result = mysqli_stmt_get_result($notif_stmt);
$notif_data = mysqli_fetch_assoc($notif_result);
$notif_count = $notif_data['total'];
mysqli_stmt_close($notif_stmt);

$pending_review_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM blood_requests WHERE status = 'pending_review'"))['total'];
$approved_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM blood_requests WHERE status = 'approved' AND assigned_blood_bank_id = $user_id"))['total'];
$matched_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM blood_requests WHERE status = 'matched' AND assigned_blood_bank_id = $user_id"))['total'];
$scheduled_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM blood_requests WHERE status = 'scheduled' AND assigned_blood_bank_id = $user_id"))['total'];
$completed_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM blood_requests WHERE status = 'completed' AND assigned_blood_bank_id = $user_id"))['total'];
$stock_request_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM stock_donation_requests WHERE blood_bank_user_id = $user_id AND status IN ('pending','scheduled')"))['total'];

$urgent_pending_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM blood_requests WHERE status = 'pending_review' AND urgency = 'urgent'"))['total'];
$urgent_approved_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM blood_requests WHERE status = 'approved' AND urgency = 'urgent' AND assigned_blood_bank_id = $user_id"))['total'];
$total_urgent_count = $urgent_pending_count + $urgent_approved_count;

$total_network_stock = getTotalNetworkStockUnits($conn, $user_id);
$branch_rows = getBranchStockSummary($conn, $user_id);
$branch_count = count($branch_rows);

/* Branch blood-group-wise stock chips */
$branch_stock_map = [];

$chip_sql = "
    SELECT 
        b.id AS branch_id,
        bs.blood_group,
        bs.units_available
    FROM branches b
    LEFT JOIN blood_stock bs ON b.id = bs.branch_id
    WHERE b.blood_bank_user_id = ?
  AND bs.units_available > 0
    ORDER BY b.id ASC, bs.blood_group ASC
";
$chip_stmt = mysqli_prepare($conn, $chip_sql);
mysqli_stmt_bind_param($chip_stmt, "i", $user_id);
mysqli_stmt_execute($chip_stmt);
$chip_result = mysqli_stmt_get_result($chip_stmt);

while($chip_row = mysqli_fetch_assoc($chip_result)){
    $branch_id = (int)$chip_row['branch_id'];
    if(!isset($branch_stock_map[$branch_id])){
        $branch_stock_map[$branch_id] = [];
    }
    $branch_stock_map[$branch_id][] = [
        'blood_group' => $chip_row['blood_group'],
        'units_available' => (int)$chip_row['units_available']
    ];
}
mysqli_stmt_close($chip_stmt);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Blood Bank Dashboard</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>

<div class="topbar topbar-full bloodbank-shell">
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
                <span>Welcome, Central Blood Bank Network (Network Control)</span>
                <a class="btn btn-logout" href="../auth/logout.php">Logout</a>
            </div>
        </div>

        <div class="topbar-menu">
            <a href="blood_bank_dashboard.php" class="active">Dashboard</a>
            <a href="blood_bank_pending.php">Pending</a>
            <a href="blood_bank_approved.php">Approved</a>
            <a href="blood_bank_matched.php">Matched</a>
            <a href="blood_bank_scheduled.php">Scheduled</a>
            <a href="blood_bank_confirmed.php">Confirmed</a>
            <a href="blood_bank_history.php">History</a>
            <a href="blood_bank_stock_requests.php">Stock Requests</a>
            <a href="notifications.php">Notifications</a>
        </div>
    </div>
</div>

<div class="container">

    <div class="hero">
        <h1>Central Blood Bank Network Dashboard</h1>
        <p>Manage requests, branches, stock-aware fulfillment, donor matching, stock donation requests, and branch-based appointment scheduling.</p>
    </div>

    <?php if($total_urgent_count > 0){ ?>
        <div class="alert-banner-urgent">
            <h3>Emergency Alert</h3>
            <p>You currently have <strong><?php echo $total_urgent_count; ?></strong> urgent request(s) requiring priority review across the network.</p>
        </div>
    <?php } ?>

    <div class="stats">
        <a class="stat-link-card" href="blood_bank_pending.php?urgency=urgent">
            <div class="stat-card urgent-stat-card">
                <div class="stat-title">Urgent Requests</div>
                <div class="stat-value"><?php echo $total_urgent_count; ?></div>
            </div>
        </a>

        <a class="stat-link-card" href="blood_bank_pending.php">
            <div class="stat-card">
                <div class="stat-title">Pending Review</div>
                <div class="stat-value"><?php echo $pending_review_count; ?></div>
            </div>
        </a>

        <a class="stat-link-card" href="blood_bank_approved.php">
            <div class="stat-card">
                <div class="stat-title">Approved</div>
                <div class="stat-value"><?php echo $approved_count; ?></div>
            </div>
        </a>

        <a class="stat-link-card" href="blood_bank_matched.php">
            <div class="stat-card">
                <div class="stat-title">Matched</div>
                <div class="stat-value"><?php echo $matched_count; ?></div>
            </div>
        </a>

        <a class="stat-link-card" href="blood_bank_scheduled.php">
            <div class="stat-card">
                <div class="stat-title">Scheduled</div>
                <div class="stat-value"><?php echo $scheduled_count; ?></div>
            </div>
        </a>

        <a class="stat-link-card" href="blood_bank_history.php">
            <div class="stat-card">
                <div class="stat-title">Completed</div>
                <div class="stat-value"><?php echo $completed_count; ?></div>
            </div>
        </a>

        <a class="stat-link-card" href="blood_bank_stock_requests.php">
            <div class="stat-card">
                <div class="stat-title">Stock Donation Requests</div>
                <div class="stat-value"><?php echo $stock_request_count; ?></div>
            </div>
        </a>

        <div class="stat-card">
            <div class="stat-title">Network Branches</div>
            <div class="stat-value"><?php echo $branch_count; ?></div>
        </div>

        <div class="stat-card">
            <div class="stat-title">Stock Units</div>
            <div class="stat-value"><?php echo $total_network_stock; ?></div>
            <div class="stat-sub">Across all branches</div>
        </div>
    </div>

    <div class="summary-grid">
        <div class="summary-card branch-overview-card">
            <div class="branch-overview-header">
                <div>
                    <h3>Branch Overview</h3>
                    <p class="branch-overview-subtext">Monitor all network branches and their current stock distribution.</p>
                </div>
                <div class="branch-overview-total">
                    <span class="branch-overview-total-label">Total Network Stock</span>
                    <span class="branch-overview-total-value"><?php echo $total_network_stock; ?> units</span>
                </div>
            </div>

            <?php if(!empty($branch_rows)){ ?>
                <div class="branch-overview-grid">
                    <?php foreach($branch_rows as $branch){ ?>
                        <?php
                        $branch_id = (int)$branch['id'];
                        $chips = $branch_stock_map[$branch_id] ?? [];
                        ?>
                        <div class="branch-overview-item">
                            <div class="branch-overview-top">
                                <h4><?php echo htmlspecialchars($branch['branch_name']); ?></h4>
                                <span class="branch-stock-badge"><?php echo (int)$branch['total_units']; ?> units</span>
                            </div>

                            <p class="branch-overview-location">
                                <?php echo htmlspecialchars($branch['city']); ?>, <?php echo htmlspecialchars($branch['zipcode']); ?>
                            </p>

                            <div class="branch-overview-meta">
                                <span class="branch-meta-chip">Branch Stock Available</span>
                            </div>

                            <div class="blood-group-chip-wrap">
                                <?php if(!empty($chips)){ ?>
                                    <?php foreach($chips as $chip){ ?>
                                        <span class="blood-group-stock-chip">
                                            <?php echo htmlspecialchars($chip['blood_group']); ?>:
                                            <strong><?php echo (int)$chip['units_available']; ?>u</strong>
                                        </span>
                                    <?php } ?>
                                <?php } else { ?>
                                    <span class="blood-group-stock-chip blood-group-stock-chip-empty">No stock available</span>
                                <?php } ?>
                            </div>
                        </div>
                    <?php } ?>
                </div>
            <?php } else { ?>
                <p>No branches found yet.</p>
            <?php } ?>
        </div>

        <div class="summary-card">
            <h3>Pending Review</h3>
            <p>Review recipient requests, preview stock outcome, and decide whether the case will be fulfilled from stock or continue to donor matching.</p>
            <a class="btn btn-primary" href="blood_bank_pending.php">Open Pending Requests</a>
        </div>

        <div class="summary-card">
            <h3>Stock Donations</h3>
            <p>Review donor requests to donate directly to stock and schedule branch-based stock donation appointments.</p>
            <a class="btn btn-primary" href="blood_bank_stock_requests.php">Open Stock Donations</a>
        </div>
    </div>

</div>

<div class="site-footer site-footer-full bloodbank-shell-footer">
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
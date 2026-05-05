<?php
session_start();
include '../config/db.php';
include '../functions/network_helper.php';
include '../functions/admin_notification_helper.php';

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin'){
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$notif_count = getAdminNotificationCount($conn);


/* Filters */
$donor_search = isset($_GET['donor_search']) ? trim($_GET['donor_search']) : "";
$status_filter = isset($_GET['status']) ? trim($_GET['status']) : "all";
$branch_search = isset($_GET['branch_search']) ? trim($_GET['branch_search']) : "";

$donor_search_safe = mysqli_real_escape_string($conn, $donor_search);
$branch_search_safe = mysqli_real_escape_string($conn, $branch_search);

$sql = "
    SELECT 
        sdr.*,
        u.name AS donor_name,
        u.blood_group,
        b.branch_name
    FROM stock_donation_requests sdr
    LEFT JOIN users u ON sdr.donor_id = u.id
    LEFT JOIN branches b ON sdr.branch_id = b.id
    WHERE 1=1
";

if($donor_search_safe !== ""){
    $sql .= " AND u.name LIKE '%$donor_search_safe%'";
}
if($status_filter !== "all"){
    $status_safe = mysqli_real_escape_string($conn, $status_filter);
    $sql .= " AND sdr.status = '$status_safe'";
}
if($branch_search_safe !== ""){
    $sql .= " AND b.branch_name LIKE '%$branch_search_safe%'";
}

$sql .= " ORDER BY sdr.id DESC";
$result = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Stock Requests</title>
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

        <div class="topbar-menu">
            <a href="admin_dashboard.php">Dashboard</a>
            <a href="admin_users.php">Users</a>
            <a href="admin_requests.php">Requests</a>
            <a href="admin_donations.php">Donations</a>
            <a href="admin_appointments.php">Appointments</a>
            <a href="admin_branches.php">Branches</a>
            <a href="admin_stock_requests.php" class="active">Stock Requests</a>
            <a href="admin_activity_logs.php">Activity Logs</a>
            <a href="notifications.php">Notifications</a>
        </div>
    </div>
</div>

<div class="container">
    <div class="hero">
        <h1>Stock Donation Requests</h1>
        <p>Monitor all stock donation requests across branches, including donor details, assigned branch, schedule, and status.</p>
    </div>

    <div class="card filter-card-compact">
        <h3>Filter Stock Requests</h3>
        <form method="GET" class="filter-form">
            <div class="filter-grid-2">
                <div class="field">
                    <label>Donor Name</label>
                    <input class="input" type="text" name="donor_search" value="<?php echo htmlspecialchars($donor_search); ?>" placeholder="Search donor">
                </div>

                <div class="field">
                    <label>Status</label>
                    <select class="select" name="status">
                        <option value="all" <?php if($status_filter=='all') echo 'selected'; ?>>All</option>
                        <option value="pending" <?php if($status_filter=='pending') echo 'selected'; ?>>Pending</option>
                        <option value="scheduled" <?php if($status_filter=='scheduled') echo 'selected'; ?>>Scheduled</option>
                        <option value="completed" <?php if($status_filter=='completed') echo 'selected'; ?>>Completed</option>
                        <option value="cancelled" <?php if($status_filter=='cancelled') echo 'selected'; ?>>Cancelled</option>
                    </select>
                </div>

                <div class="field">
                    <label>Assigned Branch</label>
                    <input class="input" type="text" name="branch_search" value="<?php echo htmlspecialchars($branch_search); ?>" placeholder="Search branch">
                </div>
            </div>

            <div class="filter-actions-row">
                <button class="btn btn-primary" type="submit">Apply</button>
                <a class="btn btn-secondary" href="admin_stock_requests.php">Reset</a>
            </div>
        </form>
    </div>

    <div class="card">
        <h3>Stock Requests</h3>

        <div class="table-scroll-admin">
            <?php if(mysqli_num_rows($result) > 0){ ?>
                <table class="compact-admin-table">
                    <tr>
                        <th class="small-cell">ID</th>
                        <th>Donor</th>
                        <th>Blood Group</th>
                        <th>Assigned Branch</th>
                        <th class="date-cell">Preferred Date</th>
                        <th class="date-cell">Scheduled Date</th>
                        <th class="date-cell">Completed At</th>
                        <th class="wrap-cell">Notes</th>
                        <th>Status</th>
                        <th class="date-cell">Created At</th>
                    </tr>

                    <?php while($row = mysqli_fetch_assoc($result)){ ?>
                        <?php
                        $statusBadge = "badge-pending";
                        if($row['status'] === 'scheduled') $statusBadge = "badge-accepted";
                        if($row['status'] === 'completed') $statusBadge = "badge-completed";
                        ?>
                        <tr>
                            <td class="small-cell"><?php echo htmlspecialchars($row['id']); ?></td>
                            <td><?php echo htmlspecialchars($row['donor_name'] ?? 'Unknown'); ?></td>
                            <td><?php echo htmlspecialchars($row['blood_group'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($row['branch_name'] ?? 'Not assigned'); ?></td>
                            <td class="date-cell"><?php echo htmlspecialchars($row['preferred_date'] ?? ''); ?></td>
                            <td class="date-cell"><?php echo htmlspecialchars($row['scheduled_date'] ?? ''); ?></td>
                            <td class="date-cell"><?php echo htmlspecialchars($row['completed_at'] ?? ''); ?></td>
                            <td class="wrap-cell"><?php echo htmlspecialchars($row['notes'] ?? ''); ?></td>
                            <td><span class="badge <?php echo $statusBadge; ?>"><?php echo htmlspecialchars($row['status']); ?></span></td>
                            <td class="date-cell"><?php echo htmlspecialchars($row['created_at'] ?? ''); ?></td>
                        </tr>
                    <?php } ?>
                </table>
            <?php } else { ?>
                <p>No stock donation requests found.</p>
            <?php } ?>
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
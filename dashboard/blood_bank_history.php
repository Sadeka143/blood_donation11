<?php
session_start();
include '../config/db.php';
include '../functions/network_helper.php';

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

$action_filter = isset($_GET['action']) ? trim($_GET['action']) : 'all';
$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
$keyword_safe = mysqli_real_escape_string($conn, $keyword);

$sql = "
    SELECT al.*, u.name, u.role AS user_role
    FROM activity_logs al
    LEFT JOIN users u ON al.user_id = u.id
    WHERE al.user_id = $user_id OR al.user_role = 'blood_bank'
";

if($action_filter !== 'all'){
    $action_safe = mysqli_real_escape_string($conn, $action_filter);
    $sql .= " AND al.action_type = '$action_safe'";
}
if($keyword_safe !== ''){
    $sql .= " AND (
        al.action_type LIKE '%$keyword_safe%' OR
        al.description LIKE '%$keyword_safe%'
    )";
}

$sql .= " ORDER BY al.created_at DESC";
$result = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Blood Bank History</title>
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
            <a href="blood_bank_dashboard.php">Dashboard</a>
            <a href="blood_bank_pending.php">Pending</a>
            <a href="blood_bank_approved.php">Approved</a>
            <a href="blood_bank_matched.php">Matched</a>
            <a href="blood_bank_scheduled.php">Scheduled</a>
            <a href="blood_bank_confirmed.php">Confirmed</a>
            <a href="blood_bank_history.php" class="active">History</a>
            <a href="blood_bank_stock_requests.php">Stock Requests</a>
            <a href="notifications.php">Notifications</a>
        </div>
    </div>
</div>

<div class="container">

    <div class="hero">
        <h1>Blood Bank History</h1>
        <p>Review request approvals, stock fulfillment, donor selection, stock donation scheduling, appointment scheduling, and completed donations.</p>
    </div>

    <div class="card filter-card-compact">
        <h3>Filter History</h3>

        <form method="GET" class="filter-form">
            <div class="filter-grid-2">
                <div class="field">
                    <label>Activity Type</label>
                    <select class="select" name="action">
                        <option value="all" <?php if($action_filter == 'all') echo 'selected'; ?>>All Activities</option>
                        <option value="approve_request" <?php if($action_filter == 'approve_request') echo 'selected'; ?>>Approved Requests</option>
                        <option value="approve_request_stock_fulfilled" <?php if($action_filter == 'approve_request_stock_fulfilled') echo 'selected'; ?>>Fulfilled From Stock</option>
                        <option value="reject_request" <?php if($action_filter == 'reject_request') echo 'selected'; ?>>Rejected Requests</option>
                        <option value="select_donor" <?php if($action_filter == 'select_donor') echo 'selected'; ?>>Selected Donor</option>
                        <option value="select_interested_donor" <?php if($action_filter == 'select_interested_donor') echo 'selected'; ?>>Selected Interested Donor</option>
                        <option value="schedule_appointment" <?php if($action_filter == 'schedule_appointment') echo 'selected'; ?>>Scheduled Appointments</option>
                        <option value="complete_donation" <?php if($action_filter == 'complete_donation') echo 'selected'; ?>>Completed Donations</option>
                        <option value="schedule_stock_donation" <?php if($action_filter == 'schedule_stock_donation') echo 'selected'; ?>>Scheduled Stock Donations</option>
                        <option value="complete_stock_donation" <?php if($action_filter == 'complete_stock_donation') echo 'selected'; ?>>Completed Stock Donations</option>
                    </select>
                </div>

                <div class="field">
                    <label>Keyword</label>
                    <input class="input" type="text" name="keyword" value="<?php echo htmlspecialchars($keyword); ?>" placeholder="Search action or description">
                </div>
            </div>

            <div class="filter-actions-row">
                <button class="btn btn-primary" type="submit">Apply</button>
                <a class="btn btn-secondary" href="blood_bank_history.php">Reset</a>
            </div>
        </form>
    </div>

    <div class="card">
        <h3>Activity History</h3>

        <?php if(mysqli_num_rows($result) > 0){ ?>
            <div class="table-scroll-admin">
                <table class="compact-admin-table">
                    <tr>
                        <th class="small-cell">ID</th>
                        <th>User</th>
                        <th>Role</th>
                        <th>Action</th>
                        <th class="wrap-cell">Description</th>
                        <th class="date-cell">Date & Time</th>
                    </tr>

                    <?php while($row = mysqli_fetch_assoc($result)){ ?>
                        <?php
                        $badgeClass = "badge-pending";
                        if(in_array($row['action_type'], ['approve_request', 'select_donor', 'select_interested_donor', 'schedule_appointment', 'schedule_stock_donation'])){
                            $badgeClass = "badge-accepted";
                        }
                        if(in_array($row['action_type'], ['complete_donation', 'approve_request_stock_fulfilled', 'complete_stock_donation'])){
                            $badgeClass = "badge-completed";
                        }
                        ?>
                        <tr>
                            <td class="small-cell"><?php echo htmlspecialchars($row['id']); ?></td>
                            <td><?php echo htmlspecialchars($row['name'] ?? getBloodBankWelcomeLabel()); ?></td>
                            <td><span class="badge badge-accepted"><?php echo htmlspecialchars($row['user_role']); ?></span></td>
                            <td><span class="badge <?php echo $badgeClass; ?>"><?php echo htmlspecialchars($row['action_type']); ?></span></td>
                            <td class="wrap-cell"><?php echo htmlspecialchars($row['description']); ?></td>
                            <td class="date-cell"><?php echo htmlspecialchars($row['created_at']); ?></td>
                        </tr>
                    <?php } ?>
                </table>
            </div>
        <?php } else { ?>
            <p>No history records found.</p>
        <?php } ?>
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
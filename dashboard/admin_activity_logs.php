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


$user_search = isset($_GET['user_search']) ? trim($_GET['user_search']) : "";
$role_filter = isset($_GET['role']) ? trim($_GET['role']) : "all";
$action_filter = isset($_GET['action']) ? trim($_GET['action']) : "all";
$date_from = isset($_GET['date_from']) ? trim($_GET['date_from']) : "";
$date_to = isset($_GET['date_to']) ? trim($_GET['date_to']) : "";

$user_search_safe = mysqli_real_escape_string($conn, $user_search);

$sql = "
    SELECT 
        al.*,
        u.name,
        u.institution_name
    FROM activity_logs al
    LEFT JOIN users u ON al.user_id = u.id
    WHERE 1=1
";

if($user_search_safe !== ""){
    $sql .= " AND (
        u.name LIKE '%$user_search_safe%' OR
        u.institution_name LIKE '%$user_search_safe%' OR
        al.description LIKE '%$user_search_safe%'
    )";
}

if($role_filter !== "all"){
    $role_safe = mysqli_real_escape_string($conn, $role_filter);
    $sql .= " AND al.user_role = '$role_safe'";
}

if($action_filter !== "all"){
    $action_safe = mysqli_real_escape_string($conn, $action_filter);
    $sql .= " AND al.action_type = '$action_safe'";
}

if($date_from !== ""){
    $date_from_safe = mysqli_real_escape_string($conn, $date_from);
    $sql .= " AND DATE(al.created_at) >= '$date_from_safe'";
}

if($date_to !== ""){
    $date_to_safe = mysqli_real_escape_string($conn, $date_to);
    $sql .= " AND DATE(al.created_at) <= '$date_to_safe'";
}

$sql .= " ORDER BY al.created_at DESC";
$result = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Activity Logs</title>
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
            <a href="admin_stock_requests.php">Stock Requests</a>
            <a href="admin_activity_logs.php" class="active">Activity Logs</a>
            <a href="notifications.php">Notifications</a>
        </div>
    </div>
</div>

<div class="container">
    <div class="hero">
        <h1>Activity Logs</h1>
        <p>Audit request review, stock fulfillment, donor selection, appointment scheduling, stock donation workflow, and user activities across the system.</p>
    </div>

    <div class="card filter-card-compact">
        <h3>Filter Activity Logs</h3>

        <form method="GET" class="filter-form">
            <div class="filter-grid-2">
                <div class="field">
                    <label>User / Institution / Description</label>
                    <input class="input" type="text" name="user_search" value="<?php echo htmlspecialchars($user_search); ?>" placeholder="Search activity">
                </div>

                <div class="field">
                    <label>Role</label>
                    <select class="select" name="role">
                        <option value="all" <?php if($role_filter=='all') echo 'selected'; ?>>All Roles</option>
                        <option value="admin" <?php if($role_filter=='admin') echo 'selected'; ?>>Admin</option>
                        <option value="blood_bank" <?php if($role_filter=='blood_bank') echo 'selected'; ?>>Blood Bank</option>
                        <option value="donor" <?php if($role_filter=='donor') echo 'selected'; ?>>Donor</option>
                        <option value="recipient" <?php if($role_filter=='recipient') echo 'selected'; ?>>Recipient</option>
                    </select>
                </div>

                <div class="field">
                    <label>Action Type</label>
                    <select class="select" name="action">
                        <option value="all" <?php if($action_filter=='all') echo 'selected'; ?>>All Actions</option>
                        <option value="toggle_user_status" <?php if($action_filter=='toggle_user_status') echo 'selected'; ?>>Toggle User Status</option>
                        <option value="delete_user" <?php if($action_filter=='delete_user') echo 'selected'; ?>>Delete User</option>
                        <option value="create_request" <?php if($action_filter=='create_request') echo 'selected'; ?>>Create Request</option>
                        <option value="approve_request" <?php if($action_filter=='approve_request') echo 'selected'; ?>>Approve Request</option>
                        <option value="approve_request_stock_fulfilled" <?php if($action_filter=='approve_request_stock_fulfilled') echo 'selected'; ?>>Fulfilled From Stock</option>
                        <option value="reject_request" <?php if($action_filter=='reject_request') echo 'selected'; ?>>Reject Request</option>
                        <option value="express_interest" <?php if($action_filter=='express_interest') echo 'selected'; ?>>Express Interest</option>
                        <option value="select_donor" <?php if($action_filter=='select_donor') echo 'selected'; ?>>Select Donor</option>
                        <option value="select_interested_donor" <?php if($action_filter=='select_interested_donor') echo 'selected'; ?>>Select Interested Donor</option>
                        <option value="schedule_appointment" <?php if($action_filter=='schedule_appointment') echo 'selected'; ?>>Schedule Appointment</option>
                        <option value="complete_donation" <?php if($action_filter=='complete_donation') echo 'selected'; ?>>Complete Donation</option>
                        <option value="schedule_stock_donation" <?php if($action_filter=='schedule_stock_donation') echo 'selected'; ?>>Schedule Stock Donation</option>
                        <option value="complete_stock_donation" <?php if($action_filter=='complete_stock_donation') echo 'selected'; ?>>Complete Stock Donation</option>
                    </select>
                </div>

                <div class="field">
                    <label>From Date</label>
                    <input class="input" type="date" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>">
                </div>

                <div class="field">
                    <label>To Date</label>
                    <input class="input" type="date" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>">
                </div>
            </div>

            <div class="filter-actions-row">
                <button class="btn btn-primary" type="submit">Apply</button>
                <a class="btn btn-secondary" href="admin_activity_logs.php">Reset</a>
            </div>
        </form>
    </div>

    <div class="card">
        <h3>Activity Log List</h3>

        <div class="table-scroll-admin">
            <?php if(mysqli_num_rows($result) > 0){ ?>
                <table class="compact-admin-table">
                    <tr>
                        <th class="small-cell">ID</th>
                        <th>User / Institution</th>
                        <th>Role</th>
                        <th>Action</th>
                        <th class="wrap-cell">Description</th>
                        <th class="date-cell">Created At</th>
                    </tr>

                    <?php while($row = mysqli_fetch_assoc($result)){ ?>
                        <?php
                        $displayName = ($row['user_role'] === 'blood_bank')
                            ? getBloodBankNetworkName()
                            : ($row['name'] ?? 'System');

                        $actionBadge = "badge-pending";
                        if(in_array($row['action_type'], ['approve_request','select_donor','select_interested_donor','schedule_appointment','schedule_stock_donation'])){
                            $actionBadge = "badge-accepted";
                        }
                        if(in_array($row['action_type'], ['complete_donation','approve_request_stock_fulfilled','complete_stock_donation'])){
                            $actionBadge = "badge-completed";
                        }
                        if(in_array($row['action_type'], ['toggle_user_status'])){
                            $actionBadge = "badge-pending";
                            }
                            if(in_array($row['action_type'], ['delete_user'])){
                                $actionBadge = "badge-completed";
                                }
                        ?>
                        <tr>
                            <td class="small-cell"><?php echo htmlspecialchars($row['id']); ?></td>
                            <td><?php echo htmlspecialchars($displayName); ?></td>
                            <td><span class="badge badge-accepted"><?php echo htmlspecialchars($row['user_role']); ?></span></td>
                            <td><span class="badge <?php echo $actionBadge; ?>"><?php echo htmlspecialchars($row['action_type']); ?></span></td>
                            <td class="wrap-cell"><?php echo htmlspecialchars($row['description']); ?></td>
                            <td class="date-cell"><?php echo htmlspecialchars($row['created_at']); ?></td>
                        </tr>
                    <?php } ?>
                </table>
            <?php } else { ?>
                <p>No activity logs found.</p>
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
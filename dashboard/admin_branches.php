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
$search = isset($_GET['search']) ? trim($_GET['search']) : "";
$city = isset($_GET['city']) ? trim($_GET['city']) : "";
$status_filter = isset($_GET['status']) ? trim($_GET['status']) : "all";

$search_safe = mysqli_real_escape_string($conn, $search);
$city_safe = mysqli_real_escape_string($conn, $city);

$sql = "
    SELECT 
        b.*,
        COALESCE(SUM(bs.units_available), 0) AS total_units
    FROM branches b
    LEFT JOIN blood_stock bs ON b.id = bs.branch_id
    WHERE 1=1
";

if($search_safe !== ""){
    $sql .= " AND (
        b.branch_name LIKE '%$search_safe%' OR
        b.address_line LIKE '%$search_safe%' OR
        b.zipcode LIKE '%$search_safe%'
    )";
}

if($city_safe !== ""){
    $sql .= " AND b.city LIKE '%$city_safe%'";
}

if($status_filter !== "all"){
    if($status_filter === "active"){
        $sql .= " AND b.is_active = 1";
    } elseif($status_filter === "inactive"){
        $sql .= " AND b.is_active = 0";
    }
}

$sql .= " GROUP BY b.id ORDER BY b.id DESC";
$result = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Branches</title>
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
            <a href="admin_branches.php" class="active">Branches</a>
            <a href="admin_stock_requests.php">Stock Requests</a>
            <a href="admin_activity_logs.php">Activity Logs</a>
            <a href="notifications.php">Notifications</a>
        </div>
    </div>
</div>

<div class="container">
    <div class="hero">
        <h1>Network Branches & Stock</h1>
        <p>View all branches, total stock units, and blood-group-wise stock distribution across the network.</p>
    </div>

    <div class="card filter-card-compact">
        <h3>Filter Branches</h3>
        <form method="GET" class="filter-form">
            <div class="filter-grid-2">
                <div class="field">
                    <label>Branch / Address / Zipcode</label>
                    <input class="input" type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search branch">
                </div>

                <div class="field">
                    <label>City</label>
                    <input class="input" type="text" name="city" value="<?php echo htmlspecialchars($city); ?>" placeholder="Search city">
                </div>

                <div class="field">
                    <label>Status</label>
                    <select class="select" name="status">
                        <option value="all" <?php if($status_filter=='all') echo 'selected'; ?>>All</option>
                        <option value="active" <?php if($status_filter=='active') echo 'selected'; ?>>Active</option>
                        <option value="inactive" <?php if($status_filter=='inactive') echo 'selected'; ?>>Inactive</option>
                    </select>
                </div>
            </div>

            <div class="filter-actions-row">
                <button class="btn btn-primary" type="submit">Apply</button>
                <a class="btn btn-secondary" href="admin_branches.php">Reset</a>
            </div>
        </form>
    </div>

    <div class="card">
        <h3>Branches Overview</h3>

        <div class="table-scroll-admin">
            <?php if(mysqli_num_rows($result) > 0){ ?>
                <table class="compact-admin-table">
                    <tr>
                        <th class="small-cell">ID</th>
                        <th>Branch Name</th>
                        <th class="wrap-cell">Address</th>
                        <th>City</th>
                        <th>Zipcode</th>
                        <th>Status</th>
                        <th>Total Stock</th>
                        <th class="wrap-cell">Blood Groups</th>
                    </tr>

                    <?php while($row = mysqli_fetch_assoc($result)){ ?>
                        <?php
                        $branch_id = (int)$row['id'];

                        $group_q = mysqli_query($conn, "
                            SELECT blood_group, units_available
                            FROM blood_stock
                            WHERE branch_id = $branch_id
                            ORDER BY blood_group ASC
                        ");

                        $group_list = [];
                        while($g = mysqli_fetch_assoc($group_q)){
                            $group_list[] = $g['blood_group'] . ' (' . $g['units_available'] . ')';
                        }

                        $statusBadge = ((int)$row['is_active'] === 1) ? 'badge-accepted' : 'badge-pending';
                        ?>
                        <tr>
                            <td class="small-cell"><?php echo htmlspecialchars($row['id']); ?></td>
                            <td><?php echo htmlspecialchars($row['branch_name']); ?></td>
                            <td class="wrap-cell"><?php echo htmlspecialchars($row['address_line'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($row['city'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($row['zipcode'] ?? ''); ?></td>
                            <td>
                                <span class="badge <?php echo $statusBadge; ?>">
                                    <?php echo ((int)$row['is_active'] === 1) ? 'Active' : 'Inactive'; ?>
                                </span>
                            </td>
                            <td><strong><?php echo htmlspecialchars($row['total_units']); ?></strong></td>
                            <td class="wrap-cell">
                                <?php echo !empty($group_list) ? htmlspecialchars(implode(', ', $group_list)) : 'No stock'; ?>
                            </td>
                        </tr>
                    <?php } ?>
                </table>
            <?php } else { ?>
                <p>No branches found.</p>
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
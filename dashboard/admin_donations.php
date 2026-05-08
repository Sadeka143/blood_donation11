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


$donor_search = isset($_GET['donor_search']) ? trim($_GET['donor_search']) : "";
$blood_group = isset($_GET['blood_group']) ? trim($_GET['blood_group']) : "all";
$donation_type = isset($_GET['donation_type']) ? trim($_GET['donation_type']) : "all";
$status_filter = isset($_GET['status']) ? trim($_GET['status']) : "all";
$date_from = isset($_GET['date_from']) ? trim($_GET['date_from']) : "";
$date_to = isset($_GET['date_to']) ? trim($_GET['date_to']) : "";

$donor_search_safe = mysqli_real_escape_string($conn, $donor_search);

$sql = "
    SELECT 
        d.*,
        u.name AS donor_name,
        COALESCE(r.blood_group, u.blood_group) AS blood_group,
        CASE 
            WHEN d.donation_type = 'stock_donation' THEN 'Branch Stock Donation'
            ELSE r.location
        END AS location,
        CASE 
            WHEN d.donation_type = 'stock_donation' THEN 1
            ELSE r.quantity
        END AS quantity,
        b.branch_name,
        bb.name AS blood_bank_name,
        bb.institution_name
    FROM donations d
    JOIN users u ON d.donor_id = u.id
    LEFT JOIN blood_requests r ON d.request_id = r.id
    LEFT JOIN branches b ON d.branch_id = b.id
    LEFT JOIN users bb ON d.blood_bank_id = bb.id
    WHERE 1=1
";

if($donor_search_safe !== ""){
    $sql .= " AND u.name LIKE '%$donor_search_safe%'";
}
if($blood_group !== "all"){
    $group_safe = mysqli_real_escape_string($conn, $blood_group);
    $sql .= " AND COALESCE(r.blood_group, u.blood_group) = '$group_safe'";
}
if($donation_type !== "all"){
    $type_safe = mysqli_real_escape_string($conn, $donation_type);
    $sql .= " AND d.donation_type = '$type_safe'";
}
if($status_filter !== "all"){
    $status_safe = mysqli_real_escape_string($conn, $status_filter);
    $sql .= " AND d.status = '$status_safe'";
}
if($date_from !== ""){
    $date_from_safe = mysqli_real_escape_string($conn, $date_from);
    $sql .= " AND DATE(d.donation_date) >= '$date_from_safe'";
}
if($date_to !== ""){
    $date_to_safe = mysqli_real_escape_string($conn, $date_to);
    $sql .= " AND DATE(d.donation_date) <= '$date_to_safe'";
}

$sql .= " ORDER BY d.id DESC";
$result = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Donations</title>
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
            <a href="admin_donations.php" class="active">Donations</a>
            <a href="admin_appointments.php">Appointments</a>
            <a href="admin_branches.php">Branches</a>
            <a href="admin_stock_requests.php">Stock Requests</a>
            <a href="admin_activity_logs.php">Activity Logs</a>
            <a href="notifications.php">Notifications</a>
        </div>
    </div>
</div>

<div class="container">
    <div class="hero">
        <h1>Donations Overview</h1>
        <p>Review stock donations and request-based donations with branch assignment, blood bank coordination, and completion timeline.</p>
    </div>

    <div class="card filter-card-compact">
        <h3>Filter Donations</h3>
        <form method="GET" class="filter-form">
            <div class="filter-grid-2">
                <div class="field">
                    <label>Donor Name</label>
                    <input class="input" type="text" name="donor_search" value="<?php echo htmlspecialchars($donor_search); ?>" placeholder="Search donor">
                </div>

                <div class="field">
                    <label>Blood Group</label>
                    <select class="select" name="blood_group">
                        <option value="all" <?php if($blood_group=='all') echo 'selected'; ?>>All Groups</option>
                        <option value="A+" <?php if($blood_group=='A+') echo 'selected'; ?>>A+</option>
                        <option value="A-" <?php if($blood_group=='A-') echo 'selected'; ?>>A-</option>
                        <option value="B+" <?php if($blood_group=='B+') echo 'selected'; ?>>B+</option>
                        <option value="B-" <?php if($blood_group=='B-') echo 'selected'; ?>>B-</option>
                        <option value="O+" <?php if($blood_group=='O+') echo 'selected'; ?>>O+</option>
                        <option value="O-" <?php if($blood_group=='O-') echo 'selected'; ?>>O-</option>
                        <option value="AB+" <?php if($blood_group=='AB+') echo 'selected'; ?>>AB+</option>
                        <option value="AB-" <?php if($blood_group=='AB-') echo 'selected'; ?>>AB-</option>
                    </select>
                </div>

                <div class="field">
                    <label>Donation Type</label>
                    <select class="select" name="donation_type">
                        <option value="all" <?php if($donation_type=='all') echo 'selected'; ?>>All</option>
                        <option value="request_based" <?php if($donation_type=='request_based') echo 'selected'; ?>>Request Donation</option>
                        <option value="stock_donation" <?php if($donation_type=='stock_donation') echo 'selected'; ?>>Stock Donation</option>
                    </select>
                </div>

                <div class="field">
                    <label>Status</label>
                    <select class="select" name="status">
                        <option value="all" <?php if($status_filter=='all') echo 'selected'; ?>>All</option>
                        <option value="confirmed" <?php if($status_filter=='confirmed') echo 'selected'; ?>>Confirmed</option>
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
                <a class="btn btn-secondary" href="admin_donations.php">Reset</a>
            </div>
        </form>
    </div>

    <div class="card">
    <div class="admin-list-header">
        <h3>Donation List</h3>

        <div class="admin-export-actions">
            <a class="btn btn-primary" href="export_donations.php">Export Donations CSV</a>

            <a class="btn btn-primary" href="export_donations_filtered.php?<?php echo http_build_query([
                'donor_search' => $donor_search,
                'blood_group' => $blood_group,
                'donation_type' => $donation_type,
                'status' => $status_filter,
                'date_from' => $date_from,
                'date_to' => $date_to
            ]); ?>">Export Filtered CSV</a>
        </div>
    </div>

        <div class="table-scroll-admin">
            <?php if(mysqli_num_rows($result) > 0){ ?>
                <table class="compact-admin-table">
                    <tr>
                        <th class="small-cell">Donation ID</th>
                        <th>Donor</th>
                        <th>Type</th>
                        <th>Blood Group</th>
                        <th class="wrap-cell">Branch</th>
                        <th>Blood Bank</th>
                        <th class="wrap-cell">Request Location</th>
                        <th class="small-cell">Quantity</th>
                        <th class="date-cell">Scheduled Date</th>
                        <th class="date-cell">Completed At</th>
                        <th class="date-cell">Donation Date</th>
                        <th>Status</th>
                    </tr>

                    <?php while($row = mysqli_fetch_assoc($result)){ ?>
                        <?php
                        $bankDisplay = getBloodBankDisplayName($row['institution_name'] ?? '', $row['blood_bank_name'] ?? '');
                        ?>
                        <tr>
                            <td class="small-cell"><?php echo htmlspecialchars($row['id']); ?></td>
                            <td><?php echo htmlspecialchars($row['donor_name']); ?></td>

                            <td>
                                <?php if(($row['donation_type'] ?? '') === 'stock_donation'){ ?>
                                    <span class="mini-badge mini-blue">Stock Donation</span>
                                <?php } else { ?>
                                    <span class="mini-badge mini-green">Request Donation</span>
                                <?php } ?>
                            </td>

                            <td><?php echo htmlspecialchars($row['blood_group'] ?? 'N/A'); ?></td>
                            <td class="wrap-cell"><?php echo htmlspecialchars($row['branch_name'] ?? 'Not assigned'); ?></td>
                            <td><?php echo htmlspecialchars($bankDisplay); ?></td>
                            <td class="wrap-cell"><?php echo htmlspecialchars($row['location'] ?? 'N/A'); ?></td>
                            <td class="small-cell"><?php echo htmlspecialchars($row['quantity'] ?? 'N/A'); ?></td>
                            <td class="date-cell"><?php echo htmlspecialchars($row['scheduled_date'] ?? ''); ?></td>
                            <td class="date-cell"><?php echo htmlspecialchars($row['completed_at'] ?? ''); ?></td>
                            <td class="date-cell"><?php echo htmlspecialchars($row['donation_date'] ?? ''); ?></td>
                            <td><span class="badge badge-completed"><?php echo htmlspecialchars($row['status']); ?></span></td>
                        </tr>
                    <?php } ?>
                </table>
            <?php } else { ?>
                <p>No donations found.</p>
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
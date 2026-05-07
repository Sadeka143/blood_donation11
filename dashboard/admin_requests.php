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


$recipient_search = isset($_GET['recipient_search']) ? trim($_GET['recipient_search']) : "";
$blood_group = isset($_GET['blood_group']) ? trim($_GET['blood_group']) : "all";
$request_status = isset($_GET['request_status']) ? trim($_GET['request_status']) : "all";
$urgency = isset($_GET['urgency']) ? trim($_GET['urgency']) : "all";
$fulfillment_source = isset($_GET['fulfillment_source']) ? trim($_GET['fulfillment_source']) : "all";
$location_search = isset($_GET['location_search']) ? trim($_GET['location_search']) : "";
$date_from = isset($_GET['date_from']) ? trim($_GET['date_from']) : "";
$date_to = isset($_GET['date_to']) ? trim($_GET['date_to']) : "";

$recipient_search_safe = mysqli_real_escape_string($conn, $recipient_search);
$location_search_safe = mysqli_real_escape_string($conn, $location_search);

$sql = "
    SELECT 
        r.*,
        u.name AS recipient_name,
        d.name AS donor_name,
        bb.name AS blood_bank_name,
        bb.institution_name,
        b.branch_name
    FROM blood_requests r
    JOIN users u ON r.recipient_id = u.id
    LEFT JOIN users d ON r.matched_donor_id = d.id
    LEFT JOIN users bb ON r.assigned_blood_bank_id = bb.id
    LEFT JOIN branches b ON r.fulfilled_branch_id = b.id
    WHERE 1=1
";

if($recipient_search_safe !== ""){
    $sql .= " AND u.name LIKE '%$recipient_search_safe%'";
}
if($blood_group !== "all"){
    $group_safe = mysqli_real_escape_string($conn, $blood_group);
    $sql .= " AND r.blood_group = '$group_safe'";
}
if($request_status !== "all"){
    $status_safe = mysqli_real_escape_string($conn, $request_status);
    $sql .= " AND r.status = '$status_safe'";
}
if($urgency !== "all"){
    $urgency_safe = mysqli_real_escape_string($conn, $urgency);
    $sql .= " AND r.urgency = '$urgency_safe'";
}
if($fulfillment_source !== "all"){
    $source_safe = mysqli_real_escape_string($conn, $fulfillment_source);
    $sql .= " AND r.fulfillment_source = '$source_safe'";
}
if($location_search_safe !== ""){
    $sql .= " AND (
        r.location LIKE '%$location_search_safe%' OR
        r.address_line LIKE '%$location_search_safe%' OR
        r.city LIKE '%$location_search_safe%' OR
        r.zipcode LIKE '%$location_search_safe%'
    )";
}
if($date_from !== ""){
    $date_from_safe = mysqli_real_escape_string($conn, $date_from);
    $sql .= " AND DATE(r.created_at) >= '$date_from_safe'";
}
if($date_to !== ""){
    $date_to_safe = mysqli_real_escape_string($conn, $date_to);
    $sql .= " AND DATE(r.created_at) <= '$date_to_safe'";
}

$sql .= " ORDER BY r.created_at DESC";
$result = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Requests</title>
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
            <a href="admin_requests.php" class="active">Requests</a>
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
    <div class="hero">
        <h1>Requests Overview</h1>
        <p>Track request status, fulfillment source, matched donor, responsible blood bank, and assigned branch across the full platform.</p>
    </div>

    <div class="card filter-card-compact">
        <h3>Filter Requests</h3>
        <form method="GET" class="filter-form">
            <div class="filter-grid-2">
                <div class="field">
                    <label>Recipient Name</label>
                    <input class="input" type="text" name="recipient_search" value="<?php echo htmlspecialchars($recipient_search); ?>" placeholder="Search recipient">
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
                    <label>Status</label>
                    <select class="select" name="request_status">
                        <option value="all" <?php if($request_status=='all') echo 'selected'; ?>>All</option>
                        <option value="pending_review" <?php if($request_status=='pending_review') echo 'selected'; ?>>Pending Review</option>
                        <option value="approved" <?php if($request_status=='approved') echo 'selected'; ?>>Approved</option>
                        <option value="matched" <?php if($request_status=='matched') echo 'selected'; ?>>Matched</option>
                        <option value="scheduled" <?php if($request_status=='scheduled') echo 'selected'; ?>>Scheduled</option>
                        <option value="completed" <?php if($request_status=='completed') echo 'selected'; ?>>Completed</option>
                        <option value="rejected" <?php if($request_status=='rejected') echo 'selected'; ?>>Rejected</option>
                    </select>
                </div>

                <div class="field">
                    <label>Urgency</label>
                    <select class="select" name="urgency">
                        <option value="all" <?php if($urgency=='all') echo 'selected'; ?>>All</option>
                        <option value="normal" <?php if($urgency=='normal') echo 'selected'; ?>>Normal</option>
                        <option value="urgent" <?php if($urgency=='urgent') echo 'selected'; ?>>Urgent</option>
                    </select>
                </div>

                <div class="field">
                    <label>Fulfillment Source</label>
                    <select class="select" name="fulfillment_source">
                        <option value="all" <?php if($fulfillment_source=='all') echo 'selected'; ?>>All</option>
                        <option value="none" <?php if($fulfillment_source=='none') echo 'selected'; ?>>None / Review Pending</option>
                        <option value="stock" <?php if($fulfillment_source=='stock') echo 'selected'; ?>>From Stock</option>
                        <option value="donor" <?php if($fulfillment_source=='donor') echo 'selected'; ?>>Donor-Based</option>
                    </select>
                </div>

                <div class="field">
                    <label>Location</label>
                    <input class="input" type="text" name="location_search" value="<?php echo htmlspecialchars($location_search); ?>" placeholder="Search address / city / zipcode">
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
    <a class="btn btn-secondary" href="admin_requests.php">Reset</a>

    <!-- Export all requests -->
    <a class="btn btn-primary" href="export_requests.php">Export Requests CSV</a>

    <!-- Export requests using current filter values -->
    <a class="btn btn-primary" href="export_requests_filtered.php?<?php echo http_build_query([
        'recipient_search' => $recipient_search,
        'blood_group' => $blood_group,
        'request_status' => $request_status,
        'urgency' => $urgency,
        'fulfillment_source' => $fulfillment_source,
        'location_search' => $location_search,
        'date_from' => $date_from,
        'date_to' => $date_to
    ]); ?>">Export Filtered CSV</a>
</div>
        </form>
    </div>

    <div class="card">
        <h3>Request List</h3>

        <div class="table-scroll-admin">
            <?php if(mysqli_num_rows($result) > 0){ ?>
                <table class="compact-admin-table">
                    <tr>
                        <th class="small-cell">Request ID</th>
                        <th>Recipient</th>
                        <th>Blood Group</th>
                        <th class="wrap-cell">Location</th>
                        <th class="small-cell">Qty</th>
                        <th>Urgency</th>
                        <th>Status</th>
                        <th>Fulfillment</th>
                        <th>Matched Donor</th>
                        <th>Blood Bank</th>
                        <th class="wrap-cell">Branch</th>
                        <th class="date-cell">Created At</th>
                    </tr>

                    <?php while($row = mysqli_fetch_assoc($result)){ ?>
                        <?php
                        $statusBadge = "badge-pending";
                        if(in_array($row['status'], ['approved','matched','scheduled','confirmed'])) $statusBadge = "badge-accepted";
                        if($row['status'] === 'completed') $statusBadge = "badge-completed";

                        $bankDisplay = getBloodBankDisplayName($row['institution_name'] ?? '', $row['blood_bank_name'] ?? '');
                        $branchDisplay = $row['branch_name'] ?: 'Not assigned';
                        $fulfillment = $row['fulfillment_source'] ?? 'none';
                        ?>
                        <tr class="<?php echo ($row['urgency'] === 'urgent') ? 'urgent-row' : ''; ?>">
                            <td class="small-cell"><?php echo htmlspecialchars($row['id']); ?></td>
                            <td><?php echo htmlspecialchars($row['recipient_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['blood_group']); ?></td>
                            <td class="wrap-cell"><?php echo htmlspecialchars($row['location']); ?></td>
                            <td class="small-cell"><?php echo htmlspecialchars($row['quantity']); ?></td>
                            <td><?php echo htmlspecialchars($row['urgency']); ?></td>
                            <td><span class="badge <?php echo $statusBadge; ?>"><?php echo htmlspecialchars($row['status']); ?></span></td>

                            <td>
                                <?php if($fulfillment === 'stock'){ ?>
                                    <span class="mini-badge mini-blue">From Stock</span>
                                <?php } elseif($fulfillment === 'donor'){ ?>
                                    <span class="mini-badge mini-green">Donor-Based</span>
                                <?php } else { ?>
                                    <span class="mini-badge mini-gray">Pending</span>
                                <?php } ?>
                            </td>

                            <td><?php echo htmlspecialchars($row['donor_name'] ?? 'Not assigned'); ?></td>
                            <td><?php echo htmlspecialchars($bankDisplay); ?></td>
                            <td class="wrap-cell"><?php echo htmlspecialchars($branchDisplay); ?></td>
                            <td class="date-cell"><?php echo htmlspecialchars($row['created_at']); ?></td>
                        </tr>
                    <?php } ?>
                </table>
            <?php } else { ?>
                <p>No requests found.</p>
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
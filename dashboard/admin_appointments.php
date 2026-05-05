<?php
session_start();
include '../config/db.php';
include '../functions/location_helper.php';
include '../functions/network_helper.php';
include '../functions/admin_notification_helper.php';

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin'){
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$notif_count = getAdminNotificationCount($conn);


$recipient_search = isset($_GET['recipient_search']) ? trim($_GET['recipient_search']) : "";
$donor_search = isset($_GET['donor_search']) ? trim($_GET['donor_search']) : "";
$blood_group = isset($_GET['blood_group']) ? trim($_GET['blood_group']) : "all";
$status_filter = isset($_GET['status']) ? trim($_GET['status']) : "all";
$appointment_type = isset($_GET['appointment_type']) ? trim($_GET['appointment_type']) : "all";
$location_search = isset($_GET['location_search']) ? trim($_GET['location_search']) : "";
$date_from = isset($_GET['date_from']) ? trim($_GET['date_from']) : "";
$date_to = isset($_GET['date_to']) ? trim($_GET['date_to']) : "";

$recipient_search_safe = mysqli_real_escape_string($conn, $recipient_search);
$donor_search_safe = mysqli_real_escape_string($conn, $donor_search);
$location_search_safe = mysqli_real_escape_string($conn, $location_search);

$sql = "
    SELECT 
        a.*,
        r.blood_group,
        r.quantity,
        r.urgency,
        u.name AS recipient_name,
        d.name AS donor_name,
        bb.name AS blood_bank_name,
        bb.institution_name,
        b.branch_name
    FROM appointments a
    LEFT JOIN blood_requests r ON a.request_id = r.id
    LEFT JOIN users u ON a.recipient_id = u.id
    LEFT JOIN users d ON a.donor_id = d.id
    LEFT JOIN users bb ON a.blood_bank_id = bb.id
    LEFT JOIN branches b ON a.branch_id = b.id
    WHERE 1=1
";

if($recipient_search_safe !== ""){
    $sql .= " AND u.name LIKE '%$recipient_search_safe%'";
}
if($donor_search_safe !== ""){
    $sql .= " AND d.name LIKE '%$donor_search_safe%'";
}
if($blood_group !== "all"){
    $group_safe = mysqli_real_escape_string($conn, $blood_group);
    $sql .= " AND r.blood_group = '$group_safe'";
}
if($status_filter !== "all"){
    $status_safe = mysqli_real_escape_string($conn, $status_filter);
    $sql .= " AND a.status = '$status_safe'";
}
if($appointment_type !== "all"){
    $type_safe = mysqli_real_escape_string($conn, $appointment_type);
    $sql .= " AND a.appointment_type = '$type_safe'";
}
if($location_search_safe !== ""){
    $sql .= " AND (
        a.appointment_location LIKE '%$location_search_safe%' OR
        a.appointment_address LIKE '%$location_search_safe%' OR
        a.appointment_city LIKE '%$location_search_safe%' OR
        a.appointment_zipcode LIKE '%$location_search_safe%'
    )";
}
if($date_from !== ""){
    $date_from_safe = mysqli_real_escape_string($conn, $date_from);
    $sql .= " AND DATE(a.appointment_date) >= '$date_from_safe'";
}
if($date_to !== ""){
    $date_to_safe = mysqli_real_escape_string($conn, $date_to);
    $sql .= " AND DATE(a.appointment_date) <= '$date_to_safe'";
}

$sql .= " ORDER BY a.appointment_date DESC";
$result = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Appointments</title>
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
            <a href="admin_appointments.php" class="active">Appointments</a>
            <a href="admin_branches.php">Branches</a>
            <a href="admin_stock_requests.php">Stock Requests</a>
            <a href="admin_activity_logs.php">Activity Logs</a>
            <a href="notifications.php">Notifications</a>
        </div>
    </div>
</div>

<div class="container">
    <div class="hero">
        <h1>Appointments Overview</h1>
        <p>Review request-based appointments with branch assignment and filter them by status, blood group, and appointment type.</p>
    </div>

    <div class="card filter-card-compact">
        <h3>Filter Appointments</h3>
        <form method="GET" class="filter-form">
            <div class="filter-grid-2">
                <div class="field">
                    <label>Recipient Name</label>
                    <input class="input" type="text" name="recipient_search" value="<?php echo htmlspecialchars($recipient_search); ?>" placeholder="Search recipient">
                </div>

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
                    <label>Status</label>
                    <select class="select" name="status">
                        <option value="all" <?php if($status_filter=='all') echo 'selected'; ?>>All</option>
                        <option value="scheduled" <?php if($status_filter=='scheduled') echo 'selected'; ?>>Scheduled</option>
                        <option value="confirmed" <?php if($status_filter=='confirmed') echo 'selected'; ?>>Confirmed</option>
                        <option value="declined" <?php if($status_filter=='declined') echo 'selected'; ?>>Declined</option>
                        <option value="completed" <?php if($status_filter=='completed') echo 'selected'; ?>>Completed</option>
                        <option value="cancelled" <?php if($status_filter=='cancelled') echo 'selected'; ?>>Cancelled</option>
                    </select>
                </div>

                <div class="field">
                    <label>Appointment Type</label>
                    <select class="select" name="appointment_type">
                        <option value="all" <?php if($appointment_type=='all') echo 'selected'; ?>>All</option>
                        <option value="request_based" <?php if($appointment_type=='request_based') echo 'selected'; ?>>Request Based</option>
                        <option value="stock_donation" <?php if($appointment_type=='stock_donation') echo 'selected'; ?>>Stock Donation</option>
                    </select>
                </div>

                <div class="field">
                    <label>Appointment Location</label>
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
                <a class="btn btn-secondary" href="admin_appointments.php">Reset</a>
            </div>
        </form>
    </div>

    <div class="card">
        <h3>Appointments List</h3>

        <div class="table-scroll-admin">
        <?php if(mysqli_num_rows($result) > 0){ ?>
            <table class="compact-admin-table">
                <tr>
                    <th class="small-cell">Appointment ID</th>
                    <th>Type</th>
                    <th class="small-cell">Request ID</th>
                    <th>Recipient</th>
                    <th>Donor</th>
                    <th>Blood Group</th>
                    <th class="small-cell">Quantity</th>
                    <th>Urgency</th>
                    <th class="wrap-cell">Blood Bank</th>
                    <th class="wrap-cell">Branch</th>
                    <th class="date-cell">Date</th>
                    <th class="wrap-cell">Location</th>
                    <th>Map</th>
                    <th>Status</th>
                </tr>

                <?php while($row = mysqli_fetch_assoc($result)){ ?>
                    <?php
                    $bloodBankDisplay = getBloodBankDisplayName($row['institution_name'] ?? '', $row['blood_bank_name'] ?? '');
                    $fullLocation = buildFullLocation(
                        $row['appointment_address'] ?? '',
                        $row['appointment_city'] ?? '',
                        $row['appointment_zipcode'] ?? '',
                        $row['appointment_location'] ?? ''
                    );
                    $mapUrl = buildMapUrl(
                        $row['appointment_address'] ?? '',
                        $row['appointment_city'] ?? '',
                        $row['appointment_zipcode'] ?? '',
                        $row['appointment_location'] ?? ''
                    );
                    $embedUrl = buildEmbedMapUrl(
                        $row['appointment_address'] ?? '',
                        $row['appointment_city'] ?? '',
                        $row['appointment_zipcode'] ?? '',
                        $row['appointment_location'] ?? ''
                    );
                    $mapId = "adminMap" . (int)$row['id'];
                    ?>
                    <tr>
                        <td class="small-cell"><?php echo htmlspecialchars($row['id']); ?></td>

                        <td>
                            <?php if(($row['appointment_type'] ?? '') === 'stock_donation'){ ?>
                                <span class="mini-badge mini-blue">Stock Donation</span>
                            <?php } else { ?>
                                <span class="mini-badge mini-green">Request Based</span>
                            <?php } ?>
                        </td>

                        <td class="small-cell"><?php echo htmlspecialchars($row['request_id'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($row['recipient_name'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($row['donor_name'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($row['blood_group'] ?? 'N/A'); ?></td>
                        <td class="small-cell"><?php echo htmlspecialchars($row['quantity'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($row['urgency'] ?? 'N/A'); ?></td>
                        <td class="wrap-cell"><?php echo htmlspecialchars($bloodBankDisplay); ?></td>
                        <td class="wrap-cell"><?php echo htmlspecialchars($row['branch_name'] ?? 'Not assigned'); ?></td>
                        <td class="date-cell"><?php echo htmlspecialchars($row['appointment_date']); ?></td>
                        <td class="wrap-cell"><?php echo htmlspecialchars($fullLocation); ?></td>

                        <td>
                            <?php if($embedUrl !== ''){ ?>
                                <div class="map-actions">
                                    <button type="button" class="map-toggle-btn" onclick="toggleMap('<?php echo $mapId; ?>', this)">Show Map</button>
                                    <a class="map-open-link" href="<?php echo htmlspecialchars($mapUrl); ?>" target="_blank">Open in Google Maps</a>
                                </div>
                                <div id="<?php echo $mapId; ?>" class="embedded-map-wrap"><iframe src="<?php echo htmlspecialchars($embedUrl); ?>" loading="lazy"></iframe></div>
                            <?php } else { ?>
                                ---
                            <?php } ?>
                        </td>

                        <td><?php echo htmlspecialchars($row['status']); ?></td>
                    </tr>
                <?php } ?>
            </table>
        <?php } else { ?>
            <p>No appointments found.</p>
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
        <div class="footer-bottom">© 2026 <?php echo htmlspecialchars(renderFooterTitle()); ?> • Final Year Project Prototype</div>
    </div>
</div>

<script>
function toggleMap(id, btn){
    const el = document.getElementById(id);
    const isOpen = el.style.display === 'block';

    document.querySelectorAll('.embedded-map-wrap').forEach(map => {
        map.style.display = 'none';
    });
    document.querySelectorAll('.map-toggle-btn').forEach(button => {
        button.textContent = 'Show Map';
    });

    if(!isOpen){
        el.style.display = 'block';
        btn.textContent = 'Hide Map';
    }
}
</script>

</body>
</html>
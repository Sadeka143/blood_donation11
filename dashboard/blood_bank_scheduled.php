<?php
session_start();
include '../config/db.php';
include '../functions/network_helper.php';
include '../functions/location_helper.php';

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'blood_bank'){
    header("Location: ../auth/login.php");
    exit();
}

$blood_bank_id = $_SESSION['user_id'];

/* notification count */
$notif_stmt = mysqli_prepare($conn, "SELECT COUNT(*) AS total FROM notifications WHERE user_id = ? AND is_read = 0");
mysqli_stmt_bind_param($notif_stmt, "i", $blood_bank_id);
mysqli_stmt_execute($notif_stmt);
$notif_result = mysqli_stmt_get_result($notif_stmt);
$notif_data = mysqli_fetch_assoc($notif_result);
$notif_count = $notif_data['total'] ?? 0;
mysqli_stmt_close($notif_stmt);

/* filter values */
$filter_urgency = trim($_GET['urgency'] ?? '');
$filter_branch = trim($_GET['branch'] ?? '');
$filter_keyword = trim($_GET['keyword'] ?? '');

$sql = "
    SELECT 
        a.id,
        a.request_id,
        a.appointment_date,
        a.appointment_location,
        a.appointment_address,
        a.appointment_city,
        a.appointment_zipcode,
        a.notes,
        a.status,
        r.blood_group,
        r.urgency,
        r.quantity,
        donor.name AS donor_name,
        recipient.name AS recipient_name,
        b.branch_name
    FROM appointments a
    JOIN blood_requests r ON a.request_id = r.id
    LEFT JOIN users donor ON a.donor_id = donor.id
    LEFT JOIN users recipient ON a.recipient_id = recipient.id
    LEFT JOIN branches b ON a.branch_id = b.id
    WHERE a.blood_bank_id = ? AND a.status = 'scheduled'
";

$params = [$blood_bank_id];
$types = "i";

if($filter_urgency !== ''){
    $sql .= " AND r.urgency = ?";
    $params[] = $filter_urgency;
    $types .= "s";
}

if($filter_branch !== ''){
    $sql .= " AND b.branch_name = ?";
    $params[] = $filter_branch;
    $types .= "s";
}

if($filter_keyword !== ''){
    $sql .= " AND (
        donor.name LIKE ?
        OR recipient.name LIKE ?
        OR r.blood_group LIKE ?
        OR a.appointment_location LIKE ?
        OR a.appointment_address LIKE ?
        OR a.appointment_city LIKE ?
        OR a.appointment_zipcode LIKE ?
    )";
    $kw = "%" . $filter_keyword . "%";
    for($i = 0; $i < 7; $i++){
        $params[] = $kw;
        $types .= "s";
    }
}

$sql .= " ORDER BY a.appointment_date DESC";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$appointments = [];

while($row = mysqli_fetch_assoc($result)){
    $res_stmt = mysqli_prepare($conn, "
        SELECT id, preferred_datetime, donor_reason, proposed_datetime, blood_bank_note, status
        FROM appointment_reschedule_requests
        WHERE appointment_id = ?
        ORDER BY id DESC
        LIMIT 1
    ");
    mysqli_stmt_bind_param($res_stmt, "i", $row['id']);
    mysqli_stmt_execute($res_stmt);
    $res_result = mysqli_stmt_get_result($res_stmt);
    $reschedule = mysqli_fetch_assoc($res_result);
    mysqli_stmt_close($res_stmt);

    $appointments[] = [
        'id' => $row['id'],
        'request_id' => $row['request_id'],
        'recipient_name' => $row['recipient_name'] ?? 'N/A',
        'donor_name' => $row['donor_name'] ?? 'N/A',
        'blood_group' => $row['blood_group'],
        'urgency' => $row['urgency'],
        'branch_name' => $row['branch_name'] ?? 'Not assigned',
        'date' => $row['appointment_date'],
        'location' => buildFullLocation(
            $row['appointment_address'] ?? '',
            $row['appointment_city'] ?? '',
            $row['appointment_zipcode'] ?? '',
            $row['appointment_location'] ?? ''
        ),
        'address' => $row['appointment_address'] ?? '',
        'city' => $row['appointment_city'] ?? '',
        'zipcode' => $row['appointment_zipcode'] ?? '',
        'fallback_location' => $row['appointment_location'] ?? '',
        'status' => $row['status'],
        'reschedule_id' => $reschedule['id'] ?? null,
        'reschedule_status' => $reschedule['status'] ?? '',
        'preferred_datetime' => $reschedule['preferred_datetime'] ?? '',
        'donor_reason' => $reschedule['donor_reason'] ?? '',
        'proposed_datetime' => $reschedule['proposed_datetime'] ?? '',
        'blood_bank_note' => $reschedule['blood_bank_note'] ?? ''
    ];
}
mysqli_stmt_close($stmt);

/* branch dropdown values */
$branch_list = mysqli_query($conn, "SELECT DISTINCT branch_name FROM branches WHERE is_active = 1 ORDER BY branch_name ASC");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Scheduled Appointments</title>
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
                <a href="notifications.php" class="notify" title="Notifications">🔔
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
            <a href="blood_bank_scheduled.php" class="active">Scheduled</a>
            <a href="blood_bank_confirmed.php">Confirmed</a>
            <a href="blood_bank_history.php">History</a>
            <a href="blood_bank_stock_requests.php">Stock Requests</a>
            <a href="notifications.php">Notifications</a>
        </div>
    </div>
</div>

<div class="container">
    <div class="hero">
        <h1>Scheduled Appointment List</h1>
        <p>Review scheduled request-based appointments, donor reschedule requests, and propose updated slots when needed.</p>
    </div>

    <?php if(isset($_GET['updated']) && $_GET['updated'] === '1'){ ?>
        <div class="alert alert-success">A new appointment slot has been proposed successfully.</div>
    <?php } ?>

    <div class="card">
        <h3>Filter Scheduled Appointments</h3>

        <form method="GET" class="filter-grid">
            <div class="filter-group">
                <label>Urgency</label>
                <select class="select" name="urgency">
                    <option value="">All Urgency</option>
                    <option value="urgent" <?php if($filter_urgency === 'urgent') echo 'selected'; ?>>Urgent</option>
                    <option value="normal" <?php if($filter_urgency === 'normal') echo 'selected'; ?>>Normal</option>
                </select>
            </div>

            <div class="filter-group">
                <label>Branch</label>
                <select class="select" name="branch">
                    <option value="">All Branches</option>
                    <?php while($branch = mysqli_fetch_assoc($branch_list)){ ?>
                        <option value="<?php echo htmlspecialchars($branch['branch_name']); ?>" <?php if($filter_branch === $branch['branch_name']) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($branch['branch_name']); ?>
                        </option>
                    <?php } ?>
                </select>
            </div>

            <div class="filter-group filter-group-wide">
                <label>Keyword</label>
                <input class="input" type="text" name="keyword" value="<?php echo htmlspecialchars($filter_keyword); ?>" placeholder="Search donor, recipient, group, location...">
            </div>

            <div class="filter-actions">
                <button class="btn btn-primary" type="submit">Apply</button>
                <a class="btn btn-secondary" href="blood_bank_scheduled.php">Reset</a>
            </div>
        </form>
    </div>

    <div class="card">
        <h3>Scheduled Appointment List</h3>

        <div class="table-scroll-admin">
            <?php if(!empty($appointments)){ ?>
                <table class="compact-admin-table">
                    <tr>
                        <th>Request ID</th>
                        <th>Recipient</th>
                        <th>Donor</th>
                        <th>Blood Group</th>
                        <th>Urgency</th>
                        <th>Branch</th>
                        <th>Date</th>
                        <th>Location</th>
                        <th>Map</th>
                        <th>Reschedule Preview</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>

                    <?php foreach($appointments as $row){ ?>
                        <?php
                        $mapUrl = buildMapUrl($row['address'], $row['city'], $row['zipcode'], $row['fallback_location']);
                        $embedUrl = buildEmbedMapUrl($row['address'], $row['city'], $row['zipcode'], $row['fallback_location']);
                        $mapId = "bankReqMap" . (int)$row['id'];
                        ?>
                        <tr class="<?php echo ($row['urgency'] === 'urgent') ? 'urgent-row' : ''; ?>">
                            <td><?php echo htmlspecialchars($row['request_id']); ?></td>
                            <td><?php echo htmlspecialchars($row['recipient_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['donor_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['blood_group']); ?></td>
                            <td>
                                <?php if($row['urgency'] === 'urgent'){ ?>
                                    <span class="urgency-badge urgency-urgent">Urgent</span>
                                <?php } else { ?>
                                    <span class="urgency-badge urgency-normal">Normal</span>
                                <?php } ?>
                            </td>
                            <td><?php echo htmlspecialchars($row['branch_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['date']); ?></td>
                            <td class="wrap-cell"><?php echo htmlspecialchars($row['location']); ?></td>
                            <td>
                                <?php if($embedUrl !== ''){ ?>
                                    <div class="map-actions">
                                        <button type="button" class="map-toggle-btn" onclick="toggleMap('<?php echo $mapId; ?>', this)">Show Map</button>
                                        <a class="map-open-link" href="<?php echo htmlspecialchars($mapUrl); ?>" target="_blank">Open in Google Maps</a>
                                    </div>
                                    <div id="<?php echo $mapId; ?>" class="embedded-map-wrap">
                                        <iframe src="<?php echo htmlspecialchars($embedUrl); ?>" loading="lazy"></iframe>
                                    </div>
                                <?php } else { ?>
                                    ---
                                <?php } ?>
                            </td>
                            <td>
                                <?php if($row['reschedule_status'] === 'requested'){ ?>
                                    <div class="reschedule-simple-box">
                                        <span class="mini-badge mini-orange">Requested</span>
                                        <div class="reschedule-date-text">Preferred: <?php echo htmlspecialchars($row['preferred_datetime']); ?></div>
                                        <div class="reason-box-simple"><?php echo nl2br(htmlspecialchars($row['donor_reason'])); ?></div>
                                    </div>
                                <?php } elseif($row['reschedule_status'] === 'resolved'){ ?>
                                    <div class="reschedule-simple-box">
                                        <span class="mini-badge mini-green">Updated</span>
                                        <?php if($row['proposed_datetime'] !== ''){ ?>
                                            <div class="reschedule-date-text">Proposed: <?php echo htmlspecialchars($row['proposed_datetime']); ?></div>
                                        <?php } ?>
                                        <?php if($row['blood_bank_note'] !== ''){ ?>
                                            <div class="reason-box-simple"><?php echo nl2br(htmlspecialchars($row['blood_bank_note'])); ?></div>
                                        <?php } ?>
                                    </div>
                                <?php } else { ?>
                                    <span class="small-muted">No reschedule request</span>
                                <?php } ?>
                            </td>
                            <td><span class="badge badge-pending">Scheduled</span></td>
                            <td>
                                <?php if($row['reschedule_status'] === 'requested'){ ?>
                                    <a class="action-pill action-pill-reschedule" href="propose_reschedule_slot.php?id=<?php echo (int)$row['id']; ?>">Propose New Slot</a>
                                <?php } else { ?>
                                    <span class="small-muted">---</span>
                                <?php } ?>
                            </td>
                        </tr>
                    <?php } ?>
                </table>
            <?php } else { ?>
                <p>No scheduled appointments found.</p>
            <?php } ?>
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

<script>
function toggleMap(id, btn){
    const el = document.getElementById(id);
    const isOpen = el.style.display === 'block';

    document.querySelectorAll('.embedded-map-wrap').forEach(map => map.style.display = 'none');
    document.querySelectorAll('.map-toggle-btn').forEach(button => button.textContent = 'Show Map');

    if(!isOpen){
        el.style.display = 'block';
        btn.textContent = 'Hide Map';
    }
}
</script>

</body>
</html>
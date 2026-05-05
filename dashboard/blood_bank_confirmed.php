<?php
session_start();
include '../config/db.php';
include '../functions/location_helper.php';
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

$recipient_search = isset($_GET['recipient_search']) ? trim($_GET['recipient_search']) : "";
$donor_search = isset($_GET['donor_search']) ? trim($_GET['donor_search']) : "";
$blood_group = isset($_GET['blood_group']) ? trim($_GET['blood_group']) : "all";
$location_search = isset($_GET['location_search']) ? trim($_GET['location_search']) : "";

$recipient_search_safe = mysqli_real_escape_string($conn, $recipient_search);
$donor_search_safe = mysqli_real_escape_string($conn, $donor_search);
$location_search_safe = mysqli_real_escape_string($conn, $location_search);

$confirmed_sql = "
    SELECT 
        a.*,
        r.blood_group,
        u.name AS recipient_name,
        d.name AS donor_name,
        b.branch_name
    FROM appointments a
    JOIN blood_requests r ON a.request_id = r.id
    JOIN users u ON a.recipient_id = u.id
    JOIN users d ON a.donor_id = d.id
    LEFT JOIN branches b ON a.branch_id = b.id
    WHERE a.blood_bank_id = $user_id AND a.status = 'confirmed'
";

if($recipient_search_safe !== ""){
    $confirmed_sql .= " AND u.name LIKE '%$recipient_search_safe%'";
}
if($donor_search_safe !== ""){
    $confirmed_sql .= " AND d.name LIKE '%$donor_search_safe%'";
}
if($blood_group !== "all"){
    $group_safe = mysqli_real_escape_string($conn, $blood_group);
    $confirmed_sql .= " AND r.blood_group = '$group_safe'";
}
if($location_search_safe !== ""){
    $confirmed_sql .= " AND (
        a.appointment_location LIKE '%$location_search_safe%' OR
        a.appointment_address LIKE '%$location_search_safe%' OR
        a.appointment_city LIKE '%$location_search_safe%' OR
        a.appointment_zipcode LIKE '%$location_search_safe%'
    )";
}

$confirmed_sql .= " ORDER BY a.appointment_date DESC";
$confirmed_result = mysqli_query($conn, $confirmed_sql);

$flashMessage = "";
$flashClass = "";

if(isset($_GET['result'])){
    if($_GET['result'] === 'completed'){
        $req = htmlspecialchars($_GET['request_id'] ?? '');
        $branch = htmlspecialchars($_GET['branch'] ?? '');
        $flashMessage = "Donation for request #{$req} was completed successfully and stock was updated at {$branch}.";
        $flashClass = "alert-success";
    } elseif($_GET['result'] === 'invalid'){
        $flashMessage = "This appointment is no longer available for completion.";
        $flashClass = "alert-error";
    } elseif($_GET['result'] === 'error'){
        $flashMessage = "Something went wrong while completing the donation.";
        $flashClass = "alert-error";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Confirmed Appointments</title>
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
            <a href="blood_bank_confirmed.php" class="active">Confirmed</a>
            <a href="blood_bank_history.php">History</a>
            <a href="blood_bank_stock_requests.php">Stock Requests</a>
            <a href="notifications.php">Notifications</a>
        </div>
    </div>
</div>

<div class="container">

    <div class="hero">
        <h1>Confirmed Appointments</h1>
        <p>View donor-confirmed appointments and complete the donation process once the donation is finished.</p>
    </div>

    <?php if($flashMessage != ""){ ?>
        <div class="alert <?php echo $flashClass; ?>"><?php echo $flashMessage; ?></div>
    <?php } ?>

    <div class="card filter-card-compact">
        <h3>Filter Confirmed Appointments</h3>
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
                    <label>Appointment Location</label>
                    <input class="input" type="text" name="location_search" value="<?php echo htmlspecialchars($location_search); ?>" placeholder="Search address / city / zipcode">
                </div>
            </div>
            <div class="filter-actions-row">
                <button class="btn btn-primary" type="submit">Apply</button>
                <a class="btn btn-secondary" href="blood_bank_confirmed.php">Reset</a>
            </div>
        </form>
    </div>

    <div class="card">
        <h3>Confirmed Appointment List</h3>
        <div class="table-scroll-admin">
        <?php if(mysqli_num_rows($confirmed_result) > 0){ ?>
            <table class="compact-admin-table">
                <tr>
                    <th class="small-cell">Request ID</th>
                    <th>Recipient</th>
                    <th>Donor</th>
                    <th>Blood Group</th>
                    <th class="wrap-cell">Branch</th>
                    <th class="date-cell">Date</th>
                    <th class="wrap-cell">Location</th>
                    <th>Map</th>
                    <th class="wrap-cell">Notes</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>

                <?php while($row = mysqli_fetch_assoc($confirmed_result)){ ?>
                    <?php
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
                    $mapId = "confirmedMap" . (int)$row['id'];
                    ?>
                    <tr>
                        <td class="small-cell"><?php echo htmlspecialchars($row['request_id']); ?></td>
                        <td><?php echo htmlspecialchars($row['recipient_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['donor_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['blood_group']); ?></td>
                        <td class="wrap-cell"><?php echo htmlspecialchars($row['branch_name'] ?? 'Not assigned'); ?></td>
                        <td class="date-cell"><?php echo htmlspecialchars($row['appointment_date']); ?></td>
                        <td class="wrap-cell"><?php echo htmlspecialchars($fullLocation); ?></td>

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

                        <td class="wrap-cell"><?php echo htmlspecialchars($row['notes'] ?? ''); ?></td>
                        <td><span class="badge badge-accepted"><?php echo htmlspecialchars($row['status']); ?></span></td>
                        <td>
                            <a class="btn btn-primary btn-compact" href="complete_donation.php?id=<?php echo $row['id']; ?>" onclick="return confirm('Mark this donation as completed?');">
                                Complete Donation
                            </a>
                        </td>
                    </tr>
                <?php } ?>
            </table>
        <?php } else { ?>
            <p>No confirmed appointments found.</p>
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
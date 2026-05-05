<?php
# ==================================
# Recipient appointments controller
# ==================================
session_start();
include '../config/db.php';
include '../functions/location_helper.php';
include '../functions/network_helper.php';

# --------------------------------
# Secure access: only recipient role
# --------------------------------
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'recipient'){
    header("Location: ../auth/login.php");
    exit();
}

$recipient_id = $_SESSION['user_id'];

# ------------------------------------
# Notification badge count for topbar
# ------------------------------------
$notif_stmt = mysqli_prepare($conn, "SELECT COUNT(*) AS total FROM notifications WHERE user_id = ? AND is_read = 0");
mysqli_stmt_bind_param($notif_stmt, "i", $recipient_id);
mysqli_stmt_execute($notif_stmt);
$notif_result = mysqli_stmt_get_result($notif_stmt);
$notif_data = mysqli_fetch_assoc($notif_result);
$notif_count = $notif_data['total'];
mysqli_stmt_close($notif_stmt);

# -----------------------------------------
# Recipient appointment list with donor info
# -----------------------------------------
$appointment_stmt = mysqli_prepare($conn, "
    SELECT a.*, r.blood_group, r.quantity, r.urgency, d.name AS donor_name
    FROM appointments a
    JOIN blood_requests r ON a.request_id = r.id
    LEFT JOIN users d ON a.donor_id = d.id
    WHERE a.recipient_id = ?
    ORDER BY a.appointment_date DESC
");
mysqli_stmt_bind_param($appointment_stmt, "i", $recipient_id);
mysqli_stmt_execute($appointment_stmt);
$appointment_result = mysqli_stmt_get_result($appointment_stmt);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Recipient Appointments</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>

<!-- =========================
     Full-width top navigation
========================= -->
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
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?> (Recipient)</span>
                <a class="btn btn-logout" href="../auth/logout.php">Logout</a>
            </div>
        </div>

        <div class="topbar-menu">
            <a href="recipient_dashboard.php">Dashboard</a>
            <a href="create_request.php">Create Request</a>
            <a href="recipient_requests.php">My Requests</a>
            <a href="recipient_appointments.php" class="active">Appointments</a>
            <a href="recipient_profile.php">Profile</a>
            <a href="notifications.php">Notifications</a>
        </div>
    </div>
</div>

<div class="container">

    <!-- =========================
         Page hero section
    ========================= -->
    <div class="hero">
        <h1>My Appointments</h1>
        <p>View all donation appointment details linked to your blood requests.</p>
    </div>

    <!-- =========================
         Appointment table section
    ========================= -->
    <div class="card">
        <h3>Appointment List</h3>

        <div class="table-scroll">
            <?php if(mysqli_num_rows($appointment_result) > 0){ ?>
                <table>
                    <tr>
                        <th>Request ID</th>
                        <th>Donor</th>
                        <th>Blood Group</th>
                        <th>Quantity</th>
                        <th>Urgency</th>
                        <th>Appointment Date</th>
                        <th>Location</th>
                        <th>Map</th>
                        <th>Notes</th>
                        <th>Status</th>
                    </tr>

                    <?php while($row = mysqli_fetch_assoc($appointment_result)){ ?>
                        <?php
                        # Appointment status badge style
                        $badgeClass = "badge-accepted";
                        if($row['status'] == 'scheduled') $badgeClass = "badge-pending";
                        if($row['status'] == 'completed') $badgeClass = "badge-completed";
                        if($row['status'] == 'declined') $badgeClass = "badge-pending";

                        # Full location for appointment
                        $fullLocation = buildFullLocation(
                            $row['appointment_address'] ?? '',
                            $row['appointment_city'] ?? '',
                            $row['appointment_zipcode'] ?? '',
                            $row['appointment_location'] ?? ''
                        );

                        # Google maps data
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

                        $mapId = "recipientMap" . (int)$row['id'];
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['request_id']); ?></td>
                            <td><?php echo htmlspecialchars($row['donor_name'] ?? 'Not assigned'); ?></td>
                            <td><?php echo htmlspecialchars($row['blood_group']); ?></td>
                            <td><?php echo htmlspecialchars($row['quantity']); ?></td>
                            <td><?php echo htmlspecialchars($row['urgency']); ?></td>
                            <td><?php echo htmlspecialchars($row['appointment_date']); ?></td>
                            <td class="location-text"><?php echo htmlspecialchars($fullLocation); ?></td>

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

                            <td class="notes-text"><?php echo htmlspecialchars($row['notes'] ?? ''); ?></td>
                            <td><span class="badge <?php echo $badgeClass; ?>"><?php echo htmlspecialchars($row['status']); ?></span></td>
                        </tr>
                    <?php } ?>
                </table>
            <?php } else { ?>
                <p>No appointments available.</p>
            <?php } ?>
            <?php mysqli_stmt_close($appointment_stmt); ?>
        </div>
    </div>

</div>

<!-- =========================
     Full-width footer section
========================= -->
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

<!-- =========================
     Embedded map toggle script
========================= -->
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
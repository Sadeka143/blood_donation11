<?php
# ==================================
# Recipient status page controller
# ==================================
session_start();
include '../config/db.php';
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
$notif_count = $notif_data['total'] ?? 0;
mysqli_stmt_close($notif_stmt);

# ----------------------------------------
# Request status query for legacy status page
# ----------------------------------------
$sql = "
    SELECT 
        r.*,
        d.name AS donor_name,
        bb.name AS blood_bank_name,
        bb.institution_name,
        a.appointment_date,
        a.appointment_location,
        a.notes AS appointment_notes,
        a.status AS appointment_status
    FROM blood_requests r
    LEFT JOIN users d ON r.matched_donor_id = d.id
    LEFT JOIN users bb ON r.assigned_blood_bank_id = bb.id
    LEFT JOIN appointments a ON r.id = a.request_id
    WHERE r.recipient_id = ?
    ORDER BY r.created_at DESC
";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $recipient_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Recipient Request Status</title>
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
            <a href="recipient_requests.php" class="active">My Requests</a>
            <a href="recipient_appointments.php">Appointments</a>
            <a href="recipient_profile.php">Profile</a>
            <a href="notifications.php">Notifications</a>
        </div>
    </div>
</div>

<div class="container">

    <!-- =========================
         Hero / heading section
    ========================= -->
    <div class="hero">
        <h1>Request Status</h1>
        <p>Review request progress, donor assignment, blood bank decision, appointments, and rejection reason if available.</p>
    </div>

    <div class="card">
        <h3>My Blood Requests</h3>

        <div class="table-scroll">
            <?php if(mysqli_num_rows($result) > 0){ ?>
                <table>
                    <tr>
                        <th>Request ID</th>
                        <th>Blood Group</th>
                        <th>Location</th>
                        <th>Quantity</th>
                        <th>Urgency</th>
                        <th>Status</th>
                        <th>Matched Donor</th>
                        <th>Blood Bank</th>
                        <th>Appointment Date</th>
                        <th>Appointment Location</th>
                        <th>Appointment Status</th>
                        <th>Rejection Reason</th>
                        <th>Requested On</th>
                    </tr>

                    <?php while($row = mysqli_fetch_assoc($result)){ ?>
                        <?php
                        $badgeClass = "badge-pending";
                        if(in_array($row['status'], ['approved','matched','scheduled'])) $badgeClass = "badge-accepted";
                        if($row['status'] == 'completed') $badgeClass = "badge-completed";
                        if(in_array($row['status'], ['rejected','cancelled'])) $badgeClass = "badge-pending";

                        $bloodBankDisplay = $row['institution_name'] ?: $row['blood_bank_name'] ?: 'N/A';
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['id']); ?></td>
                            <td><?php echo htmlspecialchars($row['blood_group']); ?></td>
                            <td><?php echo htmlspecialchars($row['location'] ?? ""); ?></td>
                            <td><?php echo htmlspecialchars($row['quantity']); ?></td>
                            <td><?php echo htmlspecialchars($row['urgency']); ?></td>
                            <td><span class="badge <?php echo $badgeClass; ?>"><?php echo htmlspecialchars($row['status']); ?></span></td>
                            <td><?php echo htmlspecialchars($row['donor_name'] ?? 'Not assigned'); ?></td>
                            <td><?php echo htmlspecialchars($bloodBankDisplay); ?></td>
                            <td><?php echo htmlspecialchars($row['appointment_date'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($row['appointment_location'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($row['appointment_status'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($row['rejection_reason'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($row['created_at']); ?></td>
                        </tr>
                    <?php } ?>
                </table>
            <?php } else { ?>
                <p>No requests yet.</p>
            <?php } ?>
            <?php mysqli_stmt_close($stmt); ?>
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

</body>
</html>
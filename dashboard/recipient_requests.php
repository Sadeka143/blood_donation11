<?php
# Recipient request tracking controller
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
$notif_count = $notif_data['total'];
mysqli_stmt_close($notif_stmt);

# ------------------------------------------------------
# Request list query with donor, blood bank, appointment
# ------------------------------------------------------
$sql = "
    SELECT 
        r.*,
        d.name AS donor_name,
        bb.name AS blood_bank_name,
        bb.institution_name,
        b.branch_name,
        a.appointment_date,
        a.appointment_location,
        a.notes AS appointment_notes,
        a.status AS appointment_status
    FROM blood_requests r
    LEFT JOIN users d ON r.matched_donor_id = d.id
    LEFT JOIN users bb ON r.assigned_blood_bank_id = bb.id
    LEFT JOIN branches b ON r.fulfilled_branch_id = b.id
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
    <title>My Requests</title>
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

    <div class="hero">
        <h1>My Blood Requests</h1>
        <p>Track stock-based fulfillment, donor matching, and branch-based scheduling for all your requests.</p>
    </div>
<!-- =========================
         Recipient request table
    ========================= -->
    <div class="card">
        <h3>Request List</h3>

        <?php if(mysqli_num_rows($result) > 0){ ?>
            <div class="table-scroll-admin">
                <table class="compact-admin-table">
                    <tr>
                        <th class="small-cell">Request ID</th>
                        <th>Blood Group</th>
                        <th class="wrap-cell">Location</th>
                        <th class="small-cell">Quantity</th>
                        <th>Urgency</th>
                        <th>Status</th>
                        <th>Fulfillment</th>
                        <th class="wrap-cell">Branch</th>
                        <th>Matched Donor</th>
                        <th class="date-cell">Appointment Date</th>
                        <th class="wrap-cell">Appointment Location</th>
                        <th>Appointment Status</th>
                        <th class="date-cell">Requested On</th>
                    </tr>

                    <?php while($row = mysqli_fetch_assoc($result)){ ?>
                        <?php
                        # Badge styling logic for request status
                        $badgeClass = "badge-pending";
                        if(in_array($row['status'], ['approved','matched','scheduled','confirmed'])) $badgeClass = "badge-accepted";
                        if($row['status'] == 'completed') $badgeClass = "badge-completed";
                        # Blood bank display name
                        $bloodBankDisplay = $row['institution_name'] ?: $row['blood_bank_name'] ?: getBloodBankWelcomeLabel();
                        $branchDisplay = $row['branch_name'] ?: 'Not assigned';
                        $fulfillmentSource = $row['fulfillment_source'] ?? 'none';
                        ?>
                        <tr>
                            <td class="small-cell"><?php echo htmlspecialchars($row['id']); ?></td>
                            <td><?php echo htmlspecialchars($row['blood_group']); ?></td>
                            <td class="wrap-cell"><?php echo htmlspecialchars($row['location'] ?? ""); ?></td>
                            <td class="small-cell"><?php echo htmlspecialchars($row['quantity']); ?></td>
                            <td><?php echo htmlspecialchars($row['urgency']); ?></td>
                            <td><span class="badge <?php echo $badgeClass; ?>"><?php echo htmlspecialchars($row['status']); ?></span></td>

                            <td>
                                <?php if($fulfillmentSource === 'stock'){ ?>
                                    <span class="mini-badge mini-blue">Fulfilled From Stock</span>
                                <?php } elseif($fulfillmentSource === 'donor'){ ?>
                                    <span class="mini-badge mini-green">Donor-Based Fulfillment</span>
                                <?php } else { ?>
                                    <span class="mini-badge mini-gray">Under Review</span>
                                <?php } ?>
                            </td>

                            <td class="wrap-cell">
                                <?php echo htmlspecialchars($branchDisplay); ?><br>
                                <span class="small-muted"><?php echo htmlspecialchars($bloodBankDisplay); ?></span>
                            </td>

                            <td><?php echo htmlspecialchars($row['donor_name'] ?? 'Not assigned'); ?></td>
                            <td class="date-cell"><?php echo htmlspecialchars($row['appointment_date'] ?? ''); ?></td>
                            <td class="wrap-cell"><?php echo htmlspecialchars($row['appointment_location'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($row['appointment_status'] ?? ''); ?></td>
                            <td class="date-cell"><?php echo htmlspecialchars($row['created_at']); ?></td>
                        </tr>
                    <?php } ?>
                </table>
            </div>
        <?php } else { ?>
            <p>No requests yet.</p>
        <?php } ?>

        <?php mysqli_stmt_close($stmt); ?>
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

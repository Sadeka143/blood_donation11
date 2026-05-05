<?php
session_start();
include '../config/db.php';
include '../functions/network_helper.php';
include '../functions/eligibility_check.php';
include '../functions/urgency_helper.php';

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'donor'){
    header("Location: ../auth/login.php");
    exit();
}

$donor_id = $_SESSION['user_id'];
$eligibility = syncDonorAvailabilityStatus($conn, $donor_id);

/* Notification count */
$notif_stmt = mysqli_prepare($conn, "SELECT COUNT(*) AS total FROM notifications WHERE user_id = ? AND is_read = 0");
mysqli_stmt_bind_param($notif_stmt, "i", $donor_id);
mysqli_stmt_execute($notif_stmt);
$notif_result = mysqli_stmt_get_result($notif_stmt);
$notif_data = mysqli_fetch_assoc($notif_result);
$notif_count = $notif_data['total'];
mysqli_stmt_close($notif_stmt);

/* Interested requests with both donor-interest status and request status */
$sql = "
    SELECT 
        di.id AS interest_id,
        di.status AS interest_status,
        di.created_at AS interested_at,
        r.id AS request_id,
        r.blood_group,
        r.address_line,
        r.city,
        r.zipcode,
        r.location,
        r.quantity,
        r.urgency,
        r.status AS request_status,
        u.name AS recipient_name,
        bb.name AS blood_bank_name,
        bb.institution_name
    FROM donor_interests di
    JOIN blood_requests r ON di.request_id = r.id
    JOIN users u ON r.recipient_id = u.id
    LEFT JOIN users bb ON r.assigned_blood_bank_id = bb.id
    WHERE di.donor_id = ?
    ORDER BY di.created_at DESC
";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $donor_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Interested Requests</title>
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
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?> (Donor)</span>
                <a class="btn btn-logout" href="../auth/logout.php">Logout</a>
            </div>
        </div>

        <div class="topbar-menu">
            <a href="donor_dashboard.php">Dashboard</a>
            <a href="donor_compatible_requests.php">Compatible Requests</a>
            <a href="donor_interested_requests.php" class="active">Interested Requests</a>
            <a href="donate_to_stock.php">Donate to Stock</a>
            <a href="donor_appointments.php">Appointments</a>
            <a href="donor_history.php">History</a>
            <a href="donor_profile.php">Profile</a>
            <a href="notifications.php">Notifications</a>
        </div>
    </div>
</div>

<div class="container">
    <div class="hero">
        <h1>Interested Requests</h1>
        <p>Review the requests you responded to and track both your interest status and the request workflow.</p>
    </div>

    <div class="card">
        <h3>Interested Request List</h3>

        <div class="table-scroll-admin">
            <?php if(mysqli_num_rows($result) > 0){ ?>
                <table class="compact-admin-table">
                    <tr>
                        <th class="small-cell">Request ID</th>
                        <th>Recipient</th>
                        <th>Blood Group</th>
                        <th class="wrap-cell">Address</th>
                        <th>City</th>
                        <th>Zipcode</th>
                        <th class="small-cell">Quantity</th>
                        <th>Urgency</th>
                        <th>Blood Bank</th>
                        <th>Interest Status</th>
                        <th>Request Status</th>
                        <th class="date-cell">Interested At</th>
                    </tr>

                    <?php while($row = mysqli_fetch_assoc($result)){ ?>
                        <?php
                        $interestBadge = "badge-pending";
                        if($row['interest_status'] === 'selected') $interestBadge = "badge-accepted";
                        if($row['interest_status'] === 'rejected') $interestBadge = "badge-pending";

                        $requestBadge = "badge-pending";
                        if(in_array($row['request_status'], ['approved','matched','scheduled'])) $requestBadge = "badge-accepted";
                        if(in_array($row['request_status'], ['confirmed','completed'])) $requestBadge = "badge-completed";

                        $bloodBankDisplay = $row['institution_name'] ?: $row['blood_bank_name'] ?: 'Not Assigned';
                        ?>
                        <tr class="<?php echo ($row['urgency'] === 'urgent') ? 'urgent-row' : ''; ?>">
                            <td class="small-cell"><?php echo htmlspecialchars($row['request_id']); ?></td>
                            <td><?php echo htmlspecialchars($row['recipient_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['blood_group']); ?></td>
                            <td class="wrap-cell"><?php echo htmlspecialchars($row['address_line'] ?? $row['location'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($row['city'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($row['zipcode'] ?? ''); ?></td>
                            <td class="small-cell"><?php echo htmlspecialchars($row['quantity']); ?></td>
                            <td><span class="<?php echo getUrgencyBadgeClass($row['urgency']); ?>"><?php echo getUrgencyLabel($row['urgency']); ?></span></td>
                            <td><?php echo htmlspecialchars($bloodBankDisplay); ?></td>
                            <td><span class="badge <?php echo $interestBadge; ?>"><?php echo htmlspecialchars($row['interest_status']); ?></span></td>
                            <td><span class="badge <?php echo $requestBadge; ?>"><?php echo htmlspecialchars($row['request_status']); ?></span></td>
                            <td class="date-cell"><?php echo htmlspecialchars($row['interested_at']); ?></td>
                        </tr>
                    <?php } ?>
                </table>
            <?php } else { ?>
                <p>You have not expressed interest in any request yet.</p>
            <?php } ?>

            <?php mysqli_stmt_close($stmt); ?>
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
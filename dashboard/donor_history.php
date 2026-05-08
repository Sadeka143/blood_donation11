<?php
session_start();
include '../config/db.php';
include '../functions/network_helper.php';
include '../functions/eligibility_check.php';

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'donor'){
    header("Location: ../auth/login.php");
    exit();
}

$donor_id = $_SESSION['user_id'];
$eligibility = syncDonorAvailabilityStatus($conn, $donor_id);

$notif_stmt = mysqli_prepare($conn, "SELECT COUNT(*) AS total FROM notifications WHERE user_id = ? AND is_read = 0");
mysqli_stmt_bind_param($notif_stmt, "i", $donor_id);
mysqli_stmt_execute($notif_stmt);
$notif_result = mysqli_stmt_get_result($notif_stmt);
$notif_data = mysqli_fetch_assoc($notif_result);
$notif_count = $notif_data['total'];
mysqli_stmt_close($notif_stmt);

$sql = "
    SELECT 
        d.id AS donation_id,
        d.request_id,
        d.donation_type,
        d.branch_id,
        d.donation_date,
        d.scheduled_date,
        d.completed_at,
        d.notes,
        d.status AS donation_status,
        COALESCE(r.blood_group, donor.blood_group) AS blood_group,
        CASE 
            WHEN d.donation_type = 'stock_donation' THEN 'Branch Stock Donation'
            ELSE r.location
        END AS location,
        CASE 
            WHEN d.donation_type = 'stock_donation' THEN 1
            ELSE r.quantity
        END AS quantity,
        r.urgency,
        b.branch_name,
        bb.name AS blood_bank_name,
        bb.institution_name
    FROM donations d
    JOIN users donor ON d.donor_id = donor.id
    LEFT JOIN blood_requests r ON d.request_id = r.id
    LEFT JOIN branches b ON d.branch_id = b.id
    LEFT JOIN users bb ON d.blood_bank_id = bb.id
    WHERE d.donor_id = ?
    ORDER BY d.id DESC
";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $donor_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Donation History</title>
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
            <a href="donor_interested_requests.php">Interested Requests</a>
            <a href="donate_to_stock.php">Donate to Stock</a>
            <a href="donor_appointments.php">Appointments</a>
            <a href="donor_history.php" class="active">History</a>
            <a href="donor_profile.php">Profile</a>
            <a href="notifications.php">Notifications</a>
        </div>
    </div>
</div>

<div class="container">
    <div class="hero">
        <h1>Donation History</h1>
        <p>Review your completed donation records, donation type, assigned branch, and blood bank coordination details.</p>
    </div>

    <div class="card">
        <h3>My Donation History</h3>

        <?php if(mysqli_num_rows($result) > 0){ ?>
            <div class="table-scroll-admin">
                <table class="compact-admin-table">
                    <tr>
                        <th class="small-cell">Donation ID</th>
                        <th class="small-cell">Request ID</th>
                        <th>Type</th>
                        <th>Blood Group</th>
                        <th class="wrap-cell">Branch</th>
                        <th>Blood Bank</th>
                        <th class="wrap-cell">Request Location</th>
                        <th class="small-cell">Quantity</th>
                        <th>Urgency</th>
                        <th class="date-cell">Scheduled Date</th>
                        <th class="date-cell">Completed At</th>
                        <th class="date-cell">Donation Date</th>
                        <th class="wrap-cell">Notes</th>
                        <th>Status</th>
                    </tr>

                    <?php while($row = mysqli_fetch_assoc($result)){ ?>
                        <?php
                        $badgeClass = ($row['donation_status'] == 'confirmed') ? "badge-completed" : "badge-pending";
                        $bankDisplay = $row['institution_name'] ?: $row['blood_bank_name'] ?: 'N/A';
                        ?>
                        <tr class="<?php echo (($row['urgency'] ?? '') === 'urgent') ? 'urgent-row' : ''; ?>">
                            <td class="small-cell"><?php echo htmlspecialchars($row['donation_id']); ?></td>
                            <td class="small-cell"><?php echo htmlspecialchars($row['request_id'] ?? 'N/A'); ?></td>
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
                            <td class="wrap-cell"><?php echo htmlspecialchars($row['location'] ?? 'Branch Stock Donation'); ?></td>
                            <td class="small-cell"><?php echo htmlspecialchars($row['quantity'] ?? 'N/A'); ?></td>
                            <td>
                                <?php if(!empty($row['urgency'])){ ?>
                                    <?php if($row['urgency'] === 'urgent'){ ?>
                                        <span class="urgency-badge urgency-urgent">Urgent</span>
                                    <?php } else { ?>
                                        <span class="urgency-badge urgency-normal">Normal</span>
                                    <?php } ?>
                                <?php } else { echo '---'; } ?>
                            </td>
                            <td class="date-cell"><?php echo htmlspecialchars($row['scheduled_date'] ?? ''); ?></td>
                            <td class="date-cell"><?php echo htmlspecialchars($row['completed_at'] ?? ''); ?></td>
                            <td class="date-cell"><?php echo htmlspecialchars($row['donation_date'] ?? ''); ?></td>
                            <td class="wrap-cell"><?php echo htmlspecialchars($row['notes'] ?? ''); ?></td>
                            <td><span class="badge <?php echo $badgeClass; ?>"><?php echo htmlspecialchars($row['donation_status']); ?></span></td>
                        </tr>
                    <?php } ?>
                </table>
            </div>
        <?php } else { ?>
            <p>No donation history available yet.</p>
        <?php } ?>
    </div>
</div>

<div class="site-footer site-footer-full">
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

</body>
</html>
<?php
session_start();
include '../config/db.php';
include '../functions/blood_compatibility.php';
include '../functions/urgency_helper.php';
include '../functions/eligibility_check.php';

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'donor'){
    header("Location: ../auth/login.php");
    exit();
}

$donor_id = $_SESSION['user_id'];

$eligibility = syncDonorAvailabilityStatus($conn, $donor_id);

$donor_stmt = mysqli_prepare($conn, "
    SELECT blood_group, city, zipcode, availability
    FROM users
    WHERE id = ?
");
mysqli_stmt_bind_param($donor_stmt, "i", $donor_id);
mysqli_stmt_execute($donor_stmt);
$donor_result = mysqli_stmt_get_result($donor_stmt);
$donor = mysqli_fetch_assoc($donor_result);
mysqli_stmt_close($donor_stmt);

$compatible_groups = getCompatibleRecipientGroups($donor['blood_group'] ?? '');

$notif_stmt = mysqli_prepare($conn, "SELECT COUNT(*) AS total FROM notifications WHERE user_id = ? AND is_read = 0");
mysqli_stmt_bind_param($notif_stmt, "i", $donor_id);
mysqli_stmt_execute($notif_stmt);
$notif_result = mysqli_stmt_get_result($notif_stmt);
$notif_data = mysqli_fetch_assoc($notif_result);
$notif_count = $notif_data['total'];
mysqli_stmt_close($notif_stmt);

$requests = [];

if(!empty($compatible_groups)){
    $escaped_groups = array_map(function($group) use ($conn){
        return "'" . mysqli_real_escape_string($conn, $group) . "'";
    }, $compatible_groups);

    $group_list = implode(',', $escaped_groups);

    $sql = "
        SELECT r.*, u.name AS recipient_name
        FROM blood_requests r
        JOIN users u ON r.recipient_id = u.id
        WHERE r.status = 'approved'
        AND r.blood_group IN ($group_list)
        ORDER BY " . getUrgencyOrderSql('r.urgency') . ", r.created_at DESC
    ";

    $result = mysqli_query($conn, $sql);

    while($row = mysqli_fetch_assoc($result)){
        $matchHint = "Other Area";
        $hintClass = "badge-pending";

        $requestCity = strtolower(trim((string)($row['city'] ?? '')));
        $requestZip = strtolower(trim((string)($row['zipcode'] ?? '')));
        $donorCity = strtolower(trim((string)($donor['city'] ?? '')));
        $donorZip = strtolower(trim((string)($donor['zipcode'] ?? '')));

        if($requestZip !== '' && $donorZip !== '' && $requestZip === $donorZip){
            $matchHint = "Same Zipcode";
            $hintClass = "badge-completed";
        } elseif($requestCity !== '' && $donorCity !== '' && $requestCity === $donorCity){
            $matchHint = "Same City";
            $hintClass = "badge-accepted";
        }

        $row['match_hint'] = $matchHint;
        $row['hint_class'] = $hintClass;
        $requests[] = $row;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Compatible Requests</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>

<div class="topbar topbar-full">
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
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?> (Donor)</span>
                <a class="btn btn-logout" href="../auth/logout.php">Logout</a>
            </div>
        </div>

        <div class="topbar-menu">
            <a href="donor_dashboard.php">Dashboard</a>
            <a href="donor_compatible_requests.php" class="active">Compatible Requests</a>
            <a href="donor_interested_requests.php">Interested Requests</a>
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
        <h1>Compatible Requests</h1>
        <p>Urgent compatible requests are prioritized at the top so donors can respond faster in emergency situations.</p>
    </div>

    <?php if(!$eligibility['eligible']) { ?>
        <div class="alert alert-error donor-lock-alert">
            You are temporarily unavailable for new donation actions.
            <?php if(!empty($eligibility['reason'])) { ?>
                <br><?php echo htmlspecialchars($eligibility['reason']); ?>
            <?php } ?>
            <?php if(!empty($eligibility['next_eligible_date'])) { ?>
                <br><strong>Next Eligible Date:</strong> <?php echo htmlspecialchars($eligibility['next_eligible_date']); ?>
            <?php } ?>
        </div>
    <?php } ?>

    <div class="card">
        <h3>Compatible Request List</h3>

        <?php if(!empty($requests)){ ?>
            <div class="table-scroll">
                <table>
                    <tr>
                        <th>ID</th>
                        <th>Recipient</th>
                        <th>Blood Group</th>
                        <th>Address</th>
                        <th>City</th>
                        <th>Zipcode</th>
                        <th>Match Hint</th>
                        <th>Quantity</th>
                        <th>Urgency</th>
                        <th>Action</th>
                    </tr>

                    <?php foreach($requests as $r){ ?>
                        <?php
                        $check_stmt = mysqli_prepare($conn, "SELECT id FROM donor_interests WHERE donor_id = ? AND request_id = ?");
                        mysqli_stmt_bind_param($check_stmt, "ii", $donor_id, $r['id']);
                        mysqli_stmt_execute($check_stmt);
                        mysqli_stmt_store_result($check_stmt);
                        $already = mysqli_stmt_num_rows($check_stmt) > 0;
                        mysqli_stmt_close($check_stmt);

                        $isUrgent = ($r['urgency'] === 'urgent');
                        ?>
                        <tr class="<?php echo $isUrgent ? 'urgent-row' : ''; ?>">
                            <td><?php echo htmlspecialchars($r['id']); ?></td>
                            <td><?php echo htmlspecialchars($r['recipient_name']); ?></td>
                            <td><?php echo htmlspecialchars($r['blood_group']); ?></td>
                            <td><?php echo htmlspecialchars($r['address_line'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($r['city'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($r['zipcode'] ?? ''); ?></td>
                            <td><span class="badge <?php echo htmlspecialchars($r['hint_class']); ?>"><?php echo htmlspecialchars($r['match_hint']); ?></span></td>
                            <td><?php echo htmlspecialchars($r['quantity']); ?></td>
                            <td><span class="<?php echo getUrgencyBadgeClass($r['urgency']); ?>"><?php echo getUrgencyLabel($r['urgency']); ?></span></td>

                            <td>
                                <?php if($already){ ?>
                                    <span class="badge badge-accepted">Interested</span>
                                <?php } elseif(!$eligibility['eligible']) { ?>
                                    <span class="badge badge-pending">Unavailable</span>
                                <?php } else { ?>
                                    <a class="btn btn-primary"
                                       href="express_interest.php?id=<?php echo $r['id']; ?>"
                                       onclick="return confirm('Express interest for this request?');">
                                       Express Interest
                                    </a>
                                <?php } ?>
                            </td>
                        </tr>
                    <?php } ?>
                </table>
            </div>
        <?php } else { ?>
            <p>No compatible requests available.</p>
        <?php } ?>
    </div>
</div>

<div class="site-footer site-footer-full">
    <div class="container">
        <div class="footer-grid">
            <div class="footer-col">
                <h4>About Us</h4>
                <p>Blood Donation Management System is a final year project prototype designed to simulate donor, recipient, blood bank, branch coordination, stock monitoring, donor matching, and appointment management.</p>
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
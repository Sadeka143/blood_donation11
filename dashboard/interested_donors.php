<?php
session_start();
include '../config/db.php';
include '../functions/eligibility_check.php';
include '../functions/matching_helper.php';

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'blood_bank'){
    header("Location: ../auth/login.php");
    exit();
}

if(!isset($_GET['id'])){
    header("Location: blood_bank_approved.php");
    exit();
}

$request_id = (int) $_GET['id'];
$blood_bank_id = $_SESSION['user_id'];
$display_name = !empty($_SESSION['institution_name']) ? $_SESSION['institution_name'] : $_SESSION['name'];

/* notification count */
$notif_stmt = mysqli_prepare($conn, "SELECT COUNT(*) AS total FROM notifications WHERE user_id = ? AND is_read = 0");
mysqli_stmt_bind_param($notif_stmt, "i", $blood_bank_id);
mysqli_stmt_execute($notif_stmt);
$notif_result = mysqli_stmt_get_result($notif_stmt);
$notif_data = mysqli_fetch_assoc($notif_result);
$notif_count = (int)($notif_data['total'] ?? 0);
mysqli_stmt_close($notif_stmt);

/* request details */
$request_stmt = mysqli_prepare($conn, "
    SELECT r.*, u.name AS recipient_name
    FROM blood_requests r
    JOIN users u ON r.recipient_id = u.id
    WHERE r.id = ?
");
mysqli_stmt_bind_param($request_stmt, "i", $request_id);
mysqli_stmt_execute($request_stmt);
$request_result = mysqli_stmt_get_result($request_stmt);

if(mysqli_num_rows($request_result) != 1){
    mysqli_stmt_close($request_stmt);
    header("Location: blood_bank_approved.php");
    exit();
}

$request = mysqli_fetch_assoc($request_result);
mysqli_stmt_close($request_stmt);

if($request['status'] != 'approved'){
    header("Location: blood_bank_approved.php");
    exit();
}

/* interested donors */
$donor_stmt = mysqli_prepare($conn, "
    SELECT
        di.created_at AS interested_at,
        di.status AS interest_status,
        u.id AS donor_id,
        u.name,
        u.email,
        u.blood_group,
        u.location,
        u.address_line,
        u.city,
        u.zipcode,
        u.weight_kg,
        u.date_of_birth,
        u.availability
    FROM donor_interests di
    JOIN users u ON di.donor_id = u.id
    WHERE di.request_id = ? AND di.status = 'interested'
");
mysqli_stmt_bind_param($donor_stmt, "i", $request_id);
mysqli_stmt_execute($donor_stmt);
$donor_result = mysqli_stmt_get_result($donor_stmt);

$interested_donors = [];
while($row = mysqli_fetch_assoc($donor_result)){
    $eligibility = checkDonorEligibility($conn, $row['donor_id'], $row['date_of_birth'], $row['weight_kg']);
    $priority = calculateDonorPriorityScore($request, $row, $eligibility);

    $row['eligibility'] = $eligibility;
    $row['match_score'] = $priority['score'];
    $row['match_reasons'] = $priority['reasons'];

    $interested_donors[] = $row;
}
mysqli_stmt_close($donor_stmt);

/* Sort by smart priority */
usort($interested_donors, function($a, $b){
    if($a['match_score'] == $b['match_score']){
        return strtotime($a['interested_at']) <=> strtotime($b['interested_at']);
    }
    return $b['match_score'] <=> $a['match_score'];
});

function buildCompactLocation($address = '', $city = '', $zipcode = '', $fallback = ''){
    $parts = array_filter([
        trim((string)$address),
        trim((string)$city),
        trim((string)$zipcode)
    ], function($v){
        return $v !== '';
    });

    if(!empty($parts)){
        return implode(', ', $parts);
    }

    return trim((string)$fallback);
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Interested Donors</title>
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
            <a href="blood_bank_approved.php" class="active">Approved</a>
            <a href="blood_bank_matched.php">Matched</a>
            <a href="blood_bank_scheduled.php">Scheduled</a>
            <a href="blood_bank_confirmed.php">Confirmed</a>
            <a href="blood_bank_history.php">History</a>
            <a href="blood_bank_stock_requests.php">Stock Requests</a>
            <a href="notifications.php">Notifications</a>
        </div>
    </div>
</div>
<div class="container">
    <div class="hero">
        <h1>Interested Donors</h1>
        <p>
            Request #<?php echo htmlspecialchars($request['id']); ?> •
            Recipient: <?php echo htmlspecialchars($request['recipient_name']); ?> •
            Blood Group Needed: <?php echo htmlspecialchars($request['blood_group']); ?>
        </p>
    </div>

    <div class="compact-info-card">
        <h4>Request Summary</h4>
        <p>
            <strong>Address:</strong> <?php echo htmlspecialchars($request['address_line'] ?? ''); ?><br>
            <strong>City:</strong> <?php echo htmlspecialchars($request['city'] ?? ''); ?><br>
            <strong>Zipcode:</strong> <?php echo htmlspecialchars($request['zipcode'] ?? ''); ?><br>
            <strong>Quantity:</strong> <?php echo htmlspecialchars($request['quantity']); ?><br>
            <strong>Urgency:</strong> <?php echo htmlspecialchars($request['urgency']); ?><br>
            <strong>Status:</strong> <?php echo htmlspecialchars($request['status']); ?>
        </p>
    </div>

    <div class="compact-info-card">
        <h4>Smart Matching Priority</h4>
        <p>
            Donors are ranked by <strong>same zipcode</strong>, then <strong>same city</strong>, then
            <strong>availability</strong>, then <strong>eligibility</strong>.
        </p>
    </div>

    <div class="card">
        <h3>Interested Donor List</h3>

        <?php if(!empty($interested_donors)){ ?>
            <div class="table-scroll-premium">
                <table class="premium-table">
                    <tr>
                        <th>Score</th>
                        <th>Donor</th>
                        <th>Blood Group</th>
                        <th class="wrap-cell">Location</th>
                        <th>Weight</th>
                        <th>Age</th>
                        <th>Availability</th>
                        <th>Eligibility</th>
                        <th>Why Ranked</th>
                        <th>Interested At</th>
                        <th>Action</th>
                    </tr>

                    <?php foreach($interested_donors as $index => $donor){ ?>
                        <?php
                        $sameZip = !empty($request['zipcode']) && !empty($donor['zipcode']) && strtolower(trim($request['zipcode'])) === strtolower(trim($donor['zipcode']));
                        $sameCity = !empty($request['city']) && !empty($donor['city']) && strtolower(trim($request['city'])) === strtolower(trim($donor['city']));
                        $eligible = !empty($donor['eligibility']['eligible']);

                        $compactLocation = buildCompactLocation(
                            $donor['address_line'] ?? '',
                            $donor['city'] ?? '',
                            $donor['zipcode'] ?? '',
                            $donor['location'] ?? ''
                        );
                        ?>
                        <tr class="<?php echo $index === 0 ? 'top-match-row' : ''; ?>">
                            <td><span class="score-text"><?php echo htmlspecialchars($donor['match_score']); ?></span></td>

                            <td class="wrap-cell">
                                <div class="donor-name"><?php echo htmlspecialchars($donor['name']); ?></div>
                                <div class="donor-email"><?php echo htmlspecialchars($donor['email']); ?></div>
                            </td>

                            <td><?php echo htmlspecialchars($donor['blood_group']); ?></td>
                            <td class="wrap-cell"><?php echo htmlspecialchars($compactLocation); ?></td>
                            <td><?php echo htmlspecialchars($donor['weight_kg']); ?> kg</td>
                            <td><?php echo htmlspecialchars($donor['eligibility']['age'] ?? 'N/A'); ?></td>

                            <td>
                                <?php if(($donor['availability'] ?? '') === 'available'){ ?>
                                    <span class="mini-badge mini-green">Available</span>
                                <?php } else { ?>
                                    <span class="mini-badge mini-gray">Unavailable</span>
                                <?php } ?>
                            </td>

                            <td>
                                <?php if($eligible){ ?>
                                    <span class="mini-badge mini-green">Eligible</span>
                                <?php } else { ?>
                                    <span class="mini-badge mini-red">Not Eligible</span>
                                <?php } ?>
                            </td>

                            <td class="wrap-cell">
                                <div class="mini-badges">
                                    <?php if($sameZip){ ?>
                                        <span class="mini-badge mini-green">Same zipcode</span>
                                    <?php } ?>
                                    <?php if($sameCity){ ?>
                                        <span class="mini-badge mini-blue">Same city</span>
                                    <?php } ?>
                                    <?php if(!$sameZip && !$sameCity){ ?>
                                        <span class="mini-badge mini-gray">Other area</span>
                                    <?php } ?>
                                    <?php if(!$eligible){ ?>
                                        <span class="mini-badge mini-red">Needs review</span>
                                    <?php } ?>
                                </div>
                            </td>

                            <td><?php echo htmlspecialchars($donor['interested_at']); ?></td>

                            <td>
                                <?php if($eligible){ ?>
                                    <a href="select_interested_donor.php?request_id=<?php echo $request_id; ?>&donor_id=<?php echo $donor['donor_id']; ?>"
                                       class="btn-select-donor"
                                       onclick="return confirm('Select this donor for the request?');">
                                       Select Donor
                                    </a>
                                <?php } else { ?>
                                    <span class="mini-badge mini-red">Cannot Select</span>
                                <?php } ?>
                            </td>
                        </tr>
                    <?php } ?>
                </table>
            </div>
        <?php } else { ?>
            <p>No interested donors found for this request yet.</p>
        <?php } ?>
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

</body>
</html>
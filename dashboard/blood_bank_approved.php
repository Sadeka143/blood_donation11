<?php
session_start();
include '../config/db.php';
include '../functions/urgency_helper.php';
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
$blood_group = isset($_GET['blood_group']) ? trim($_GET['blood_group']) : "all";
$urgency = isset($_GET['urgency']) ? trim($_GET['urgency']) : "all";
$location_search = isset($_GET['location_search']) ? trim($_GET['location_search']) : "";

$recipient_search_safe = mysqli_real_escape_string($conn, $recipient_search);
$location_search_safe = mysqli_real_escape_string($conn, $location_search);

$sql = "
    SELECT r.*, u.name AS recipient_name,
    (
        SELECT COUNT(*) 
        FROM donor_interests di 
        WHERE di.request_id = r.id AND di.status = 'interested'
    ) AS interested_count
    FROM blood_requests r
    JOIN users u ON r.recipient_id = u.id
    WHERE r.status = 'approved'
      AND r.assigned_blood_bank_id = $user_id
";

if($recipient_search_safe !== ""){
    $sql .= " AND u.name LIKE '%$recipient_search_safe%'";
}
if($blood_group !== "all"){
    $group_safe = mysqli_real_escape_string($conn, $blood_group);
    $sql .= " AND r.blood_group = '$group_safe'";
}
if($urgency !== "all"){
    $urgency_safe = mysqli_real_escape_string($conn, $urgency);
    $sql .= " AND r.urgency = '$urgency_safe'";
}
if($location_search_safe !== ""){
    $sql .= " AND (
        r.location LIKE '%$location_search_safe%' OR
        r.address_line LIKE '%$location_search_safe%' OR
        r.city LIKE '%$location_search_safe%' OR
        r.zipcode LIKE '%$location_search_safe%'
    )";
}

$sql .= " ORDER BY " . getUrgencyOrderSql('r.urgency') . ", r.created_at DESC";
$result = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Approved Requests</title>
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
        <h1>Approved Requests</h1>
        <p>These are donor-matching requests only. Requests fulfilled directly from stock are not shown here because they are completed during review.</p>
    </div>

    <div class="card filter-card-compact">
        <h3>Filter Approved Requests</h3>
        <form method="GET" class="filter-form">
            <div class="filter-grid-2">
                <div class="field">
                    <label>Recipient Name</label>
                    <input class="input" type="text" name="recipient_search" value="<?php echo htmlspecialchars($recipient_search); ?>" placeholder="Search recipient">
                </div>

                <div class="field">
                    <label>Blood Group</label>
                    <select class="select" name="blood_group">
                        <option value="all" <?php if($blood_group == 'all') echo 'selected'; ?>>All Groups</option>
                        <option value="A+" <?php if($blood_group == 'A+') echo 'selected'; ?>>A+</option>
                        <option value="A-" <?php if($blood_group == 'A-') echo 'selected'; ?>>A-</option>
                        <option value="B+" <?php if($blood_group == 'B+') echo 'selected'; ?>>B+</option>
                        <option value="B-" <?php if($blood_group == 'B-') echo 'selected'; ?>>B-</option>
                        <option value="O+" <?php if($blood_group == 'O+') echo 'selected'; ?>>O+</option>
                        <option value="O-" <?php if($blood_group == 'O-') echo 'selected'; ?>>O-</option>
                        <option value="AB+" <?php if($blood_group == 'AB+') echo 'selected'; ?>>AB+</option>
                        <option value="AB-" <?php if($blood_group == 'AB-') echo 'selected'; ?>>AB-</option>
                    </select>
                </div>

                <div class="field">
                    <label>Urgency</label>
                    <select class="select" name="urgency">
                        <option value="all" <?php if($urgency == 'all') echo 'selected'; ?>>All</option>
                        <option value="normal" <?php if($urgency == 'normal') echo 'selected'; ?>>Normal</option>
                        <option value="urgent" <?php if($urgency == 'urgent') echo 'selected'; ?>>Urgent</option>
                    </select>
                </div>

                <div class="field">
                    <label>Location</label>
                    <input class="input" type="text" name="location_search" value="<?php echo htmlspecialchars($location_search); ?>" placeholder="Search address / city / zipcode">
                </div>
            </div>

            <div class="filter-actions-row">
                <button class="btn btn-primary" type="submit">Apply</button>
                <a class="btn btn-secondary" href="blood_bank_approved.php">Reset</a>
            </div>
        </form>
    </div>

    <div class="card">
        <h3>Approved Request List</h3>

        <div class="table-scroll-admin">
            <?php if(mysqli_num_rows($result) > 0){ ?>
                <table class="compact-admin-table">
                    <tr>
                        <th>ID</th>
                        <th>Recipient</th>
                        <th>Blood Group</th>
                        <th class="wrap-cell">Location</th>
                        <th class="small-cell">Quantity</th>
                        <th>Urgency</th>
                        <th>Status</th>
                        <th>Interested Donors</th>
                        <th class="date-cell">Requested On</th>
                        <th>Action</th>
                    </tr>

                    <?php while($row = mysqli_fetch_assoc($result)){ ?>
                        <tr class="<?php echo ($row['urgency'] === 'urgent') ? 'urgent-row' : ''; ?>">
                            <td><?php echo (int)$row['id']; ?></td>
                            <td><?php echo htmlspecialchars($row['recipient_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['blood_group']); ?></td>
                            <td class="wrap-cell"><?php echo htmlspecialchars($row['location']); ?></td>
                            <td class="small-cell"><?php echo htmlspecialchars($row['quantity']); ?></td>
                            <td>
                                <span class="<?php echo getUrgencyBadgeClass($row['urgency']); ?>">
                                    <?php echo getUrgencyLabel($row['urgency']); ?>
                                </span>
                            </td>
                            <td><span class="badge badge-accepted"><?php echo htmlspecialchars($row['status']); ?></span></td>
                            <td>
                                <?php if((int)$row['interested_count'] > 0){ ?>
                                    <span class="mini-badge mini-green"><?php echo (int)$row['interested_count']; ?> Interested</span>
                                <?php } else { ?>
                                    <span class="mini-badge mini-orange">0 Interested</span>
                                <?php } ?>
                            </td>
                            <td class="date-cell"><?php echo htmlspecialchars($row['created_at']); ?></td>
                            <td>
                                <a class="btn btn-primary btn-compact" href="interested_donors.php?id=<?php echo $row['id']; ?>">View Interested Donors</a>
                            </td>
                        </tr>
                    <?php } ?>
                </table>
            <?php } else { ?>
                <p>No approved donor-matching requests available.</p>
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

</body>
</html>
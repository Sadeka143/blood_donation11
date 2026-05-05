<?php
// ======================================
// NOTIFICATIONS PAGE
// Purpose:
// - Show notifications for donor, recipient, blood bank, and admin
// - Show only one notification badge in header
// - Allow stored notifications to be marked as read
// - Admin can view stored notifications + live system alerts
// ======================================

session_start();

include '../config/db.php';
include '../functions/network_helper.php';
include '../functions/admin_notification_helper.php';

// Check login
if(!isset($_SESSION['user_id']) || !isset($_SESSION['role'])){
    header("Location: ../auth/login.php");
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$user_role = $_SESSION['role'];
$user_name = $_SESSION['name'] ?? 'User';

$is_admin = ($user_role === 'admin');

// Prepare display name and role label
$role_label = ucfirst(str_replace('_', ' ', $user_role));

if($user_role === 'blood_bank'){
    $display_name = getBloodBankWelcomeLabel();
    $role_label = "Network Control";
} else {
    $display_name = $user_name;
}

// Back link by role
$back_link = 'index.php';

if($user_role === 'donor'){
    $back_link = 'donor_dashboard.php';
} elseif($user_role === 'recipient'){
    $back_link = 'recipient_dashboard.php';
} elseif($user_role === 'blood_bank'){
    $back_link = 'blood_bank_dashboard.php';
} elseif($user_role === 'admin'){
    $back_link = 'admin_dashboard.php';
}

// Mark all stored notifications as read
if(isset($_GET['mark']) && $_GET['mark'] === 'all'){

    $mark_stmt = mysqli_prepare($conn, "
        UPDATE notifications
        SET is_read = 1
        WHERE user_id = ?
    ");

    mysqli_stmt_bind_param($mark_stmt, "i", $user_id);
    mysqli_stmt_execute($mark_stmt);
    mysqli_stmt_close($mark_stmt);

    header("Location: notifications.php?marked=1");
    exit();
}

// Get notification data
if($is_admin){

    // Admin gets stored notifications + live alerts
    $admin_items = getAdminNotificationItems($conn, $user_id);
    $unread_count = getAdminNotificationCount($conn, $user_id);

    // Only stored unread notifications can be marked as read
    $stored_unread_count = getAdminUnreadStoredNotificationCount($conn, $user_id);

} else {

    // Normal users get stored notifications only
    $notif_stmt = mysqli_prepare($conn, "
        SELECT id, user_id, message, is_read, created_at
        FROM notifications
        WHERE user_id = ?
        ORDER BY created_at DESC
    ");
    mysqli_stmt_bind_param($notif_stmt, "i", $user_id);
    mysqli_stmt_execute($notif_stmt);
    $notif_result = mysqli_stmt_get_result($notif_stmt);

    // Count unread stored notifications
    $count_stmt = mysqli_prepare($conn, "
        SELECT COUNT(*) AS total
        FROM notifications
        WHERE user_id = ? AND is_read = 0
    ");
    mysqli_stmt_bind_param($count_stmt, "i", $user_id);
    mysqli_stmt_execute($count_stmt);
    $count_result = mysqli_stmt_get_result($count_stmt);
    $count_data = mysqli_fetch_assoc($count_result);
    $unread_count = (int)($count_data['total'] ?? 0);
    mysqli_stmt_close($count_stmt);
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Notifications</title>
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
                    <span class="notify-badge"><?php echo (int)$unread_count; ?></span>
                </a>

                <span class="topbar-user-text">
                    Welcome, <?php echo htmlspecialchars($display_name); ?> (<?php echo htmlspecialchars($role_label); ?>)
                </span>

                <a class="btn btn-logout" href="../auth/logout.php">Logout</a>
            </div>
        </div>

        <div class="topbar-menu">
            <?php if($user_role === 'donor'){ ?>
                <a href="donor_dashboard.php">Dashboard</a>
                <a href="donor_compatible_requests.php">Compatible Requests</a>
                <a href="donor_interested_requests.php">Interested Requests</a>
                <a href="donate_to_stock.php">Donate to Stock</a>
                <a href="donor_appointments.php">Appointments</a>
                <a href="donor_history.php">History</a>
                <a href="donor_profile.php">Profile</a>
                <a href="notifications.php" class="active">Notifications</a>

            <?php } elseif($user_role === 'recipient'){ ?>
                <a href="recipient_dashboard.php">Dashboard</a>
                <a href="create_request.php">Create Request</a>
                <a href="recipient_requests.php">My Requests</a>
                <a href="recipient_appointments.php">Appointments</a>
                <a href="recipient_profile.php">Profile</a>
                <a href="notifications.php" class="active">Notifications</a>

            <?php } elseif($user_role === 'blood_bank'){ ?>
                <a href="blood_bank_dashboard.php">Dashboard</a>
                <a href="blood_bank_pending.php">Pending</a>
                <a href="blood_bank_approved.php">Approved</a>
                <a href="blood_bank_matched.php">Matched</a>
                <a href="blood_bank_scheduled.php">Scheduled</a>
                <a href="blood_bank_confirmed.php">Confirmed</a>
                <a href="blood_bank_history.php">History</a>
                <a href="blood_bank_stock_requests.php">Stock Donations</a>
                <a href="notifications.php" class="active">Notifications</a>

            <?php } elseif($user_role === 'admin'){ ?>
                <a href="admin_dashboard.php">Dashboard</a>
                <a href="admin_users.php">Users</a>
                <a href="admin_requests.php">Requests</a>
                <a href="admin_donations.php">Donations</a>
                <a href="admin_appointments.php">Appointments</a>
                <a href="admin_branches.php">Branches</a>
                <a href="admin_stock_requests.php">Stock Requests</a>
                <a href="admin_activity_logs.php">Activity Logs</a>
                <a href="notifications.php" class="active">Notifications</a>
            <?php } ?>
        </div>
    </div>
</div>

<div class="container">
    <div class="hero">
        <h1>Your Notifications</h1>
        <p>
            <?php if($is_admin){ ?>
                View live admin alerts related to urgent requests, pending reviews, stock requests, appointment reschedules, and recent system activity.
            <?php } else { ?>
                View all recent updates related to your account, requests, appointments, donation activities, and stock-related workflow.
            <?php } ?>
        </p>
    </div>

    <?php if(isset($_GET['marked']) && $_GET['marked'] == '1'){ ?>
        <div class="alert alert-success">Stored notifications have been marked as read.</div>
    <?php } ?>

    <div class="card">
        <h3>Notification List</h3>

        <div class="notification-toolbar">
            <a class="link" href="<?php echo htmlspecialchars($back_link); ?>">← Back to Dashboard</a>

            <div class="notification-actions">
                <span class="badge badge-pending">
                    <?php echo $is_admin ? 'Live Alerts: ' : 'Unread: '; ?><?php echo (int)$unread_count; ?>
                </span>

                <?php if($is_admin){ ?>
                    <?php if(isset($stored_unread_count) && $stored_unread_count > 0){ ?>
                        <a class="btn btn-primary" href="notifications.php?mark=all">Mark all as read</a>
                    <?php } ?>
                <?php } else { ?>
                    <?php if($unread_count > 0){ ?>
                        <a class="btn btn-primary" href="notifications.php?mark=all">Mark all as read</a>
                    <?php } ?>
                <?php } ?>
            </div>
        </div>

        <div class="notif-table-wrap">
            <?php if($is_admin){ ?>

                <?php if(!empty($admin_items)){ ?>
                    <table class="notification-table">
                        <tr>
                            <th>Type</th>
                            <th>Message</th>
                            <th>Date & Time</th>
                        </tr>

                        <?php foreach($admin_items as $item){ ?>
                            <?php
                            // Set row and badge style by alert type
                            $rowClass = ($item['type'] === 'urgent') ? 'urgent-row' : '';

                            $badgeClass = 'badge-completed';
                            $badgeText = 'Recent';

                            if($item['type'] === 'urgent'){
                                $badgeClass = 'badge-pending';
                                $badgeText = 'Urgent';
                            } elseif($item['type'] === 'warning'){
                                $badgeClass = 'badge-pending';
                                $badgeText = 'Warning';
                            } elseif($item['type'] === 'info'){
                                $badgeClass = 'badge-accepted';
                                $badgeText = 'Info';
                            } elseif($item['type'] === 'stored_unread'){
                                $badgeClass = 'badge-pending';
                                $badgeText = 'Unread';
                            } elseif($item['type'] === 'stored_read'){
                                $badgeClass = 'badge-completed';
                                $badgeText = 'Read';
                            }
                            ?>

                            <tr class="<?php echo $rowClass; ?>">
                                <td>
                                    <span class="badge <?php echo htmlspecialchars($badgeClass); ?>">
                                        <?php echo htmlspecialchars($badgeText); ?>
                                    </span>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($item['title']); ?></strong>
                                    <br>
                                    <?php echo htmlspecialchars($item['message']); ?>
                                </td>
                                <td><?php echo htmlspecialchars($item['created_at']); ?></td>
                            </tr>
                        <?php } ?>
                    </table>
                <?php } else { ?>
                    <p>No admin alerts available.</p>
                <?php } ?>

            <?php } else { ?>

                <?php if(mysqli_num_rows($notif_result) > 0){ ?>
                    <table class="notification-table">
                        <tr>
                            <th>ID</th>
                            <th>Message</th>
                            <th>Status</th>
                            <th>Date & Time</th>
                        </tr>

                        <?php while($row = mysqli_fetch_assoc($notif_result)){ ?>
                            <?php
                            // Highlight urgent messages
                            $isUrgent = (stripos($row['message'], '[URGENT]') !== false);

                            if($row['is_read']){
                                $status_badge = "<span class='badge badge-completed'>Read</span>";
                            } else {
                                $status_badge = "<span class='badge badge-pending'>Unread</span>";
                            }
                            ?>

                            <tr class="<?php echo $isUrgent ? 'urgent-row' : ''; ?>">
                                <td><?php echo htmlspecialchars($row['id']); ?></td>

                                <td>
                                    <?php if($isUrgent){ ?>
                                        <span class="urgent-pill">URGENT</span>
                                        <span class="urgent-message"><?php echo htmlspecialchars($row['message']); ?></span>
                                    <?php } else { ?>
                                        <?php echo htmlspecialchars($row['message']); ?>
                                    <?php } ?>
                                </td>

                                <td><?php echo $status_badge; ?></td>
                                <td><?php echo htmlspecialchars($row['created_at']); ?></td>
                            </tr>
                        <?php } ?>
                    </table>
                <?php } else { ?>
                    <p>No notifications available.</p>
                <?php } ?>

                <?php mysqli_stmt_close($notif_stmt); ?>

            <?php } ?>
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
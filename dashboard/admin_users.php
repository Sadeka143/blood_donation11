<?php
// ======================================
// ADMIN USERS PAGE
// Purpose:
// - View/filter users
// - Activate/deactivate users
// - Delete users with mandatory reason
// ======================================

session_start();
include '../config/db.php';
include '../functions/network_helper.php';
include '../functions/admin_notification_helper.php';

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin'){
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$notif_count = getAdminNotificationCount($conn);

$search = isset($_GET['search']) ? trim($_GET['search']) : "";
$role_filter = isset($_GET['role']) ? trim($_GET['role']) : "all";
$blood_group = isset($_GET['blood_group']) ? trim($_GET['blood_group']) : "all";
$city = isset($_GET['city']) ? trim($_GET['city']) : "";

$search_safe = mysqli_real_escape_string($conn, $search);
$city_safe = mysqli_real_escape_string($conn, $city);

$sql = "SELECT * FROM users WHERE 1=1";

if($search_safe !== ""){
    $sql .= " AND (
        name LIKE '%$search_safe%' OR
        email LIKE '%$search_safe%' OR
        institution_name LIKE '%$search_safe%'
    )";
}

if($role_filter !== "all"){
    $role_safe = mysqli_real_escape_string($conn, $role_filter);
    $sql .= " AND role = '$role_safe'";
}

if($blood_group !== "all"){
    $group_safe = mysqli_real_escape_string($conn, $blood_group);
    $sql .= " AND blood_group = '$group_safe'";
}

if($city_safe !== ""){
    $sql .= " AND city LIKE '%$city_safe%'";
}

$sql .= " ORDER BY id DESC";
$result = mysqli_query($conn, $sql);

$flashMessage = "";
$flashClass = "";

if(isset($_GET['result'])){
    if($_GET['result'] === 'status_updated'){
        $flashMessage = "User status updated successfully.";
        $flashClass = "alert-success";
    } elseif($_GET['result'] === 'deleted'){
        $flashMessage = "User deleted successfully and the reason was saved in activity logs.";
        $flashClass = "alert-success";
    } elseif($_GET['result'] === 'reason_required'){
        $flashMessage = "Please write a valid delete reason before deleting a user.";
        $flashClass = "alert-error";
    } elseif($_GET['result'] === 'self_blocked'){
        $flashMessage = "You cannot deactivate or delete your own admin account.";
        $flashClass = "alert-error";
    } elseif($_GET['result'] === 'not_found'){
        $flashMessage = "User not found.";
        $flashClass = "alert-error";
    } elseif($_GET['result'] === 'error'){
        $flashMessage = "Something went wrong while processing the user action.";
        $flashClass = "alert-error";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Users</title>
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
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?> (Admin)</span>
                <a class="btn btn-logout" href="../auth/logout.php">Logout</a>
            </div>
        </div>

        <div class="topbar-menu">
            <a href="admin_dashboard.php">Dashboard</a>
            <a href="admin_users.php" class="active">Users</a>
            <a href="admin_requests.php">Requests</a>
            <a href="admin_donations.php">Donations</a>
            <a href="admin_appointments.php">Appointments</a>
            <a href="admin_branches.php">Branches</a>
            <a href="admin_stock_requests.php">Stock Requests</a>
            <a href="admin_activity_logs.php">Activity Logs</a>
            <a href="notifications.php">Notifications</a>
        </div>
    </div>
</div>

<div class="container">

    <div class="hero">
        <h1>Users Overview</h1>
        <p>Review donors, recipients, blood banks, and administrators. Admin must provide a valid reason before deleting any user.</p>
    </div>

    <?php if($flashMessage != ""){ ?>
        <div class="alert <?php echo $flashClass; ?>">
            <?php echo htmlspecialchars($flashMessage); ?>
        </div>
    <?php } ?>

    <div class="card filter-card-compact">
        <h3>Filter Users</h3>

        <form method="GET" class="filter-form">
            <div class="filter-grid-2">
                <div class="field">
                    <label>Name / Email / Institution</label>
                    <input class="input" type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search user">
                </div>

                <div class="field">
                    <label>Role</label>
                    <select class="select" name="role">
                        <option value="all" <?php if($role_filter=='all') echo 'selected'; ?>>All Roles</option>
                        <option value="admin" <?php if($role_filter=='admin') echo 'selected'; ?>>Admin</option>
                        <option value="blood_bank" <?php if($role_filter=='blood_bank') echo 'selected'; ?>>Blood Bank</option>
                        <option value="donor" <?php if($role_filter=='donor') echo 'selected'; ?>>Donor</option>
                        <option value="recipient" <?php if($role_filter=='recipient') echo 'selected'; ?>>Recipient</option>
                    </select>
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
                    <label>City</label>
                    <input class="input" type="text" name="city" value="<?php echo htmlspecialchars($city); ?>" placeholder="Search city">
                </div>
            </div>

            <div class="filter-actions-row">
                <button class="btn btn-primary" type="submit">Apply</button>
                <a class="btn btn-secondary" href="admin_users.php">Reset</a>
            </div>
        </form>
    </div>

    <div class="card">
    <div class="admin-list-header">
        <h3>User List</h3>

        <div class="admin-export-actions">
            <a class="btn btn-primary" href="export_users.php">Export Users CSV</a>

            <a class="btn btn-primary" href="export_users_filtered.php?<?php echo http_build_query([
                'search' => $search,
                'role' => $role_filter,
                'blood_group' => $blood_group,
                'city' => $city
            ]); ?>">Export Filtered CSV</a>
        </div>
    </div>

        <div class="table-scroll-admin">
            <?php if(mysqli_num_rows($result) > 0){ ?>
                <table class="compact-admin-table admin-users-delete-table">
                    <tr>
                        <th class="small-cell">ID</th>
                        <th>Name / Institution</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Blood Group</th>
                        <th>Contact</th>
                        <th class="wrap-cell">Location</th>
                        <th>Status</th>
                        <th class="delete-reason-col">Delete Reason</th>
                        <th class="action-col">Action</th>
                    </tr>

                    <?php while($row = mysqli_fetch_assoc($result)){ ?>
                        <?php
                        $displayName = $row['role'] === 'blood_bank'
                            ? getBloodBankNetworkName()
                            : ($row['name'] ?? '');

                        $roleBadge = "badge-pending";
                        if($row['role'] === 'admin') $roleBadge = "badge-completed";
                        if($row['role'] === 'blood_bank') $roleBadge = "badge-accepted";
                        if($row['role'] === 'donor') $roleBadge = "badge-accepted";
                        if($row['role'] === 'recipient') $roleBadge = "badge-pending";

                        $statusValue = $row['status'];
                        $statusBadge = ($statusValue === 'inactive') ? 'badge-pending' : 'badge-accepted';
                        $statusLabel = ($statusValue === 'inactive') ? 'Inactive' : 'Active';

                        $isCurrentAdmin = ((int)$row['id'] === (int)$user_id);
                        $deleteFormId = "deleteForm_" . (int)$row['id'];
                        ?>
                        <tr>
                            <td class="small-cell"><?php echo htmlspecialchars($row['id']); ?></td>
                            <td><?php echo htmlspecialchars($displayName); ?></td>
                            <td><?php echo htmlspecialchars($row['email'] ?? ''); ?></td>
                            <td><span class="badge <?php echo $roleBadge; ?>"><?php echo htmlspecialchars($row['role']); ?></span></td>
                            <td><?php echo htmlspecialchars($row['blood_group'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($row['contact_number'] ?? ''); ?></td>
                            <td class="wrap-cell"><?php echo htmlspecialchars($row['location'] ?? ''); ?></td>
                            <td><span class="badge <?php echo $statusBadge; ?>"><?php echo $statusLabel; ?></span></td>

                            <td class="delete-reason-col">
                                <?php if(!$isCurrentAdmin){ ?>
                                    <textarea
                                        class="input delete-reason-input"
                                        name="delete_reason"
                                        form="<?php echo $deleteFormId; ?>"
                                        placeholder="Write valid reason before delete..."
                                        required
                                    ></textarea>
                                <?php } else { ?>
                                    <span class="mini-badge mini-orange">Not allowed</span>
                                <?php } ?>
                            </td>

                            <td class="action-col">
                                <?php if($isCurrentAdmin){ ?>
                                    <span class="mini-badge mini-orange">Current Admin</span>
                                <?php } else { ?>
                                    <div class="action-stack">
                                        <?php if($statusValue === 'inactive'){ ?>
                                            <a class="btn btn-primary btn-compact" href="toggle_user_status.php?id=<?php echo $row['id']; ?>" onclick="return confirm('Activate this user?');">Activate</a>
                                        <?php } else { ?>
                                            <a class="btn btn-secondary btn-compact" href="toggle_user_status.php?id=<?php echo $row['id']; ?>" onclick="return confirm('Deactivate this user?');">Deactivate</a>
                                        <?php } ?>

                                        <form id="<?php echo $deleteFormId; ?>" method="POST" action="delete_user.php" onsubmit="return confirm('Are you sure you want to delete this user? This action will also remove linked records.');">
                                            <input type="hidden" name="id" value="<?php echo (int)$row['id']; ?>">
                                            <button class="btn btn-danger-outline btn-compact" type="submit">Delete</button>
                                        </form>
                                    </div>
                                <?php } ?>
                            </td>
                        </tr>
                    <?php } ?>
                </table>
            <?php } else { ?>
                <p>No users found.</p>
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
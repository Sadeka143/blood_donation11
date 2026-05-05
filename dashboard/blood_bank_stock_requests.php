<?php
session_start();
include '../config/db.php';
include '../functions/network_helper.php';
include '../functions/location_helper.php';

// =========================================================
// Access control: only blood bank user can view this page
// =========================================================
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'blood_bank'){
    header("Location: ../auth/login.php");
    exit();
}

$blood_bank_id = (int)$_SESSION['user_id'];

// =========================================================
// Notification badge count
// =========================================================
$notif_stmt = mysqli_prepare($conn, "SELECT COUNT(*) AS total FROM notifications WHERE user_id = ? AND is_read = 0");
mysqli_stmt_bind_param($notif_stmt, "i", $blood_bank_id);
mysqli_stmt_execute($notif_stmt);
$notif_result = mysqli_stmt_get_result($notif_stmt);
$notif_data = mysqli_fetch_assoc($notif_result);
$notif_count = $notif_data['total'] ?? 0;
mysqli_stmt_close($notif_stmt);

// =========================================================
// Filters
// =========================================================
$blood_group = trim($_GET['blood_group'] ?? 'all');
$branch_search = trim($_GET['branch_search'] ?? '');
$status = trim($_GET['status'] ?? 'all');
$location_search = trim($_GET['location_search'] ?? '');

$allowed_groups = ['all','A+','A-','B+','B-','O+','O-','AB+','AB-'];
$allowed_status = ['all','pending','scheduled','confirmed','completed','cancelled'];

if(!in_array($blood_group, $allowed_groups)){
    $blood_group = 'all';
}

if(!in_array($status, $allowed_status)){
    $status = 'all';
}

// =========================================================
// Fetch stock donation requests
// Status flow:
// pending -> scheduled -> confirmed -> completed
// cancelled can happen if donor declines.
// =========================================================
$sql = "
    SELECT
        sdr.*,
        donor.name AS donor_name,
        donor.blood_group,
        b.branch_name,
        b.address_line,
        b.city,
        b.zipcode
    FROM stock_donation_requests sdr
    JOIN users donor ON sdr.donor_id = donor.id
    LEFT JOIN branches b ON sdr.branch_id = b.id
    WHERE sdr.blood_bank_user_id = ?
";

$params = [$blood_bank_id];
$types = "i";

if($blood_group !== 'all'){
    $sql .= " AND donor.blood_group = ?";
    $params[] = $blood_group;
    $types .= "s";
}

if($branch_search !== ''){
    $sql .= " AND b.branch_name LIKE ?";
    $params[] = "%" . $branch_search . "%";
    $types .= "s";
}

if($status !== 'all'){
    $sql .= " AND sdr.status = ?";
    $params[] = $status;
    $types .= "s";
}

if($location_search !== ''){
    $sql .= " AND (
        b.address_line LIKE ?
        OR b.city LIKE ?
        OR b.zipcode LIKE ?
    )";
    $kw = "%" . $location_search . "%";
    $params[] = $kw;
    $params[] = $kw;
    $params[] = $kw;
    $types .= "sss";
}

$sql .= " ORDER BY sdr.id DESC";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// =========================================================
// Flash messages after schedule/complete actions
// =========================================================
$flashMessage = "";
$flashClass = "";

if(isset($_GET['scheduled']) && $_GET['scheduled'] === '1'){
    $flashMessage = "Stock donation slot has been saved successfully.";
    $flashClass = "alert-success";
}

if(isset($_GET['complete'])){
    if($_GET['complete'] === 'success'){
        $flashMessage = "Stock donation has been completed successfully. Branch stock, donation history, donor availability, and notification have been updated.";
        $flashClass = "alert-success";
    } elseif($_GET['complete'] === 'not_confirmed'){
        $flashMessage = "This stock donation cannot be completed yet. Donor must confirm the appointment first.";
        $flashClass = "alert-error";
    } elseif($_GET['complete'] === 'invalid'){
        $flashMessage = "Invalid stock donation request.";
        $flashClass = "alert-error";
    } elseif($_GET['complete'] === 'error'){
        $flashMessage = "Something went wrong while completing the stock donation.";
        $flashClass = "alert-error";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Stock Donation Requests</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>

<div class="topbar topbar-full bloodbank-shell">
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
                <a href="notifications.php" class="notify" title="Notifications">🔔
                    <span class="notify-badge"><?php echo (int)$notif_count; ?></span>
                </a>
                <span>Welcome, <?php echo htmlspecialchars(getBloodBankWelcomeLabel()); ?> (Network Control)</span>
                <a class="btn btn-logout" href="../auth/logout.php">Logout</a>
            </div>
        </div>

        <div class="topbar-menu">
            <a href="blood_bank_dashboard.php">Dashboard</a>
            <a href="blood_bank_pending.php">Pending</a>
            <a href="blood_bank_approved.php">Approved</a>
            <a href="blood_bank_matched.php">Matched</a>
            <a href="blood_bank_scheduled.php">Scheduled</a>
            <a href="blood_bank_confirmed.php">Confirmed</a>
            <a href="blood_bank_history.php">History</a>
            <a href="blood_bank_stock_requests.php" class="active">Stock Requests</a>
            <a href="notifications.php">Notifications</a>
        </div>
    </div>
</div>

<div class="container">
    <div class="hero">
        <h1>Stock Donation Requests</h1>
        <p>Manage stock donation requests from donor request submission to appointment confirmation and final donation completion.</p>
    </div>

    <?php if($flashMessage !== ''){ ?>
        <div class="alert <?php echo htmlspecialchars($flashClass); ?>"><?php echo htmlspecialchars($flashMessage); ?></div>
    <?php } ?>

    <div class="card">
        <h3>Filter Stock Requests</h3>

        <form method="GET" class="filter-form">
            <div class="filter-grid-2">
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
                    <label>Branch</label>
                    <input class="input" type="text" name="branch_search" value="<?php echo htmlspecialchars($branch_search); ?>" placeholder="Search branch">
                </div>

                <div class="field">
                    <label>Status</label>
                    <select class="select" name="status">
                        <option value="all" <?php if($status == 'all') echo 'selected'; ?>>All</option>
                        <option value="pending" <?php if($status == 'pending') echo 'selected'; ?>>Pending</option>
                        <option value="scheduled" <?php if($status == 'scheduled') echo 'selected'; ?>>Scheduled</option>
                        <option value="confirmed" <?php if($status == 'confirmed') echo 'selected'; ?>>Confirmed</option>
                        <option value="completed" <?php if($status == 'completed') echo 'selected'; ?>>Completed</option>
                        <option value="cancelled" <?php if($status == 'cancelled') echo 'selected'; ?>>Cancelled</option>
                    </select>
                </div>

                <div class="field">
                    <label>Location</label>
                    <input class="input" type="text" name="location_search" value="<?php echo htmlspecialchars($location_search); ?>" placeholder="Search address / city / zipcode">
                </div>
            </div>

            <div class="filter-actions-row">
                <button class="btn btn-primary" type="submit">Apply</button>
                <a class="btn btn-secondary" href="blood_bank_stock_requests.php">Reset</a>
            </div>
        </form>
    </div>

    <div class="card">
        <h3>Stock Requests</h3>

        <div class="table-scroll-admin">
            <?php if(mysqli_num_rows($result) > 0){ ?>
                <table class="compact-admin-table">
                    <tr>
                        <th>ID</th>
                        <th>Donor</th>
                        <th>Blood Group</th>
                        <th>Preferred Date</th>
                        <th>Scheduled Date</th>
                        <th>Branch</th>
                        <th>Location</th>
                        <th>Map</th>
                        <th>Notes</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>

                    <?php while($row = mysqli_fetch_assoc($result)){ ?>
                        <?php
                        // -----------------------------
                        // Build branch location and map links
                        // -----------------------------
                        $fullLocation = buildFullLocation(
                            $row['address_line'] ?? '',
                            $row['city'] ?? '',
                            $row['zipcode'] ?? '',
                            ''
                        );

                        $mapUrl = buildMapUrl(
                            $row['address_line'] ?? '',
                            $row['city'] ?? '',
                            $row['zipcode'] ?? '',
                            ''
                        );

                        $embedUrl = buildEmbedMapUrl(
                            $row['address_line'] ?? '',
                            $row['city'] ?? '',
                            $row['zipcode'] ?? '',
                            ''
                        );

                        $mapId = "stockMap" . (int)$row['id'];

                        // -----------------------------
                        // Badge color by status
                        // -----------------------------
                        $statusClass = 'badge-pending';
                        if($row['status'] === 'scheduled'){
                            $statusClass = 'badge-accepted';
                        }
                        if($row['status'] === 'confirmed' || $row['status'] === 'completed'){
                            $statusClass = 'badge-completed';
                        }
                        if($row['status'] === 'cancelled'){
                            $statusClass = 'badge-pending';
                        }

                        // -----------------------------
                        // Donor reschedule detection from notes
                        // -----------------------------
                        $hasDonorRescheduleRequest = (strpos((string)($row['notes'] ?? ''), '[Donor Reschedule Request]') !== false);

                        // -----------------------------
                        // Action button logic
                        // pending   -> Schedule Slot / Propose New Slot
                        // scheduled -> Propose New Slot only; donor still needs to confirm
                        // confirmed -> Complete Donation button appears here
                        // completed/cancelled -> no action
                        // -----------------------------
                        $actionHtml = '<span class="small-muted">---</span>';

                        if($row['status'] === 'pending' && !$hasDonorRescheduleRequest){
                            $actionHtml = '<a class="stock-action-btn stock-action-schedule" href="schedule_stock_donation.php?id=' . (int)$row['id'] . '">Schedule Slot</a>';
                        } elseif($row['status'] === 'pending' && $hasDonorRescheduleRequest){
                            $actionHtml = '<a class="stock-action-btn stock-action-propose" href="schedule_stock_donation.php?id=' . (int)$row['id'] . '">Propose New Slot</a>';
                        } elseif($row['status'] === 'scheduled'){
                            $actionHtml = '<a class="stock-action-btn stock-action-propose" href="schedule_stock_donation.php?id=' . (int)$row['id'] . '">Propose New Slot</a>';
                        } elseif($row['status'] === 'confirmed'){
                            $actionHtml = '<div class="stock-action-group">';
                            $actionHtml .= '<a class="stock-action-btn stock-action-complete" href="complete_stock_donation.php?id=' . (int)$row['id'] . '" onclick="return confirm(\'Mark this stock donation as completed? This will increase stock and make donor unavailable until next eligible date.\');">Complete Donation</a>';
                            $actionHtml .= '<a class="stock-action-btn stock-action-propose" href="schedule_stock_donation.php?id=' . (int)$row['id'] . '">Propose New Slot</a>';
                            $actionHtml .= '</div>';
                        }
                        ?>
                        <tr>
                            <td><?php echo (int)$row['id']; ?></td>
                            <td><?php echo htmlspecialchars($row['donor_name'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($row['blood_group']); ?></td>
                            <td><?php echo htmlspecialchars($row['preferred_date'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($row['scheduled_date'] ?? 'Not scheduled'); ?></td>
                            <td><?php echo htmlspecialchars($row['branch_name'] ?? 'Not assigned'); ?></td>
                            <td class="wrap-cell"><?php echo htmlspecialchars($fullLocation); ?></td>
                            <td>
                                <?php if($embedUrl !== ''){ ?>
                                    <div class="map-actions">
                                        <button type="button" class="map-toggle-btn map-toggle-btn-sm" onclick="toggleMap('<?php echo $mapId; ?>', this)">Show Map</button>
                                        <a class="map-open-link" href="<?php echo htmlspecialchars($mapUrl); ?>" target="_blank">Open in Google Maps</a>
                                    </div>
                                    <div id="<?php echo $mapId; ?>" class="embedded-map-wrap">
                                        <iframe src="<?php echo htmlspecialchars($embedUrl); ?>" loading="lazy"></iframe>
                                    </div>
                                <?php } else { ?>
                                    ---
                                <?php } ?>
                            </td>
                            <td class="wrap-cell"><?php echo nl2br(htmlspecialchars($row['notes'] ?? '')); ?></td>
                            <td><span class="badge <?php echo $statusClass; ?>"><?php echo htmlspecialchars($row['status']); ?></span></td>
                            <td><?php echo $actionHtml; ?></td>
                        </tr>
                    <?php } ?>
                </table>
            <?php } else { ?>
                <p>No stock donation requests found.</p>
            <?php } ?>
        </div>
    </div>
</div>

<div class="site-footer site-footer-full bloodbank-shell-footer">
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
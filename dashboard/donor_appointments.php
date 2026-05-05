<?php
session_start();
include '../config/db.php';
include '../functions/location_helper.php';
include '../functions/network_helper.php';
include '../functions/eligibility_check.php';

// Check donor login
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'donor') {
    header("Location: ../auth/login.php");
    exit();
}

$donor_id = (int)$_SESSION['user_id'];

// Sync donor eligibility before showing appointment actions
$eligibility = syncDonorAvailabilityStatus($conn, $donor_id);
$canConfirmAppointment = ($eligibility['eligible'] === true && ($eligibility['availability'] ?? '') === 'available');

$eligibilityReason = $eligibility['reason'] ?? 'You are currently not eligible to donate.';
$nextEligibleDate = $eligibility['next_eligible_date'] ?? '';
$blockedConfirmMessage = $eligibilityReason;

if ($nextEligibleDate !== '') {
    $blockedConfirmMessage .= ' Next eligible date: ' . $nextEligibleDate . '.';
}

$blockedConfirmMessageEsc = htmlspecialchars($blockedConfirmMessage, ENT_QUOTES, 'UTF-8');

// Notification count
$notif_stmt = mysqli_prepare($conn, "SELECT COUNT(*) AS total FROM notifications WHERE user_id = ? AND is_read = 0");
mysqli_stmt_bind_param($notif_stmt, "i", $donor_id);
mysqli_stmt_execute($notif_stmt);
$notif_result = mysqli_stmt_get_result($notif_stmt);
$notif_data = mysqli_fetch_assoc($notif_result);
$notif_count = $notif_data['total'] ?? 0;
mysqli_stmt_close($notif_stmt);

// Request-based appointments
$request_sql = "
    SELECT 
        a.id,
        a.request_id,
        a.appointment_date,
        a.appointment_location,
        a.appointment_address,
        a.appointment_city,
        a.appointment_zipcode,
        a.notes,
        a.status,
        a.blood_bank_id,
        a.recipient_id,
        r.blood_group,
        r.quantity,
        r.urgency,
        b.branch_name
    FROM appointments a
    JOIN blood_requests r ON a.request_id = r.id
    LEFT JOIN branches b ON a.branch_id = b.id
    WHERE a.donor_id = ?
";

// Stock donation appointments
$stock_sql = "
    SELECT
        sdr.id,
        sdr.branch_id,
        sdr.blood_bank_user_id,
        sdr.preferred_date,
        sdr.scheduled_date,
        sdr.notes,
        sdr.status,
        u.blood_group,
        b.branch_name,
        b.address_line,
        b.city,
        b.zipcode
    FROM stock_donation_requests sdr
    JOIN users u ON sdr.donor_id = u.id
    LEFT JOIN branches b ON sdr.branch_id = b.id
    WHERE sdr.donor_id = ?
";

// Run request appointment query
$request_stmt = mysqli_prepare($conn, $request_sql);
mysqli_stmt_bind_param($request_stmt, "i", $donor_id);
mysqli_stmt_execute($request_stmt);
$request_result = mysqli_stmt_get_result($request_stmt);

// Run stock appointment query
$stock_stmt = mysqli_prepare($conn, $stock_sql);
mysqli_stmt_bind_param($stock_stmt, "i", $donor_id);
mysqli_stmt_execute($stock_stmt);
$stock_result = mysqli_stmt_get_result($stock_stmt);

$appointments = [];

// Build request-based appointment list
while ($row = mysqli_fetch_assoc($request_result)) {

    // Get latest reschedule request
    $reschedule_stmt = mysqli_prepare($conn, "
        SELECT preferred_datetime, donor_reason, proposed_datetime, blood_bank_note, status
        FROM appointment_reschedule_requests
        WHERE appointment_id = ?
        ORDER BY id DESC
        LIMIT 1
    ");
    mysqli_stmt_bind_param($reschedule_stmt, "i", $row['id']);
    mysqli_stmt_execute($reschedule_stmt);
    $reschedule_result = mysqli_stmt_get_result($reschedule_stmt);
    $reschedule = mysqli_fetch_assoc($reschedule_result);
    mysqli_stmt_close($reschedule_stmt);

    $appointments[] = [
        'type' => 'request_based',
        'id' => $row['id'],
        'request_id' => $row['request_id'],
        'blood_group' => $row['blood_group'],
        'quantity' => $row['quantity'],
        'urgency' => $row['urgency'],
        'branch_name' => $row['branch_name'],
        'date' => $row['appointment_date'],
        'location' => buildFullLocation(
            $row['appointment_address'] ?? '',
            $row['appointment_city'] ?? '',
            $row['appointment_zipcode'] ?? '',
            $row['appointment_location'] ?? ''
        ),
        'address' => $row['appointment_address'] ?? '',
        'city' => $row['appointment_city'] ?? '',
        'zipcode' => $row['appointment_zipcode'] ?? '',
        'fallback_location' => $row['appointment_location'] ?? '',
        'notes' => $row['notes'] ?? '',
        'status' => $row['status'],
        'reschedule_status' => $reschedule['status'] ?? '',
        'preferred_datetime' => $reschedule['preferred_datetime'] ?? '',
        'donor_reason' => $reschedule['donor_reason'] ?? '',
        'proposed_datetime' => $reschedule['proposed_datetime'] ?? '',
        'blood_bank_note' => $reschedule['blood_bank_note'] ?? ''
    ];
}

// Build stock donation appointment list
while ($row = mysqli_fetch_assoc($stock_result)) {
    $appointments[] = [
        'type' => 'stock_donation',
        'id' => $row['id'],
        'request_id' => 'Stock',
        'blood_group' => $row['blood_group'],
        'quantity' => 1,
        'urgency' => 'normal',
        'branch_name' => $row['branch_name'],
        'date' => $row['scheduled_date'] ?: $row['preferred_date'],
        'location' => buildFullLocation(
            $row['address_line'] ?? '',
            $row['city'] ?? '',
            $row['zipcode'] ?? '',
            ''
        ),
        'address' => $row['address_line'] ?? '',
        'city' => $row['city'] ?? '',
        'zipcode' => $row['zipcode'] ?? '',
        'fallback_location' => '',
        'notes' => $row['notes'] ?? '',
        'status' => $row['status'],
        'reschedule_status' => '',
        'preferred_datetime' => $row['preferred_date'] ?? '',
        'donor_reason' => '',
        'proposed_datetime' => '',
        'blood_bank_note' => ''
    ];
}

mysqli_stmt_close($request_stmt);
mysqli_stmt_close($stock_stmt);

// Sort latest appointment first
usort($appointments, function ($a, $b) {
    return strtotime((string)$b['date']) <=> strtotime((string)$a['date']);
});
?>
<!DOCTYPE html>
<html>
<head>
    <title>Donor Appointments</title>
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
                    <span class="notify-badge"><?php echo (int)$notif_count; ?></span>
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
            <a href="donor_appointments.php" class="active">Appointments</a>
            <a href="donor_history.php">History</a>
            <a href="donor_profile.php">Profile</a>
            <a href="notifications.php">Notifications</a>
        </div>
    </div>
</div>

<div class="container">
    <div class="hero">
        <h1>My Appointments</h1>
        <p>Review request-based and stock-donation appointments, then confirm, decline, or request reschedule when required.</p>
    </div>

    <?php if (!$canConfirmAppointment) { ?>
        <div class="alert alert-error">
            You cannot confirm or request reschedule for any donation appointment right now.
            <?php echo htmlspecialchars($blockedConfirmMessage); ?>
        </div>
    <?php } ?>

    <?php if (isset($_GET['reschedule']) && $_GET['reschedule'] === 'requested') { ?>
        <div class="alert alert-success">Your reschedule request has been submitted successfully.</div>
    <?php } ?>

    <?php if (isset($_GET['reschedule']) && $_GET['reschedule'] === 'blocked') { ?>
        <div class="alert alert-error">Reschedule request blocked. You are not eligible to donate right now.</div>
    <?php } ?>

    <?php if (isset($_GET['confirm']) && $_GET['confirm'] === 'success') { ?>
        <div class="alert alert-success">Your donation appointment has been confirmed.</div>
    <?php } ?>

    <?php if (isset($_GET['confirm']) && $_GET['confirm'] === 'blocked') { ?>
        <div class="alert alert-error">Appointment confirmation blocked. You are not eligible to donate right now.</div>
    <?php } ?>

    <?php if (isset($_GET['stock']) && $_GET['stock'] === 'confirmed') { ?>
        <div class="alert alert-success">Your stock donation appointment has been confirmed.</div>
    <?php } ?>

    <?php if (isset($_GET['stock']) && $_GET['stock'] === 'declined') { ?>
        <div class="alert alert-success">Your stock donation appointment has been declined.</div>
    <?php } ?>

    <?php if (isset($_GET['stock']) && $_GET['stock'] === 'blocked') { ?>
        <div class="alert alert-error">Stock donation confirmation blocked. You are not eligible to donate right now.</div>
    <?php } ?>

    <div class="card">
        <h3>Appointment List</h3>

        <div class="table-scroll-admin">
            <?php if (!empty($appointments)) { ?>
                <table class="compact-admin-table">
                    <tr>
                        <th>Type</th>
                        <th class="small-cell">Request</th>
                        <th>Blood Group</th>
                        <th class="small-cell">Qty</th>
                        <th>Urgency</th>
                        <th class="wrap-cell">Branch</th>
                        <th class="date-cell">Date</th>
                        <th class="wrap-cell">Location</th>
                        <th>Map</th>
                        <th class="wrap-cell">Notes</th>
                        <th>Status</th>
                        <th>Reschedule</th>
                        <th>Action</th>
                    </tr>

                    <?php foreach ($appointments as $row) { ?>
                        <?php
                        // Status badge class
                        $badgeClass = "badge-accepted";

                        if (in_array($row['status'], ['scheduled', 'pending'])) {
                            $badgeClass = "badge-pending";
                        } elseif (in_array($row['status'], ['completed', 'confirmed'])) {
                            $badgeClass = "badge-completed";
                        } elseif (in_array($row['status'], ['declined', 'cancelled'])) {
                            $badgeClass = "badge-pending";
                        }

                        // Build map links
                        $mapUrl = buildMapUrl(
                            $row['address'] ?? '',
                            $row['city'] ?? '',
                            $row['zipcode'] ?? '',
                            $row['fallback_location'] ?? ''
                        );

                        $embedUrl = buildEmbedMapUrl(
                            $row['address'] ?? '',
                            $row['city'] ?? '',
                            $row['zipcode'] ?? '',
                            $row['fallback_location'] ?? ''
                        );

                        $mapId = "donorMap" . md5($row['type'] . '_' . $row['id']);

                        // Reschedule status display
                        $rescheduleHtml = '---';

                        if ($row['type'] === 'request_based') {
                            if ($row['reschedule_status'] === 'requested') {
                                $rescheduleHtml = '<div class="reschedule-simple-box">';
                                $rescheduleHtml .= '<span class="mini-badge mini-orange">Requested</span>';

                                if ($row['preferred_datetime'] !== '') {
                                    $rescheduleHtml .= '<div class="reschedule-date-text">Preferred: ' . htmlspecialchars($row['preferred_datetime']) . '</div>';
                                }

                                if ($row['donor_reason'] !== '') {
                                    $rescheduleHtml .= '<div class="reason-box-simple">' . nl2br(htmlspecialchars($row['donor_reason'])) . '</div>';
                                }

                                $rescheduleHtml .= '</div>';

                            } elseif ($row['reschedule_status'] === 'resolved') {
                                $rescheduleHtml = '<div class="reschedule-simple-box">';
                                $rescheduleHtml .= '<span class="mini-badge mini-green">Updated</span>';

                                if ($row['proposed_datetime'] !== '') {
                                    $rescheduleHtml .= '<div class="reschedule-date-text">New Slot: ' . htmlspecialchars($row['proposed_datetime']) . '</div>';
                                }

                                if ($row['blood_bank_note'] !== '') {
                                    $rescheduleHtml .= '<div class="reason-box-simple">' . nl2br(htmlspecialchars($row['blood_bank_note'])) . '</div>';
                                }

                                $rescheduleHtml .= '</div>';

                            } else {
                                $rescheduleHtml = '<span class="badge badge-pending">Available</span>';
                            }

                        } else {
                            if ($row['status'] === 'pending') {
                                $rescheduleHtml = '<div class="reschedule-simple-box">';
                                $rescheduleHtml .= '<span class="mini-badge mini-orange">Pending Review</span>';

                                if ($row['preferred_datetime'] !== '') {
                                    $rescheduleHtml .= '<div class="reschedule-date-text">Preferred: ' . htmlspecialchars($row['preferred_datetime']) . '</div>';
                                }

                                $rescheduleHtml .= '</div>';

                            } elseif ($row['status'] === 'confirmed') {
                                $rescheduleHtml = '<span class="mini-badge mini-green">Confirmed by Donor</span>';

                            } else {
                                $rescheduleHtml = '<span class="small-muted">---</span>';
                            }
                        }

                        // Action buttons
                        $actionHtml = '<span class="small-muted">---</span>';

                        if ($row['type'] === 'request_based' && $row['status'] === 'scheduled') {
                            $actionHtml = '<div class="appointment-action-group">';

                            // Confirm button is blocked if donor is not eligible
                            if ($canConfirmAppointment) {
                                $actionHtml .= '<a class="action-pill action-pill-confirm" href="confirm_appointment.php?id=' . (int)$row['id'] . '" onclick="return confirm(\'Confirm this appointment?\');">Confirm</a>';
                            } else {
                                $actionHtml .= '<span class="action-pill action-pill-disabled">Confirm Blocked</span>';
                            }

                            // Decline is still allowed so donor can cancel old appointment
                            $actionHtml .= '<a class="action-pill action-pill-decline" href="decline_appointment.php?id=' . (int)$row['id'] . '" onclick="return confirm(\'Decline this appointment?\');">Decline</a>';

                            // Reschedule is blocked if donor is not eligible
                            if ($row['reschedule_status'] !== 'requested') {
                                if ($canConfirmAppointment) {
                                    $actionHtml .= '<a class="action-pill action-pill-reschedule" href="donor_request_reschedule.php?id=' . (int)$row['id'] . '">Request Reschedule</a>';
                                } else {
                                    $actionHtml .= '<span class="action-pill action-pill-disabled">Reschedule Blocked</span>';
                                    $actionHtml .= '<div class="eligibility-block-note">' . $blockedConfirmMessageEsc . '</div>';
                                }
                            }

                            $actionHtml .= '</div>';

                        } elseif ($row['type'] === 'stock_donation' && $row['status'] === 'scheduled') {
                            $actionHtml = '<div class="appointment-action-group">';

                            // Confirm button is blocked if donor is not eligible
                            if ($canConfirmAppointment) {
                                $actionHtml .= '<a class="action-pill action-pill-confirm" href="confirm_stock_appointment.php?id=' . (int)$row['id'] . '" onclick="return confirm(\'Confirm this stock donation appointment?\');">Confirm</a>';
                            } else {
                                $actionHtml .= '<span class="action-pill action-pill-disabled">Confirm Blocked</span>';
                            }

                            // Decline is still allowed so donor can cancel old appointment
                            $actionHtml .= '<a class="action-pill action-pill-decline" href="decline_stock_appointment.php?id=' . (int)$row['id'] . '" onclick="return confirm(\'Decline this stock donation appointment?\');">Decline</a>';

                            // Stock reschedule is blocked if donor is not eligible
                            if ($canConfirmAppointment) {
                                $actionHtml .= '<a class="action-pill action-pill-reschedule" href="donor_stock_request_reschedule.php?id=' . (int)$row['id'] . '">Request Reschedule</a>';
                            } else {
                                $actionHtml .= '<span class="action-pill action-pill-disabled">Reschedule Blocked</span>';
                                $actionHtml .= '<div class="eligibility-block-note">' . $blockedConfirmMessageEsc . '</div>';
                            }

                            $actionHtml .= '</div>';
                        }
                        ?>

                        <tr class="<?php echo (($row['urgency'] ?? '') === 'urgent') ? 'urgent-row' : ''; ?>">
                            <td>
                                <?php if ($row['type'] === 'stock_donation') { ?>
                                    <span class="mini-badge mini-blue">Stock Donation</span>
                                <?php } else { ?>
                                    <span class="mini-badge mini-green">Request Donation</span>
                                <?php } ?>
                            </td>

                            <td class="small-cell"><?php echo htmlspecialchars((string)$row['request_id']); ?></td>
                            <td><?php echo htmlspecialchars($row['blood_group']); ?></td>
                            <td class="small-cell"><?php echo htmlspecialchars((string)$row['quantity']); ?></td>

                            <td>
                                <?php if (($row['urgency'] ?? '') === 'urgent') { ?>
                                    <span class="urgency-badge urgency-urgent">Urgent</span>
                                <?php } else { ?>
                                    <span class="urgency-badge urgency-normal">Normal</span>
                                <?php } ?>
                            </td>

                            <td class="wrap-cell"><?php echo htmlspecialchars($row['branch_name'] ?? 'Not assigned'); ?></td>
                            <td class="date-cell"><?php echo htmlspecialchars((string)$row['date']); ?></td>
                            <td class="wrap-cell"><?php echo htmlspecialchars($row['location']); ?></td>

                            <td>
                                <?php if ($embedUrl !== '') { ?>
                                    <div class="map-actions">
                                        <button type="button" class="map-toggle-btn" onclick="toggleMap('<?php echo $mapId; ?>', this)">Show Map</button>
                                        <a class="map-open-link" href="<?php echo htmlspecialchars($mapUrl); ?>" target="_blank">Open in Google Maps</a>
                                    </div>
                                    <div id="<?php echo $mapId; ?>" class="embedded-map-wrap">
                                        <iframe src="<?php echo htmlspecialchars($embedUrl); ?>" loading="lazy"></iframe>
                                    </div>
                                <?php } else { ?>
                                    ---
                                <?php } ?>
                            </td>

                            <td class="wrap-cell"><?php echo nl2br(htmlspecialchars($row['notes'])); ?></td>
                            <td><span class="badge <?php echo $badgeClass; ?>"><?php echo htmlspecialchars($row['status']); ?></span></td>
                            <td><?php echo $rescheduleHtml; ?></td>
                            <td><?php echo $actionHtml; ?></td>
                        </tr>
                    <?php } ?>
                </table>
            <?php } else { ?>
                <p>No appointments available.</p>
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
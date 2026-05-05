<?php
session_start();
include '../config/db.php';
include '../functions/log_activity.php';
include '../functions/network_helper.php';
include '../functions/location_helper.php';
include '../functions/eligibility_check.php';

// Check blood bank login
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'blood_bank'){
    header("Location: ../auth/login.php");
    exit();
}

$blood_bank_id = (int)$_SESSION['user_id'];

// Check stock donation request id
if(!isset($_GET['id']) || !is_numeric($_GET['id'])){
    header("Location: blood_bank_stock_requests.php");
    exit();
}

$request_id = (int)$_GET['id'];

// Get notification count
$notif_stmt = mysqli_prepare($conn, "SELECT COUNT(*) AS total FROM notifications WHERE user_id = ? AND is_read = 0");
mysqli_stmt_bind_param($notif_stmt, "i", $blood_bank_id);
mysqli_stmt_execute($notif_stmt);
$notif_result = mysqli_stmt_get_result($notif_stmt);
$notif_data = mysqli_fetch_assoc($notif_result);
$notif_count = $notif_data['total'] ?? 0;
mysqli_stmt_close($notif_stmt);

// Get stock donation request with donor and branch details
$stmt = mysqli_prepare($conn, "
    SELECT
        sdr.*,
        donor.name AS donor_name,
        donor.blood_group,
        donor.availability AS donor_availability,
        donor.account_status AS donor_account_status,
        donor.status AS donor_user_status,
        b.branch_name,
        b.address_line,
        b.city,
        b.zipcode
    FROM stock_donation_requests sdr
    JOIN users donor ON sdr.donor_id = donor.id
    LEFT JOIN branches b ON sdr.branch_id = b.id
    WHERE sdr.id = ? 
      AND sdr.blood_bank_user_id = ?
    LIMIT 1
");
mysqli_stmt_bind_param($stmt, "ii", $request_id, $blood_bank_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if(mysqli_num_rows($result) != 1){
    mysqli_stmt_close($stmt);
    header("Location: blood_bank_stock_requests.php");
    exit();
}

$request = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

$donor_id = (int)$request['donor_id'];

// Re-check donor eligibility before scheduling stock appointment
$donorEligibility = syncDonorAvailabilityStatus($conn, $donor_id);

// Check donor account status
$donorAccountActive = (
    ($request['donor_account_status'] ?? 'active') === 'active' &&
    ($request['donor_user_status'] ?? 'active') === 'active'
);

// Final permission for stock appointment scheduling
$canScheduleStock = (
    $donorAccountActive &&
    !empty($donorEligibility['eligible']) &&
    ($donorEligibility['availability'] ?? 'not_available') === 'available'
);

// Build block message
$donorBlockMessage = "";

if(!$donorAccountActive){
    $donorBlockMessage = "This donor account is inactive, so a stock donation slot cannot be scheduled.";
} elseif(!$canScheduleStock){
    $donorBlockMessage = $donorEligibility['reason'] ?? "This donor is currently not eligible or available for stock donation.";

    if(!empty($donorEligibility['next_eligible_date'])){
        $donorBlockMessage .= " Next eligible date: " . $donorEligibility['next_eligible_date'] . ".";
    }
}

// Check if donor requested reschedule
$hasDonorRescheduleRequest = (strpos((string)($request['notes'] ?? ''), '[Donor Reschedule Request]') !== false);

// Page title and button text
$pageTitle = $hasDonorRescheduleRequest ? 'Propose New Slot for Stock Donation' : 'Schedule Stock Donation Slot';
$buttonText = $hasDonorRescheduleRequest ? 'Propose New Slot' : 'Schedule Slot';

// Handle schedule/propose form
if(isset($_POST['schedule_slot'])){
    $scheduled_date = trim($_POST['scheduled_date'] ?? '');
    $notes = trim($_POST['notes'] ?? '');

    // Re-check donor eligibility again on submit
    $donorEligibility = syncDonorAvailabilityStatus($conn, $donor_id);

    $canScheduleStock = (
        $donorAccountActive &&
        !empty($donorEligibility['eligible']) &&
        ($donorEligibility['availability'] ?? 'not_available') === 'available'
    );

    if(!$canScheduleStock){
        $error = $donorBlockMessage !== "" ? $donorBlockMessage : "This donor is currently not eligible or available for stock donation.";
    } elseif($scheduled_date === ""){
        $error = "Scheduled date is required.";
    } elseif(in_array($request['status'], ['completed', 'cancelled'])){
        $error = "This stock donation request is already closed and cannot be scheduled again.";
    } else {

        // Merge old notes with new blood bank note
        $mergedNotes = trim(($request['notes'] ?? '') . "\n\n[Blood Bank Schedule Note]\n" . $notes);

        // Update stock donation request only if it belongs to this blood bank
        $update_stmt = mysqli_prepare($conn, "
            UPDATE stock_donation_requests
            SET scheduled_date = ?, notes = ?, status = 'scheduled'
            WHERE id = ?
              AND blood_bank_user_id = ?
              AND status IN ('pending', 'scheduled', 'confirmed')
        ");
        mysqli_stmt_bind_param($update_stmt, "ssii", $scheduled_date, $mergedNotes, $request_id, $blood_bank_id);
        mysqli_stmt_execute($update_stmt);

        if(mysqli_stmt_affected_rows($update_stmt) === 1){
            mysqli_stmt_close($update_stmt);

            // Save activity log
            logActivity(
                $conn,
                $blood_bank_id,
                'blood_bank',
                'schedule_stock_donation',
                "Blood bank scheduled or proposed a stock donation slot for stock request #{$request_id}."
            );

            // Notify donor
            if($hasDonorRescheduleRequest){
                $message_donor = "The blood bank proposed a new stock donation slot for request #{$request_id}: {$scheduled_date}.";
            } else {
                $message_donor = "A stock donation appointment has been scheduled for request #{$request_id} on {$scheduled_date}.";
            }

            $notif_stmt2 = mysqli_prepare($conn, "INSERT INTO notifications (user_id, message) VALUES (?, ?)");
            mysqli_stmt_bind_param($notif_stmt2, "is", $donor_id, $message_donor);
            mysqli_stmt_execute($notif_stmt2);
            mysqli_stmt_close($notif_stmt2);

            header("Location: blood_bank_stock_requests.php?scheduled=1");
            exit();

        } else {
            mysqli_stmt_close($update_stmt);
            $error = "Failed to save the stock donation slot. The request may already be closed.";
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
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
                <a href="notifications.php" class="notify" title="Notifications">
                    🔔
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
        <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
        <p>Set or update a stock donation appointment for the donor.</p>
    </div>

    <?php if(!$canScheduleStock){ ?>
        <div class="alert alert-error">
            Stock appointment scheduling is blocked.
            <?php echo htmlspecialchars($donorBlockMessage); ?>
        </div>
    <?php } ?>

    <div class="card">
        <h3>Stock Donation Request Summary</h3>
        <p>
            <strong>Request ID:</strong> <?php echo htmlspecialchars($request['id']); ?><br>
            <strong>Donor:</strong> <?php echo htmlspecialchars($request['donor_name']); ?><br>
            <strong>Blood Group:</strong> <?php echo htmlspecialchars($request['blood_group']); ?><br>
            <strong>Donor Availability:</strong> <?php echo htmlspecialchars($donorEligibility['availability'] ?? 'not_available'); ?><br>
            <strong>Preferred Date:</strong> <?php echo htmlspecialchars($request['preferred_date'] ?? ''); ?><br>
            <strong>Current Scheduled Date:</strong> <?php echo htmlspecialchars($request['scheduled_date'] ?? 'Not scheduled'); ?><br>
            <strong>Branch:</strong> <?php echo htmlspecialchars($request['branch_name'] ?? 'Not assigned'); ?><br>
            <strong>Location:</strong>
            <?php
                echo htmlspecialchars(
                    buildFullLocation(
                        $request['address_line'] ?? '',
                        $request['city'] ?? '',
                        $request['zipcode'] ?? '',
                        ''
                    )
                );
            ?>
        </p>
    </div>

    <div class="card">
        <h3><?php echo htmlspecialchars($buttonText); ?></h3>

        <?php if(isset($error)){ ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php } ?>

        <form method="POST" class="form">
            <label>Appointment Date & Time</label>
            <input
                class="input"
                type="datetime-local"
                name="scheduled_date"
                <?php echo !$canScheduleStock ? 'disabled' : ''; ?>
                required
            >

            <label>Note to Donor</label>
            <textarea
                class="input"
                name="notes"
                rows="4"
                placeholder="Optional note..."
                <?php echo !$canScheduleStock ? 'disabled' : ''; ?>
            ></textarea>

            <div class="form-actions-row">
                <button
                    class="btn btn-primary"
                    type="submit"
                    name="schedule_slot"
                    <?php echo !$canScheduleStock ? 'disabled' : ''; ?>
                >
                    <?php echo htmlspecialchars($buttonText); ?>
                </button>

                <a class="btn btn-secondary" href="blood_bank_stock_requests.php">Cancel</a>
            </div>
        </form>
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

</body>
</html>
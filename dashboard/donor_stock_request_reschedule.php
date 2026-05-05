<?php
session_start();
include '../config/db.php';
include '../functions/log_activity.php';
include '../functions/network_helper.php';
include '../functions/eligibility_check.php';

// Check donor login
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'donor'){
    header("Location: ../auth/login.php");
    exit();
}

$donor_id = (int)$_SESSION['user_id'];
$donor_name = $_SESSION['name'] ?? 'Donor';

// Notification count
$notif_stmt = mysqli_prepare($conn, "SELECT COUNT(*) AS total FROM notifications WHERE user_id = ? AND is_read = 0");
mysqli_stmt_bind_param($notif_stmt, "i", $donor_id);
mysqli_stmt_execute($notif_stmt);
$notif_result = mysqli_stmt_get_result($notif_stmt);
$notif_data = mysqli_fetch_assoc($notif_result);
$notif_count = $notif_data['total'] ?? 0;
mysqli_stmt_close($notif_stmt);

// Validate stock request id
if(!isset($_GET['id']) || !is_numeric($_GET['id'])){
    header("Location: donor_appointments.php");
    exit();
}

$stock_request_id = (int)$_GET['id'];

// Get stock donation request
$stmt = mysqli_prepare($conn, "
    SELECT sdr.*, u.blood_group, b.branch_name, b.address_line, b.city, b.zipcode
    FROM stock_donation_requests sdr
    JOIN users u ON sdr.donor_id = u.id
    LEFT JOIN branches b ON sdr.branch_id = b.id
    WHERE sdr.id = ? AND sdr.donor_id = ?
");
mysqli_stmt_bind_param($stmt, "ii", $stock_request_id, $donor_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if(mysqli_num_rows($result) != 1){
    mysqli_stmt_close($stmt);
    header("Location: donor_appointments.php");
    exit();
}

$request = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

// Only scheduled or confirmed stock request can ask reschedule
if(!in_array($request['status'], ['scheduled','confirmed'])){
    header("Location: donor_appointments.php");
    exit();
}

// Server-side eligibility block for direct URL access
$eligibility = syncDonorAvailabilityStatus($conn, $donor_id);
$canRequestReschedule = ($eligibility['eligible'] === true && ($eligibility['availability'] ?? '') === 'available');

if(!$canRequestReschedule){
    header("Location: donor_appointments.php?reschedule=blocked");
    exit();
}

// Submit stock reschedule request
if(isset($_POST['submit_stock_reschedule'])){
    $preferred_date = trim($_POST['preferred_date'] ?? '');
    $donor_reason = trim($_POST['donor_reason'] ?? '');

    // Recheck eligibility before saving form
    $eligibility = syncDonorAvailabilityStatus($conn, $donor_id);
    $canRequestReschedule = ($eligibility['eligible'] === true && ($eligibility['availability'] ?? '') === 'available');

    if(!$canRequestReschedule){
        header("Location: donor_appointments.php?reschedule=blocked");
        exit();
    }

    if($preferred_date === '' || $donor_reason === ''){
        $error = "Preferred new date and reason are required.";
    } else {
        $updated_notes = trim(($request['notes'] ?? '') . "\n\n[Donor Reschedule Request]\nReason: " . $donor_reason);

        // Return request to pending so blood bank can propose a new slot
        $update_stmt = mysqli_prepare($conn, "
            UPDATE stock_donation_requests
            SET preferred_date = ?, scheduled_date = NULL, notes = ?, status = 'pending'
            WHERE id = ? AND donor_id = ?
        ");
        mysqli_stmt_bind_param($update_stmt, "ssii", $preferred_date, $updated_notes, $stock_request_id, $donor_id);

        if(mysqli_stmt_execute($update_stmt)){
            mysqli_stmt_close($update_stmt);

            // Save donor activity
            logActivity(
                $conn,
                $donor_id,
                'donor',
                'request_stock_reschedule',
                "Donor {$donor_name} requested a new slot for stock donation request #{$stock_request_id}."
            );

            // Notify blood bank
            $message_bank = "A donor requested reschedule for stock donation request #{$stock_request_id}. Please review and schedule a new slot.";
            $notif_stmt2 = mysqli_prepare($conn, "INSERT INTO notifications (user_id, message) VALUES (?, ?)");
            mysqli_stmt_bind_param($notif_stmt2, "is", $request['blood_bank_user_id'], $message_bank);
            mysqli_stmt_execute($notif_stmt2);
            mysqli_stmt_close($notif_stmt2);

            header("Location: donor_appointments.php?reschedule=requested");
            exit();
        } else {
            $error = "Failed to submit stock reschedule request.";
            mysqli_stmt_close($update_stmt);
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Request Stock Donation Reschedule</title>
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
        <h1>Request Stock Donation Reschedule</h1>
        <p>Submit your preferred new slot for this stock donation appointment.</p>
    </div>

    <div class="card">
        <h3>Current Stock Donation Appointment</h3>
        <p>
            <strong>Request ID:</strong> <?php echo htmlspecialchars($request['id']); ?><br>
            <strong>Blood Group:</strong> <?php echo htmlspecialchars($request['blood_group']); ?><br>
            <strong>Branch:</strong> <?php echo htmlspecialchars($request['branch_name'] ?? 'Not assigned'); ?><br>
            <strong>Current Scheduled Date:</strong> <?php echo htmlspecialchars($request['scheduled_date'] ?? ''); ?><br>
            <strong>Location:</strong> <?php echo htmlspecialchars(trim(($request['address_line'] ?? '') . ', ' . ($request['city'] ?? '') . ', ' . ($request['zipcode'] ?? ''))); ?>
        </p>
    </div>

    <div class="card">
        <h3>Reschedule Request Form</h3>

        <?php if(isset($error)){ ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php } ?>

        <form method="POST" class="form">
            <label>Preferred New Date & Time</label>
            <input class="input" type="datetime-local" name="preferred_date" required>

            <label>Reason</label>
            <textarea class="input" name="donor_reason" rows="5" placeholder="Explain why you need a new slot..." required></textarea>

            <div class="form-actions-row">
                <button class="btn btn-primary" type="submit" name="submit_stock_reschedule">Submit Reschedule Request</button>
                <a class="btn btn-secondary" href="donor_appointments.php">Cancel</a>
            </div>
        </form>
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
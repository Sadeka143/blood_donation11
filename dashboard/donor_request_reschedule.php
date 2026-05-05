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

// Validate appointment id
if(!isset($_GET['id']) || !is_numeric($_GET['id'])){
    header("Location: donor_appointments.php");
    exit();
}

$appointment_id = (int)$_GET['id'];

// Get appointment details
$appointment_stmt = mysqli_prepare($conn, "
    SELECT 
        a.*,
        r.blood_group,
        r.quantity,
        r.urgency
    FROM appointments a
    JOIN blood_requests r ON a.request_id = r.id
    WHERE a.id = ? AND a.donor_id = ?
");
mysqli_stmt_bind_param($appointment_stmt, "ii", $appointment_id, $donor_id);
mysqli_stmt_execute($appointment_stmt);
$appointment_result = mysqli_stmt_get_result($appointment_stmt);

if(mysqli_num_rows($appointment_result) != 1){
    mysqli_stmt_close($appointment_stmt);
    header("Location: donor_appointments.php");
    exit();
}

$appointment = mysqli_fetch_assoc($appointment_result);
mysqli_stmt_close($appointment_stmt);

// Only scheduled appointment can be rescheduled by donor
if($appointment['status'] !== 'scheduled'){
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

// Prevent duplicate pending reschedule request
$open_check = mysqli_prepare($conn, "
    SELECT id 
    FROM appointment_reschedule_requests
    WHERE appointment_id = ? AND status = 'requested'
    LIMIT 1
");
mysqli_stmt_bind_param($open_check, "i", $appointment_id);
mysqli_stmt_execute($open_check);
$open_result = mysqli_stmt_get_result($open_check);
$has_open_request = mysqli_num_rows($open_result) > 0;
mysqli_stmt_close($open_check);

// Submit reschedule request
if(isset($_POST['submit_reschedule_request'])){
    $preferred_datetime = trim($_POST['preferred_datetime'] ?? '');
    $donor_reason = trim($_POST['donor_reason'] ?? '');

    // Recheck eligibility before saving form
    $eligibility = syncDonorAvailabilityStatus($conn, $donor_id);
    $canRequestReschedule = ($eligibility['eligible'] === true && ($eligibility['availability'] ?? '') === 'available');

    if(!$canRequestReschedule){
        header("Location: donor_appointments.php?reschedule=blocked");
        exit();
    }

    if($has_open_request){
        $error = "A reschedule request is already pending for this appointment.";
    } elseif($preferred_datetime === "" || $donor_reason === ""){
        $error = "Preferred new date and reason are required.";
    } else {
        $insert_stmt = mysqli_prepare($conn, "
            INSERT INTO appointment_reschedule_requests
            (appointment_id, preferred_datetime, donor_reason, status)
            VALUES (?, ?, ?, 'requested')
        ");
        mysqli_stmt_bind_param($insert_stmt, "iss", $appointment_id, $preferred_datetime, $donor_reason);

        if(mysqli_stmt_execute($insert_stmt)){
            mysqli_stmt_close($insert_stmt);

            // Save donor activity
            logActivity(
                $conn,
                $donor_id,
                'donor',
                'request_reschedule_appointment',
                "Donor {$donor_name} requested reschedule for appointment #{$appointment_id}."
            );

            // Notify blood bank
            $message_bank = "A donor requested reschedule for appointment #{$appointment_id}. Please review and propose a new slot.";
            $notif_stmt1 = mysqli_prepare($conn, "INSERT INTO notifications (user_id, message) VALUES (?, ?)");
            mysqli_stmt_bind_param($notif_stmt1, "is", $appointment['blood_bank_id'], $message_bank);
            mysqli_stmt_execute($notif_stmt1);
            mysqli_stmt_close($notif_stmt1);

            // Notify recipient
            $message_recipient = "The donor requested a reschedule for your blood donation appointment. The blood bank will review it.";
            $notif_stmt2 = mysqli_prepare($conn, "INSERT INTO notifications (user_id, message) VALUES (?, ?)");
            mysqli_stmt_bind_param($notif_stmt2, "is", $appointment['recipient_id'], $message_recipient);
            mysqli_stmt_execute($notif_stmt2);
            mysqli_stmt_close($notif_stmt2);

            header("Location: donor_appointments.php?reschedule=requested");
            exit();
        } else {
            $error = "Failed to submit reschedule request.";
            mysqli_stmt_close($insert_stmt);
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Request Reschedule</title>
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
        <h1>Request Appointment Reschedule</h1>
        <p>Submit your preferred new appointment slot and explain why you need a reschedule.</p>
    </div>

    <div class="card">
        <h3>Current Appointment Summary</h3>
        <p>
            <strong>Appointment ID:</strong> <?php echo htmlspecialchars($appointment['id']); ?><br>
            <strong>Request ID:</strong> <?php echo htmlspecialchars($appointment['request_id']); ?><br>
            <strong>Blood Group:</strong> <?php echo htmlspecialchars($appointment['blood_group']); ?><br>
            <strong>Quantity:</strong> <?php echo htmlspecialchars($appointment['quantity']); ?><br>
            <strong>Urgency:</strong> <?php echo htmlspecialchars($appointment['urgency']); ?><br>
            <strong>Current Date:</strong> <?php echo htmlspecialchars($appointment['appointment_date']); ?><br>
            <strong>Current Location:</strong> <?php echo htmlspecialchars($appointment['appointment_location']); ?>
        </p>
    </div>

    <div class="card">
        <h3>Reschedule Request Form</h3>

        <?php if(isset($error)){ ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php } ?>

        <?php if($has_open_request){ ?>
            <div class="alert alert-error">A reschedule request is already pending for this appointment.</div>
            <a class="btn btn-secondary" href="donor_appointments.php">Back to Appointments</a>
        <?php } else { ?>
            <form method="POST" class="form">
                <label>Preferred New Date & Time</label>
                <input class="input" type="datetime-local" name="preferred_datetime" required>

                <label>Reason</label>
                <textarea class="input" name="donor_reason" rows="5" placeholder="Explain why you need to reschedule..." required></textarea>

                <div class="form-actions-row">
                    <button class="btn btn-primary" type="submit" name="submit_reschedule_request">Submit Reschedule Request</button>
                    <a class="btn btn-secondary" href="donor_appointments.php">Cancel</a>
                </div>
            </form>
        <?php } ?>
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
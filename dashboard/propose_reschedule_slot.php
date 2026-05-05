<?php
session_start();
include '../config/db.php';
include '../functions/log_activity.php';
include '../functions/network_helper.php';

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'blood_bank'){
    header("Location: ../auth/login.php");
    exit();
}

$blood_bank_id = $_SESSION['user_id'];

if(!isset($_GET['id']) || !is_numeric($_GET['id'])){
    header("Location: blood_bank_scheduled.php");
    exit();
}

$appointment_id = (int)$_GET['id'];

$sql = "
    SELECT 
        a.*,
        donor.name AS donor_name,
        recipient.name AS recipient_name,
        r.blood_group,
        arr.id AS reschedule_id,
        arr.preferred_datetime,
        arr.donor_reason,
        arr.proposed_datetime,
        arr.blood_bank_note,
        arr.status AS reschedule_status
    FROM appointments a
    JOIN users donor ON a.donor_id = donor.id
    JOIN users recipient ON a.recipient_id = recipient.id
    JOIN blood_requests r ON a.request_id = r.id
    LEFT JOIN appointment_reschedule_requests arr ON arr.id = (
        SELECT id
        FROM appointment_reschedule_requests
        WHERE appointment_id = a.id
        ORDER BY id DESC
        LIMIT 1
    )
    WHERE a.id = ? AND a.blood_bank_id = ?
";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ii", $appointment_id, $blood_bank_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if(mysqli_num_rows($result) != 1){
    mysqli_stmt_close($stmt);
    header("Location: blood_bank_scheduled.php");
    exit();
}

$data = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if(($data['reschedule_status'] ?? '') !== 'requested'){
    header("Location: blood_bank_scheduled.php");
    exit();
}

if(isset($_POST['submit_new_slot'])){
    $proposed_datetime = trim($_POST['proposed_datetime'] ?? '');
    $blood_bank_note = trim($_POST['blood_bank_note'] ?? '');

    if($proposed_datetime === ''){
        $error = "New slot date and time is required.";
    } else {
        mysqli_begin_transaction($conn);

        try {
            $update_req = mysqli_prepare($conn, "
                UPDATE appointment_reschedule_requests
                SET proposed_datetime = ?, blood_bank_note = ?, status = 'resolved'
                WHERE id = ?
            ");
            mysqli_stmt_bind_param($update_req, "ssi", $proposed_datetime, $blood_bank_note, $data['reschedule_id']);
            mysqli_stmt_execute($update_req);
            mysqli_stmt_close($update_req);

            $update_appointment = mysqli_prepare($conn, "
                UPDATE appointments
                SET appointment_date = ?
                WHERE id = ? AND blood_bank_id = ?
            ");
            mysqli_stmt_bind_param($update_appointment, "sii", $proposed_datetime, $appointment_id, $blood_bank_id);
            mysqli_stmt_execute($update_appointment);
            mysqli_stmt_close($update_appointment);

            logActivity(
                $conn,
                $blood_bank_id,
                'blood_bank',
                'propose_reschedule_slot',
                "Blood bank proposed a new slot for appointment #{$appointment_id}."
            );

            $message_donor = "The blood bank proposed a new slot for your appointment #{$appointment_id}: {$proposed_datetime}.";
            if($blood_bank_note !== ''){
                $message_donor .= " Note: {$blood_bank_note}";
            }
            $notif1 = mysqli_prepare($conn, "INSERT INTO notifications (user_id, message) VALUES (?, ?)");
            mysqli_stmt_bind_param($notif1, "is", $data['donor_id'], $message_donor);
            mysqli_stmt_execute($notif1);
            mysqli_stmt_close($notif1);

            $message_recipient = "A new appointment slot has been proposed for request #{$data['request_id']}: {$proposed_datetime}.";
            $notif2 = mysqli_prepare($conn, "INSERT INTO notifications (user_id, message) VALUES (?, ?)");
            mysqli_stmt_bind_param($notif2, "is", $data['recipient_id'], $message_recipient);
            mysqli_stmt_execute($notif2);
            mysqli_stmt_close($notif2);

            mysqli_commit($conn);
            header("Location: blood_bank_scheduled.php?updated=1");
            exit();
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $error = "Failed to propose a new slot.";
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Propose New Slot</title>
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
                <span>Welcome, <?php echo htmlspecialchars(getBloodBankNetworkName()); ?> (Network Control)</span>
                <a class="btn btn-logout" href="../auth/logout.php">Logout</a>
            </div>
        </div>

        <div class="topbar-menu">
            <a href="blood_bank_dashboard.php">Dashboard</a>
            <a href="blood_bank_pending.php">Pending</a>
            <a href="blood_bank_approved.php">Approved</a>
            <a href="blood_bank_matched.php">Matched</a>
            <a href="blood_bank_scheduled.php" class="active">Scheduled</a>
            <a href="blood_bank_confirmed.php">Confirmed</a>
            <a href="blood_bank_history.php">History</a>
            <a href="notifications.php">Notifications</a>
        </div>
    </div>
</div>

<div class="container">
    <div class="hero">
        <h1>Propose New Slot</h1>
        <p>Review donor reschedule request and propose a new appointment slot.</p>
    </div>

    <div class="card">
        <h3>Reschedule Request Summary</h3>
        <p>
            <strong>Appointment ID:</strong> <?php echo htmlspecialchars($data['id']); ?><br>
            <strong>Recipient:</strong> <?php echo htmlspecialchars($data['recipient_name']); ?><br>
            <strong>Donor:</strong> <?php echo htmlspecialchars($data['donor_name']); ?><br>
            <strong>Blood Group:</strong> <?php echo htmlspecialchars($data['blood_group']); ?><br>
            <strong>Current Date:</strong> <?php echo htmlspecialchars($data['appointment_date']); ?><br>
            <strong>Preferred Date by Donor:</strong> <?php echo htmlspecialchars($data['preferred_datetime']); ?><br>
            <strong>Reason:</strong> <?php echo nl2br(htmlspecialchars($data['donor_reason'])); ?>
        </p>
    </div>

    <div class="card">
        <h3>New Slot Proposal</h3>

        <?php if(isset($error)){ ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php } ?>

        <form method="POST" class="form">
            <label>Proposed New Date & Time</label>
            <input class="input" type="datetime-local" name="proposed_datetime" required>

            <label>Note to Donor / Recipient</label>
            <textarea class="input" name="blood_bank_note" rows="4" placeholder="Optional note..."></textarea>

            <div class="form-actions-row">
                <button class="btn btn-primary" type="submit" name="submit_new_slot">Propose New Slot</button>
                <a class="btn btn-secondary" href="blood_bank_scheduled.php">Cancel</a>
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
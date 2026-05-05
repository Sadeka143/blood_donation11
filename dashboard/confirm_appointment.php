<?php
session_start();
include '../config/db.php';
include '../functions/log_activity.php';
include '../functions/eligibility_check.php';

// Only donor can confirm own appointment
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'donor'){
    header("Location: ../auth/login.php");
    exit();
}

// Validate appointment id
if(!isset($_GET['id']) || !is_numeric($_GET['id'])){
    header("Location: donor_appointments.php");
    exit();
}

$appointment_id = (int)$_GET['id'];
$donor_id = (int)$_SESSION['user_id'];
$donor_name = $_SESSION['name'] ?? 'Donor';

// Get appointment and make sure it belongs to logged-in donor
$stmt = mysqli_prepare($conn, "
    SELECT id, request_id, donor_id, blood_bank_id, status
    FROM appointments
    WHERE id = ? AND donor_id = ?
    LIMIT 1
");
mysqli_stmt_bind_param($stmt, "ii", $appointment_id, $donor_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if(mysqli_num_rows($result) !== 1){
    mysqli_stmt_close($stmt);
    header("Location: donor_appointments.php");
    exit();
}

$appointment = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

// Only scheduled appointments can be confirmed
if($appointment['status'] !== 'scheduled'){
    header("Location: donor_appointments.php");
    exit();
}

// Server-side eligibility block to prevent direct URL confirmation
$eligibility = syncDonorAvailabilityStatus($conn, $donor_id);
$canConfirm = ($eligibility['eligible'] === true && ($eligibility['availability'] ?? '') === 'available');

if(!$canConfirm){
    header("Location: donor_appointments.php?confirm=blocked");
    exit();
}

// Confirm appointment
$update_stmt = mysqli_prepare($conn, "
    UPDATE appointments
    SET status = 'confirmed'
    WHERE id = ? AND donor_id = ? AND status = 'scheduled'
");
mysqli_stmt_bind_param($update_stmt, "ii", $appointment_id, $donor_id);
mysqli_stmt_execute($update_stmt);
$updated = mysqli_stmt_affected_rows($update_stmt);
mysqli_stmt_close($update_stmt);

if($updated === 1){
    // Save donor activity log
    logActivity(
        $conn,
        $donor_id,
        'donor',
        'confirm_appointment',
        "Donor {$donor_name} confirmed appointment #{$appointment_id} for request #{$appointment['request_id']}."
    );

    // Notify blood bank
    $blood_bank_id = (int)$appointment['blood_bank_id'];
    $message = "A donor has confirmed the scheduled donation appointment for request #{$appointment['request_id']}.";

    $notif_stmt = mysqli_prepare($conn, "INSERT INTO notifications (user_id, message) VALUES (?, ?)");
    mysqli_stmt_bind_param($notif_stmt, "is", $blood_bank_id, $message);
    mysqli_stmt_execute($notif_stmt);
    mysqli_stmt_close($notif_stmt);
}

header("Location: donor_appointments.php?confirm=success");
exit();
?>
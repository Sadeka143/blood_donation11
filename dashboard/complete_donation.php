<?php
session_start();

include '../config/db.php';
include '../functions/log_activity.php';
include '../functions/eligibility_check.php';

// Check blood bank login
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'blood_bank'){
    header("Location: ../auth/login.php");
    exit();
}

// Validate appointment id
if(!isset($_GET['id']) || !is_numeric($_GET['id'])){
    header("Location: blood_bank_confirmed.php?result=invalid");
    exit();
}

$appointment_id = (int)$_GET['id'];
$blood_bank_id = (int)$_SESSION['user_id'];
$blood_bank_name = $_SESSION['name'] ?? 'Central Blood Bank Network';

// Get appointment only if it belongs to logged-in blood bank
$stmt = mysqli_prepare($conn, "
    SELECT 
        a.*,
        r.id AS request_id,
        r.status AS request_status,
        d.name AS donor_name,
        d.blood_group AS donor_blood_group,
        b.branch_name
    FROM appointments a
    JOIN blood_requests r ON a.request_id = r.id
    JOIN users d ON a.donor_id = d.id
    LEFT JOIN branches b ON a.branch_id = b.id
    WHERE a.id = ? AND a.blood_bank_id = ?
    LIMIT 1
");
mysqli_stmt_bind_param($stmt, "ii", $appointment_id, $blood_bank_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if(mysqli_num_rows($result) !== 1){
    mysqli_stmt_close($stmt);
    header("Location: blood_bank_confirmed.php?result=invalid");
    exit();
}

$appointment = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

// Only confirmed appointment can be completed
if($appointment['status'] !== 'confirmed'){
    header("Location: blood_bank_confirmed.php?result=invalid");
    exit();
}

$request_id = (int)$appointment['request_id'];
$donor_id = (int)$appointment['donor_id'];
$recipient_id = (int)$appointment['recipient_id'];
$appointment_date = $appointment['appointment_date'];
$notes = $appointment['notes'] ?? '';
$branch_id = !empty($appointment['branch_id']) ? (int)$appointment['branch_id'] : null;
$branch_name = $appointment['branch_name'] ?? 'selected branch';

$donation_date = date('Y-m-d');
$completed_at = date('Y-m-d H:i:s');

mysqli_begin_transaction($conn);

try {

    // Mark appointment as completed with ownership check
    $update_appointment = mysqli_prepare($conn, "
        UPDATE appointments
        SET status = 'completed'
        WHERE id = ? AND blood_bank_id = ? AND status = 'confirmed'
    ");
    mysqli_stmt_bind_param($update_appointment, "ii", $appointment_id, $blood_bank_id);
    mysqli_stmt_execute($update_appointment);

    if(mysqli_stmt_affected_rows($update_appointment) !== 1){
        mysqli_stmt_close($update_appointment);
        throw new Exception("Appointment could not be completed.");
    }

    mysqli_stmt_close($update_appointment);

    // Mark linked blood request as completed
    $update_request = mysqli_prepare($conn, "
        UPDATE blood_requests
        SET status = 'completed'
        WHERE id = ?
    ");
    mysqli_stmt_bind_param($update_request, "i", $request_id);
    mysqli_stmt_execute($update_request);
    mysqli_stmt_close($update_request);

    // Insert request-based donation history
    $insert_donation = mysqli_prepare($conn, "
        INSERT INTO donations
            (donor_id, blood_bank_id, request_id, donation_date, scheduled_date, completed_at, notes, status, branch_id, donation_type)
        VALUES
            (?, ?, ?, ?, ?, ?, ?, 'confirmed', ?, 'request_based')
    ");

    mysqli_stmt_bind_param(
        $insert_donation,
        "iiissssi",
        $donor_id,
        $blood_bank_id,
        $request_id,
        $donation_date,
        $appointment_date,
        $completed_at,
        $notes,
        $branch_id
    );

    mysqli_stmt_execute($insert_donation);

    if(mysqli_stmt_affected_rows($insert_donation) !== 1){
        mysqli_stmt_close($insert_donation);
        throw new Exception("Donation history could not be saved.");
    }

    mysqli_stmt_close($insert_donation);

    // Update donor availability and next eligible date
    $eligibility = syncDonorAvailabilityStatus($conn, $donor_id);

    // Save activity log
    logActivity(
        $conn,
        $blood_bank_id,
        'blood_bank',
        'complete_donation',
        "Blood bank {$blood_bank_name} completed appointment #{$appointment_id} for request #{$request_id} with donor {$appointment['donor_name']}."
    );

    // Notify donor
    $message_donor = "Your blood donation for request #{$request_id} has been marked as completed. You are temporarily unavailable for donation.";

    if(!empty($eligibility['next_eligible_date'])){
        $message_donor .= " Next Eligible Date: " . $eligibility['next_eligible_date'] . ".";
    }

    $notif_stmt1 = mysqli_prepare($conn, "INSERT INTO notifications (user_id, message) VALUES (?, ?)");
    mysqli_stmt_bind_param($notif_stmt1, "is", $donor_id, $message_donor);
    mysqli_stmt_execute($notif_stmt1);
    mysqli_stmt_close($notif_stmt1);

    // Notify recipient
    $message_recipient = "Your blood request #{$request_id} has been fulfilled and marked as completed by the blood bank.";

    $notif_stmt2 = mysqli_prepare($conn, "INSERT INTO notifications (user_id, message) VALUES (?, ?)");
    mysqli_stmt_bind_param($notif_stmt2, "is", $recipient_id, $message_recipient);
    mysqli_stmt_execute($notif_stmt2);
    mysqli_stmt_close($notif_stmt2);

    mysqli_commit($conn);

    header(
        "Location: blood_bank_confirmed.php?result=completed" .
        "&request_id=" . urlencode((string)$request_id) .
        "&branch=" . urlencode($branch_name)
    );
    exit();

} catch (Exception $e) {

    // Rollback if any step fails
    mysqli_rollback($conn);

    header("Location: blood_bank_confirmed.php?result=error");
    exit();
}
?>
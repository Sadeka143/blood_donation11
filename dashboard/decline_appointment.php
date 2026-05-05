<?php
session_start();
include '../config/db.php';
include '../functions/log_activity.php';

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'donor'){
    header("Location: ../auth/login.php");
    exit();
}

if(!isset($_GET['id'])){
    header("Location: donor_appointments.php");
    exit();
}

$appointment_id = (int) $_GET['id'];
$donor_id = $_SESSION['user_id'];
$donor_name = $_SESSION['name'];

/* Get appointment */
$stmt = mysqli_prepare($conn, "SELECT request_id, donor_id, blood_bank_id, recipient_id, status FROM appointments WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $appointment_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if(mysqli_num_rows($result) != 1){
    header("Location: donor_appointments.php");
    exit();
}

$appointment = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if($appointment['donor_id'] != $donor_id){
    header("Location: donor_appointments.php");
    exit();
}

if($appointment['status'] == 'scheduled'){
    $request_id = $appointment['request_id'];
    $blood_bank_id = $appointment['blood_bank_id'];
    $recipient_id = $appointment['recipient_id'];

    /* Mark appointment declined */
    $decline_stmt = mysqli_prepare($conn, "UPDATE appointments SET status = 'declined' WHERE id = ?");
    mysqli_stmt_bind_param($decline_stmt, "i", $appointment_id);
    mysqli_stmt_execute($decline_stmt);
    mysqli_stmt_close($decline_stmt);

    /* Re-open request for new donor matching */
    $request_stmt = mysqli_prepare($conn, "UPDATE blood_requests SET status = 'approved', matched_donor_id = NULL WHERE id = ?");
    mysqli_stmt_bind_param($request_stmt, "i", $request_id);
    mysqli_stmt_execute($request_stmt);
    mysqli_stmt_close($request_stmt);

    /* Activity log */
    logActivity(
        $conn,
        $donor_id,
        'donor',
        'decline_appointment',
        "Donor {$donor_name} declined appointment #{$appointment_id} for request #{$request_id}."
    );

    /* Notify blood bank */
    $message_bank = "A donor has declined the scheduled appointment. Please assign another donor.";
    $notif_stmt1 = mysqli_prepare($conn, "INSERT INTO notifications (user_id, message) VALUES (?, ?)");
    mysqli_stmt_bind_param($notif_stmt1, "is", $blood_bank_id, $message_bank);
    mysqli_stmt_execute($notif_stmt1);
    mysqli_stmt_close($notif_stmt1);

    /* Notify recipient */
    $message_recipient = "The previously matched donor declined the appointment. The blood bank will arrange another donor.";
    $notif_stmt2 = mysqli_prepare($conn, "INSERT INTO notifications (user_id, message) VALUES (?, ?)");
    mysqli_stmt_bind_param($notif_stmt2, "is", $recipient_id, $message_recipient);
    mysqli_stmt_execute($notif_stmt2);
    mysqli_stmt_close($notif_stmt2);
}

header("Location: donor_appointments.php");
exit();
?>
<?php
session_start();
include '../config/db.php';

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin'){
    header("Location: ../auth/login.php");
    exit();
}

if(!isset($_GET['id'])){
    header("Location: admin_dashboard.php");
    exit();
}

$request_id = (int) $_GET['id'];

/* Get recipient_id and donor_id for this request */
$get_stmt = mysqli_prepare($conn, "
    SELECT br.recipient_id, d.donor_id
    FROM blood_requests br
    LEFT JOIN donations d ON br.id = d.request_id
    WHERE br.id = ? AND br.status = 'accepted'
    ORDER BY d.id DESC
    LIMIT 1
");
mysqli_stmt_bind_param($get_stmt, "i", $request_id);
mysqli_stmt_execute($get_stmt);
$get_result = mysqli_stmt_get_result($get_stmt);

if(mysqli_num_rows($get_result) == 1){
    $data = mysqli_fetch_assoc($get_result);
    $recipient_id = $data['recipient_id'];
    $donor_id = $data['donor_id'];

    /* Mark completed */
    $stmt = mysqli_prepare($conn, "UPDATE blood_requests SET status='completed' WHERE id = ? AND status='accepted'");
    mysqli_stmt_bind_param($stmt, "i", $request_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    /* Notify recipient */
    $message_recipient = "Your blood request has been marked as completed by the admin.";
    $notif_stmt1 = mysqli_prepare($conn, "INSERT INTO notifications (user_id, message) VALUES (?, ?)");
    mysqli_stmt_bind_param($notif_stmt1, "is", $recipient_id, $message_recipient);
    mysqli_stmt_execute($notif_stmt1);
    mysqli_stmt_close($notif_stmt1);

    /* Notify donor if donor exists */
    if(!empty($donor_id)){
        $message_donor = "A blood request you responded to has been marked as completed by the admin.";
        $notif_stmt2 = mysqli_prepare($conn, "INSERT INTO notifications (user_id, message) VALUES (?, ?)");
        mysqli_stmt_bind_param($notif_stmt2, "is", $donor_id, $message_donor);
        mysqli_stmt_execute($notif_stmt2);
        mysqli_stmt_close($notif_stmt2);
    }
}

mysqli_stmt_close($get_stmt);

header("Location: admin_dashboard.php");
exit();
?>
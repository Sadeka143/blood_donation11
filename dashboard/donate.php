<?php
session_start();
include '../config/db.php';

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'donor'){
    header("Location: ../auth/login.php");
    exit();
}

if(!isset($_GET['id'])){
    header("Location: donor_dashboard.php");
    exit();
}

$donor_id = (int) $_SESSION['user_id'];
$request_id = (int) $_GET['id'];

$success = "";
$error = "";

/* Check request is pending + get recipient_id */
$reqStmt = mysqli_prepare($conn, "SELECT id, status, recipient_id FROM blood_requests WHERE id = ?");
mysqli_stmt_bind_param($reqStmt, "i", $request_id);
mysqli_stmt_execute($reqStmt);
$reqRes = mysqli_stmt_get_result($reqStmt);

if(mysqli_num_rows($reqRes) != 1){
    $error = "Request not found.";
} else {
    $req = mysqli_fetch_assoc($reqRes);
    $recipient_id = $req['recipient_id'];

    if($req['status'] != 'pending'){
        $error = "This request is not available (already accepted or completed).";
    } else {

        $dupStmt = mysqli_prepare($conn, "SELECT id FROM donations WHERE donor_id = ? AND request_id = ?");
        mysqli_stmt_bind_param($dupStmt, "ii", $donor_id, $request_id);
        mysqli_stmt_execute($dupStmt);
        $dupRes = mysqli_stmt_get_result($dupStmt);

        if(mysqli_num_rows($dupRes) > 0){
            $error = "You have already donated for this request.";
        } else {

            $insStmt = mysqli_prepare($conn, "INSERT INTO donations (donor_id, request_id, donation_date) VALUES (?,?,CURDATE())");
            mysqli_stmt_bind_param($insStmt, "ii", $donor_id, $request_id);

            if(mysqli_stmt_execute($insStmt)){

                $upStmt = mysqli_prepare($conn, "UPDATE blood_requests SET status='accepted' WHERE id = ? AND status='pending'");
                mysqli_stmt_bind_param($upStmt, "i", $request_id);
                mysqli_stmt_execute($upStmt);
                mysqli_stmt_close($upStmt);

                /* Notify recipient */
                $message_recipient = "Your blood request has been accepted by a donor.";
                $notif_stmt1 = mysqli_prepare($conn, "INSERT INTO notifications (user_id, message) VALUES (?, ?)");
                mysqli_stmt_bind_param($notif_stmt1, "is", $recipient_id, $message_recipient);
                mysqli_stmt_execute($notif_stmt1);
                mysqli_stmt_close($notif_stmt1);

                /* Notify donor نفسه */
                $message_donor = "Your donation response has been recorded successfully.";
                $notif_stmt2 = mysqli_prepare($conn, "INSERT INTO notifications (user_id, message) VALUES (?, ?)");
                mysqli_stmt_bind_param($notif_stmt2, "is", $donor_id, $message_donor);
                mysqli_stmt_execute($notif_stmt2);
                mysqli_stmt_close($notif_stmt2);

                /* Notify admins */
                $admins = mysqli_query($conn, "SELECT id FROM users WHERE role='admin'");
                while($admin = mysqli_fetch_assoc($admins)){
                    $admin_id = $admin['id'];
                    $admin_message = "A donor has responded to a blood request.";
                    $admin_notif = mysqli_prepare($conn, "INSERT INTO notifications (user_id, message) VALUES (?, ?)");
                    mysqli_stmt_bind_param($admin_notif, "is", $admin_id, $admin_message);
                    mysqli_stmt_execute($admin_notif);
                    mysqli_stmt_close($admin_notif);
                }

                $success = "Donation confirmed successfully! Thank you for donating.";

            } else {
                $error = "Something went wrong: " . mysqli_error($conn);
            }

            mysqli_stmt_close($insStmt);
        }

        mysqli_stmt_close($dupStmt);
    }
}

mysqli_stmt_close($reqStmt);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Donate</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>

<div class="container">

    <div class="navbar">
        <div class="brand">
            <div class="logo">🩸</div>
            <div class="brand-text">
                <h2>Blood Donation Management System</h2>
                <p>Donate Blood, Save Lives</p>
            </div>
        </div>

        <div class="right">
            <span><?php echo htmlspecialchars($_SESSION['name']); ?> (Donor)</span>
            <a class="btn btn-logout" href="../auth/logout.php">Logout</a>
        </div>
    </div>

    <div class="card">
        <h3>Donation Confirmation</h3>

        <?php
        if($success) echo "<div class='alert alert-success'>$success</div>";
        if($error) echo "<div class='alert alert-error'>$error</div>";
        ?>

        <p><a class="link" href="donor_dashboard.php">← Back to Donor Dashboard</a></p>
        <p><a class="link" href="donor_history.php">View Donation History</a></p>
    </div>

</div>

</body>
</html>
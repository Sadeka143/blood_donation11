<?php
session_start();
if(!isset($_SESSION['user_id'])){
    header("Location: ../auth/login.php");
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Terms and Conditions</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="container">

    <div class="card">
        <h3>Terms and Conditions</h3>
        <p><a class="link" href="javascript:history.back()">← Back</a></p>

        <p>This system is a simulated academic prototype developed for final year project purposes.</p>
        <p>It is not connected to real hospitals, blood banks, or live donation services.</p>
        <p>All data, workflows, and records shown inside the system are for demonstration, testing, and educational use only.</p>
        <p>Users should not rely on this prototype for real medical decision-making or emergency blood arrangements.</p>
    </div>

</div>
</body>
</html>
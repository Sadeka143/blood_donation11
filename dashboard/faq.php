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
    <title>FAQ</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="container">

    <div class="card">
        <h3>Frequently Asked Questions (FAQ)</h3>
        <p><a class="link" href="javascript:history.back()">← Back</a></p>

        <p><strong>1. Is this system connected to real blood banks?</strong><br>
        No. This is a simulated academic prototype.</p>

        <p><strong>2. Who verifies blood requests?</strong><br>
        Blood bank users inside the system review and verify requests.</p>

        <p><strong>3. How are donors matched?</strong><br>
        Matching is based on blood compatibility and donor eligibility rules.</p>

        <p><strong>4. Can this system be used for real emergency blood requests?</strong><br>
        No. It is designed for project demonstration only.</p>
    </div>

</div>
</body>
</html>
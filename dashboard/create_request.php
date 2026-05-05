<?php
# ===================================
# Recipient create request controller
# ===================================
session_start();
include '../config/db.php';
include '../functions/log_activity.php';
include '../functions/network_helper.php';

# --------------------------------
# Secure access: only recipient role
# --------------------------------
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'recipient'){
    header("Location: ../auth/login.php");
    exit();
}

# --------------------------
# Logged-in recipient details
# --------------------------
$recipient_id = $_SESSION['user_id'];
$recipient_name = $_SESSION['name'];

# ------------------------------------
# Notification badge count for topbar
# ------------------------------------
$notif_stmt = mysqli_prepare($conn, "SELECT COUNT(*) AS total FROM notifications WHERE user_id = ? AND is_read = 0");
mysqli_stmt_bind_param($notif_stmt, "i", $recipient_id);
mysqli_stmt_execute($notif_stmt);
$notif_result = mysqli_stmt_get_result($notif_stmt);
$notif_data = mysqli_fetch_assoc($notif_result);
$notif_count = $notif_data['total'];
mysqli_stmt_close($notif_stmt);

# ----------------------------------------
# Primary blood bank selection for request
# ----------------------------------------
$bank_result = mysqli_query($conn, "SELECT id FROM users WHERE role='blood_bank' ORDER BY id ASC LIMIT 1");
$bank_row = mysqli_fetch_assoc($bank_result);
$primary_blood_bank_id = (int)($bank_row['id'] ?? 0);

# ----------------------------------------
# Request submission / validation block
# ----------------------------------------
if(isset($_POST['create_request'])){
    $blood_group = trim($_POST['blood_group']);
    $address_line = trim($_POST['address_line']);
    $city = trim($_POST['city']);
    $zipcode = trim($_POST['zipcode']);
    $quantity = (int)$_POST['quantity'];
    $urgency = trim($_POST['urgency']);
    $patient_note = trim($_POST['patient_note']);
    $status = 'pending_review';

    $valid_groups = ['A+','A-','B+','B-','O+','O-','AB+','AB-'];
    $valid_urgency = ['normal','urgent'];

    $location_parts = array_filter([$address_line, $city, $zipcode], function($v){
        return $v !== '';
    });
    $location = implode(', ', $location_parts);

    if(!in_array($blood_group, $valid_groups)){
        $error = "Please select a valid blood group.";
    } elseif($address_line == "" || $city == "" || $zipcode == ""){
        $error = "Address, city, and zipcode are required.";
    } elseif($quantity < 1){
        $error = "Quantity must be at least 1 unit.";
    } elseif(!in_array($urgency, $valid_urgency)){
        $error = "Please select a valid urgency.";
    } else {
        $stmt = mysqli_prepare($conn, "
            INSERT INTO blood_requests
            (recipient_id, blood_group, location, address_line, city, zipcode, quantity, urgency, status, assigned_blood_bank_id, patient_note)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        mysqli_stmt_bind_param(
            $stmt,
            "isssssissis",
            $recipient_id,
            $blood_group,
            $location,
            $address_line,
            $city,
            $zipcode,
            $quantity,
            $urgency,
            $status,
            $primary_blood_bank_id,
            $patient_note
        );

        if(mysqli_stmt_execute($stmt)){
            $request_id = mysqli_insert_id($conn);
            $success = "Blood request submitted successfully! It is now waiting for Central Blood Bank Network review.";

            # Activity log for audit trail
            logActivity(
                $conn,
                $recipient_id,
                'recipient',
                'create_request',
                "Recipient {$recipient_name} submitted {$urgency} blood request #{$request_id} for blood group {$blood_group}."
            );

            # Notify blood bank for review
            if($primary_blood_bank_id > 0){
                $blood_bank_message = ($urgency === 'urgent')
                    ? "[URGENT] Emergency blood request submitted. Request #{$request_id} needs immediate review."
                    : "A new blood request has been submitted and is waiting for review.";

                $notif_stmt_bank = mysqli_prepare($conn, "INSERT INTO notifications (user_id, message) VALUES (?, ?)");
                mysqli_stmt_bind_param($notif_stmt_bank, "is", $primary_blood_bank_id, $blood_bank_message);
                mysqli_stmt_execute($notif_stmt_bank);
                mysqli_stmt_close($notif_stmt_bank);
            }
        } else {
            $error = "Error: " . mysqli_error($conn);
        }

        mysqli_stmt_close($stmt);
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Create Blood Request</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>

<!-- =========================
     Full-width top navigation
========================= -->
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
                    <span class="notify-badge"><?php echo $notif_count; ?></span>
                </a>
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?> (Recipient)</span>
                <a class="btn btn-logout" href="../auth/logout.php">Logout</a>
            </div>
        </div>

        <div class="topbar-menu">
            <a href="recipient_dashboard.php">Dashboard</a>
            <a href="create_request.php" class="active">Create Request</a>
            <a href="recipient_requests.php">My Requests</a>
            <a href="recipient_appointments.php">Appointments</a>
            <a href="recipient_profile.php">Profile</a>
            <a href="notifications.php">Notifications</a>
        </div>
    </div>
</div>

<div class="container">

    <!-- =========================
         Hero / heading section
    ========================= -->
    <div class="hero">
        <h1>Create Blood Request</h1>
        <p>Your request will first go to Central Blood Bank Network for review. After assessment, the blood bank will check the nearest branch stock and decide the next step.</p>
    </div>

    <!-- =========================
         Request form card
    ========================= -->
    <div class="card">
        <h3>Request Form</h3>

        <?php
        if(isset($success)) echo "<div class='alert alert-success'>" . htmlspecialchars($success) . "</div>";
        if(isset($error)) echo "<div class='alert alert-error'>" . htmlspecialchars($error) . "</div>";
        ?>

        <form method="POST" class="form">
            <label>Blood Group</label>
            <select class="select" name="blood_group" required>
                <option value="">Select Blood Group</option>
                <option>A+</option><option>A-</option>
                <option>B+</option><option>B-</option>
                <option>O+</option><option>O-</option>
                <option>AB+</option><option>AB-</option>
            </select>

            <label>Address</label>
            <input class="input" type="text" name="address_line" required>

            <label>City</label>
            <input class="input" type="text" name="city" required>

            <label>Zipcode</label>
            <input class="input" type="text" name="zipcode" required>

            <label>Required Units</label>
            <input class="input" type="number" name="quantity" min="1" required>

            <label>Urgency</label>
            <select class="select" name="urgency" required>
                <option value="normal">Normal</option>
                <option value="urgent">Urgent</option>
            </select>

            <label>Patient Note</label>
            <textarea class="input" name="patient_note" rows="4"></textarea>

            <button class="btn btn-primary" type="submit" name="create_request">Submit Request</button>
        </form>
    </div>

</div>

<!-- =========================
     Full-width footer section
========================= -->
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
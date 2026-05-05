<?php
session_start();
include '../config/db.php';
include '../functions/eligibility_check.php';
include '../functions/branch_helper.php';
include '../functions/network_helper.php';

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'donor'){
    header("Location: ../auth/login.php");
    exit();
}

$donor_id = $_SESSION['user_id'];

$eligibility = syncDonorAvailabilityStatus($conn, $donor_id);

$notif_stmt = mysqli_prepare($conn, "SELECT COUNT(*) AS total FROM notifications WHERE user_id = ? AND is_read = 0");
mysqli_stmt_bind_param($notif_stmt, "i", $donor_id);
mysqli_stmt_execute($notif_stmt);
$notif_result = mysqli_stmt_get_result($notif_stmt);
$notif_data = mysqli_fetch_assoc($notif_result);
$notif_count = $notif_data['total'];
mysqli_stmt_close($notif_stmt);

$donor_stmt = mysqli_prepare($conn, "
    SELECT id, city, zipcode
    FROM users
    WHERE id = ?
");
mysqli_stmt_bind_param($donor_stmt, "i", $donor_id);
mysqli_stmt_execute($donor_stmt);
$donor_result = mysqli_stmt_get_result($donor_stmt);
$donor = mysqli_fetch_assoc($donor_result);
mysqli_stmt_close($donor_stmt);

$suggested_branch = null;
if(function_exists('getNearestBranchForLocation')){
    $suggested_branch = getNearestBranchForLocation($conn, 18, $donor['city'] ?? '', $donor['zipcode'] ?? '');
}

if(isset($_POST['submit_stock_request'])){
    if(!$eligibility['eligible']){
        $error = "You are temporarily unavailable to donate. Please wait until your next eligible date.";
    } else {
        $preferred_date = trim($_POST['preferred_date']);
        $branch_id = (int)($_POST['branch_id'] ?? 0);
        $notes = trim($_POST['notes']);

        if($preferred_date == "" || $branch_id <= 0){
            $error = "Preferred date and branch are required.";
        } else {
            $blood_bank_user_id = 18;

            $insert_stmt = mysqli_prepare($conn, "
                INSERT INTO stock_donation_requests (donor_id, blood_bank_user_id, branch_id, preferred_date, notes, status)
                VALUES (?, ?, ?, ?, ?, 'pending')
            ");
            mysqli_stmt_bind_param($insert_stmt, "iiiss", $donor_id, $blood_bank_user_id, $branch_id, $preferred_date, $notes);

            if(mysqli_stmt_execute($insert_stmt)){
                mysqli_stmt_close($insert_stmt);
                $success = "Your stock donation request has been submitted successfully.";
            } else {
                $error = "Failed to submit stock donation request.";
                mysqli_stmt_close($insert_stmt);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Donate to Stock</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>

<div class="topbar topbar-full">
    <div class="container">
        <div class="topbar-header">
            <div class="topbar-left">
                <div class="logo">🩸</div>
                <div class="brand-text">
                    <h2>Blood Donation Management System</h2>
                    <p>Donate Blood, Save Lives</p>
                </div>
            </div>

            <div class="topbar-right">
                <a href="notifications.php" class="notify" title="Notifications">
                    🔔
                    <span class="notify-badge"><?php echo $notif_count; ?></span>
                </a>
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?> (Donor)</span>
                <a class="btn btn-logout" href="../auth/logout.php">Logout</a>
            </div>
        </div>

        <div class="topbar-menu">
            <a href="donor_dashboard.php">Dashboard</a>
            <a href="donor_compatible_requests.php">Compatible Requests</a>
            <a href="donor_interested_requests.php">Interested Requests</a>
            <a href="donate_to_stock.php" class="active">Donate to Stock</a>
            <a href="donor_appointments.php">Appointments</a>
            <a href="donor_history.php">History</a>
            <a href="donor_profile.php">Profile</a>
            <a href="notifications.php">Notifications</a>
        </div>
    </div>
</div>

<div class="container">
    <div class="hero">
        <h1>Donate to Stock</h1>
        <p>Request a general donation appointment so your blood can strengthen stock at the nearest branch.</p>
    </div>

    <?php if(!$eligibility['eligible']) { ?>
        <div class="alert alert-error donor-lock-alert">
            You are temporarily unavailable for stock donation.
            <?php if(!empty($eligibility['reason'])) { ?>
                <br><?php echo htmlspecialchars($eligibility['reason']); ?>
            <?php } ?>
            <?php if(!empty($eligibility['next_eligible_date'])) { ?>
                <br><strong>Next Eligible Date:</strong> <?php echo htmlspecialchars($eligibility['next_eligible_date']); ?>
            <?php } ?>
        </div>
    <?php } ?>

    <div class="card">
        <h3>Nearest Suggested Branch</h3>
        <?php if($suggested_branch){ ?>
            <p>
                <strong>Branch:</strong> <?php echo htmlspecialchars($suggested_branch['branch_name'] ?? ''); ?><br>
                <strong>Location:</strong> <?php echo htmlspecialchars(buildBranchFullLocation($suggested_branch)); ?>
            </p>
        <?php } else { ?>
            <p>No suggested branch available.</p>
        <?php } ?>
    </div>

    <div class="card">
        <h3>Stock Donation Request</h3>

        <?php
        if(isset($success)) echo "<div class='alert alert-success'>" . htmlspecialchars($success) . "</div>";
        if(isset($error)) echo "<div class='alert alert-error'>" . htmlspecialchars($error) . "</div>";
        ?>

        <form method="POST" class="form">
            <label>Preferred Date & Time</label>
            <input class="input" type="datetime-local" name="preferred_date" <?php echo !$eligibility['eligible'] ? 'disabled' : ''; ?> required>

            <label>Select Branch</label>
            <select class="select" name="branch_id" <?php echo !$eligibility['eligible'] ? 'disabled' : ''; ?> required>
                <option value="">Select Branch</option>
                <?php
                $branches = mysqli_query($conn, "SELECT * FROM branches WHERE is_active = 1 ORDER BY branch_name ASC");
                while($branch = mysqli_fetch_assoc($branches)){
                    $selected = ($suggested_branch && (int)$suggested_branch['id'] === (int)$branch['id']) ? 'selected' : '';
                    echo "<option value='" . (int)$branch['id'] . "' $selected>" .
                        htmlspecialchars($branch['branch_name'] . ' - ' . buildBranchFullLocation($branch)) .
                    "</option>";
                }
                ?>
            </select>

            <label>Notes</label>
            <textarea class="input" name="notes" rows="5" <?php echo !$eligibility['eligible'] ? 'disabled' : ''; ?>></textarea>

            <button class="btn btn-primary" type="submit" name="submit_stock_request" <?php echo !$eligibility['eligible'] ? 'disabled' : ''; ?>>
                Submit Stock Donation Request
            </button>
        </form>
    </div>
</div>

<div class="site-footer site-footer-full">
    <div class="container">
        <div class="footer-grid">
            <div class="footer-col">
                <h4>About Us</h4>
                <p>Blood Donation Management System is a final year project prototype designed to simulate donor, recipient, blood bank, branch coordination, stock monitoring, donor matching, and appointment management.</p>
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
            © 2026 Blood Donation Management System • Final Year Project Prototype
        </div>
    </div>
</div>

</body>
</html>
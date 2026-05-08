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

$donor_id = (int)$_SESSION['user_id'];

// Check donor eligibility before allowing stock donation request
$eligibility = syncDonorAvailabilityStatus($conn, $donor_id);

// Get donor notification count
$notif_stmt = mysqli_prepare($conn, "SELECT COUNT(*) AS total FROM notifications WHERE user_id = ? AND is_read = 0");
mysqli_stmt_bind_param($notif_stmt, "i", $donor_id);
mysqli_stmt_execute($notif_stmt);
$notif_result = mysqli_stmt_get_result($notif_stmt);
$notif_data = mysqli_fetch_assoc($notif_result);
$notif_count = $notif_data['total'];
mysqli_stmt_close($notif_stmt);

// Get donor details for branch suggestion and notification message
$donor_stmt = mysqli_prepare($conn, "
    SELECT id, name, blood_group, city, zipcode
    FROM users
    WHERE id = ?
");
mysqli_stmt_bind_param($donor_stmt, "i", $donor_id);
mysqli_stmt_execute($donor_stmt);
$donor_result = mysqli_stmt_get_result($donor_stmt);
$donor = mysqli_fetch_assoc($donor_result);
mysqli_stmt_close($donor_stmt);

// Get default active blood bank user for branch suggestion
$default_blood_bank_id = 0;
$bank_result = mysqli_query($conn, "
    SELECT id 
    FROM users 
    WHERE role = 'blood_bank' AND status = 'active' 
    ORDER BY id ASC 
    LIMIT 1
");

if($bank_result && mysqli_num_rows($bank_result) > 0){
    $bank_row = mysqli_fetch_assoc($bank_result);
    $default_blood_bank_id = (int)$bank_row['id'];
}

// Suggest nearest branch for donor location
$suggested_branch = null;
if($default_blood_bank_id > 0 && function_exists('getNearestBranchForLocation')){
    $suggested_branch = getNearestBranchForLocation($conn, $default_blood_bank_id, $donor['city'] ?? '', $donor['zipcode'] ?? '');
}

if(isset($_POST['submit_stock_request'])){

    // Block request if donor is not eligible
    if(!$eligibility['eligible']){
        $error = "You are temporarily unavailable to donate. Please wait until your next eligible date.";
    } else {
        $preferred_date = trim($_POST['preferred_date']);
        $branch_id = (int)($_POST['branch_id'] ?? 0);
        $notes = trim($_POST['notes']);

        if($preferred_date == "" || $branch_id <= 0){
            $error = "Preferred date and branch are required.";
        } else {

            // Fetch selected branch and its blood bank owner
            $branch_stmt = mysqli_prepare($conn, "
                SELECT id, branch_name, blood_bank_user_id
                FROM branches
                WHERE id = ? AND is_active = 1
                LIMIT 1
            ");
            mysqli_stmt_bind_param($branch_stmt, "i", $branch_id);
            mysqli_stmt_execute($branch_stmt);
            $branch_result = mysqli_stmt_get_result($branch_stmt);

            if(mysqli_num_rows($branch_result) !== 1){
                $error = "Selected branch is not valid.";
                mysqli_stmt_close($branch_stmt);
            } else {
                $branch = mysqli_fetch_assoc($branch_result);
                mysqli_stmt_close($branch_stmt);

                $blood_bank_user_id = (int)$branch['blood_bank_user_id'];
                $branch_name = $branch['branch_name'];

                // Insert stock donation request
                $insert_stmt = mysqli_prepare($conn, "
                    INSERT INTO stock_donation_requests 
                        (donor_id, blood_bank_user_id, branch_id, preferred_date, notes, status)
                    VALUES 
                        (?, ?, ?, ?, ?, 'pending')
                ");
                mysqli_stmt_bind_param($insert_stmt, "iiiss", $donor_id, $blood_bank_user_id, $branch_id, $preferred_date, $notes);

                if(mysqli_stmt_execute($insert_stmt)){
                    $stock_request_id = mysqli_insert_id($conn);
                    mysqli_stmt_close($insert_stmt);

                    // Notify blood bank about new stock donation request
                    $donor_name = $donor['name'] ?? 'A donor';
                    $donor_blood_group = $donor['blood_group'] ?? 'N/A';

                    $message_bank = "New stock donation request #{$stock_request_id} from {$donor_name} ({$donor_blood_group}) for {$branch_name}. Preferred date: {$preferred_date}.";

                    $notif_bank = mysqli_prepare($conn, "
                        INSERT INTO notifications (user_id, message)
                        VALUES (?, ?)
                    ");
                    mysqli_stmt_bind_param($notif_bank, "is", $blood_bank_user_id, $message_bank);
                    mysqli_stmt_execute($notif_bank);
                    mysqli_stmt_close($notif_bank);

                    // Notify donor that request was submitted
                    $message_donor = "Your stock donation request #{$stock_request_id} has been submitted and is waiting for blood bank scheduling.";

                    $notif_donor = mysqli_prepare($conn, "
                        INSERT INTO notifications (user_id, message)
                        VALUES (?, ?)
                    ");
                    mysqli_stmt_bind_param($notif_donor, "is", $donor_id, $message_donor);
                    mysqli_stmt_execute($notif_donor);
                    mysqli_stmt_close($notif_donor);

                    $success = "Your stock donation request has been submitted successfully.";
                } else {
                    $error = "Failed to submit stock donation request.";
                    mysqli_stmt_close($insert_stmt);
                }
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
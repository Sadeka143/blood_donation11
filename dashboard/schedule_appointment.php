<?php
session_start();
include '../config/db.php';
include '../functions/log_activity.php';
include '../functions/network_helper.php';
include '../functions/branch_helper.php';
include '../functions/eligibility_check.php';

// Check blood bank login
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'blood_bank'){
    header("Location: ../auth/login.php");
    exit();
}

$user_id = (int)$_SESSION['user_id'];

// Check request id
if(!isset($_GET['id']) || !is_numeric($_GET['id'])){
    header("Location: blood_bank_matched.php");
    exit();
}

$request_id = (int)$_GET['id'];

// Get notification count
$notif_stmt = mysqli_prepare($conn, "SELECT COUNT(*) AS total FROM notifications WHERE user_id = ? AND is_read = 0");
mysqli_stmt_bind_param($notif_stmt, "i", $user_id);
mysqli_stmt_execute($notif_stmt);
$notif_result = mysqli_stmt_get_result($notif_stmt);
$notif_data = mysqli_fetch_assoc($notif_result);
$notif_count = $notif_data['total'] ?? 0;
mysqli_stmt_close($notif_stmt);

// Get matched request with recipient and donor details
$request_stmt = mysqli_prepare($conn, "
    SELECT 
        r.*, 
        u.name AS recipient_name, 
        d.name AS donor_name,
        d.availability AS donor_availability,
        d.account_status AS donor_account_status,
        d.status AS donor_user_status
    FROM blood_requests r
    JOIN users u ON r.recipient_id = u.id
    LEFT JOIN users d ON r.matched_donor_id = d.id
    WHERE r.id = ? 
      AND r.status = 'matched' 
      AND r.assigned_blood_bank_id = ?
    LIMIT 1
");
mysqli_stmt_bind_param($request_stmt, "ii", $request_id, $user_id);
mysqli_stmt_execute($request_stmt);
$request_result = mysqli_stmt_get_result($request_stmt);

if(mysqli_num_rows($request_result) != 1){
    mysqli_stmt_close($request_stmt);
    header("Location: blood_bank_matched.php");
    exit();
}

$request = mysqli_fetch_assoc($request_result);
mysqli_stmt_close($request_stmt);

$donor_id = (int)($request['matched_donor_id'] ?? 0);
$recipient_id = (int)$request['recipient_id'];

// Block if no donor is matched
if($donor_id <= 0){
    header("Location: blood_bank_matched.php");
    exit();
}

// Re-check donor eligibility before scheduling
$donorEligibility = syncDonorAvailabilityStatus($conn, $donor_id);

// Check donor account status
$donorAccountActive = (
    ($request['donor_account_status'] ?? 'active') === 'active' &&
    ($request['donor_user_status'] ?? 'active') === 'active'
);

// Final permission for scheduling
$canScheduleAppointment = (
    $donorAccountActive &&
    !empty($donorEligibility['eligible']) &&
    ($donorEligibility['availability'] ?? 'not_available') === 'available'
);

// Build block message
$donorBlockMessage = "";

if(!$donorAccountActive){
    $donorBlockMessage = "This donor account is inactive, so an appointment cannot be scheduled.";
} elseif(!$canScheduleAppointment){
    $donorBlockMessage = $donorEligibility['reason'] ?? "This donor is currently not eligible or available for donation.";

    if(!empty($donorEligibility['next_eligible_date'])){
        $donorBlockMessage .= " Next eligible date: " . $donorEligibility['next_eligible_date'] . ".";
    }
}

// Get suggested nearest branch
$suggested_branch = getNearestBranchForLocation($conn, $user_id, $request['city'] ?? '', $request['zipcode'] ?? '');

// Handle appointment schedule form
if(isset($_POST['schedule_appointment'])){
    $appointment_date = trim($_POST['appointment_date'] ?? '');
    $branch_id = (int)($_POST['branch_id'] ?? 0);
    $notes = trim($_POST['notes'] ?? '');

    // Re-check donor eligibility again on submit
    $donorEligibility = syncDonorAvailabilityStatus($conn, $donor_id);

    $canScheduleAppointment = (
        $donorAccountActive &&
        !empty($donorEligibility['eligible']) &&
        ($donorEligibility['availability'] ?? 'not_available') === 'available'
    );

    if(!$canScheduleAppointment){
        $error = $donorBlockMessage !== "" ? $donorBlockMessage : "This donor is currently not eligible or available for donation.";
    } elseif($appointment_date == "" || $branch_id <= 0){
        $error = "Appointment date and branch are required.";
    } else {

        // Validate selected branch
        $branch_stmt = mysqli_prepare($conn, "
            SELECT * 
            FROM branches 
            WHERE id = ? AND blood_bank_user_id = ? AND is_active = 1
            LIMIT 1
        ");
        mysqli_stmt_bind_param($branch_stmt, "ii", $branch_id, $user_id);
        mysqli_stmt_execute($branch_stmt);
        $branch_result = mysqli_stmt_get_result($branch_stmt);
        $branch = mysqli_fetch_assoc($branch_result);
        mysqli_stmt_close($branch_stmt);

        if(!$branch){
            $error = "Invalid branch selected.";
        } else {
            $appointment_location = buildBranchFullLocation($branch);

            // Use transaction so partial data is not saved
            mysqli_begin_transaction($conn);

            try{

                // Insert appointment
                $insert_stmt = mysqli_prepare($conn, "
                    INSERT INTO appointments
                    (request_id, donor_id, recipient_id, blood_bank_id, branch_id, appointment_date, appointment_location, appointment_address, appointment_city, appointment_zipcode, notes, status, appointment_type)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'scheduled', 'request_based')
                ");

                mysqli_stmt_bind_param(
                    $insert_stmt,
                    "iiiiissssss",
                    $request_id,
                    $donor_id,
                    $recipient_id,
                    $user_id,
                    $branch_id,
                    $appointment_date,
                    $appointment_location,
                    $branch['address_line'],
                    $branch['city'],
                    $branch['zipcode'],
                    $notes
                );

                mysqli_stmt_execute($insert_stmt);

                if(mysqli_stmt_affected_rows($insert_stmt) !== 1){
                    mysqli_stmt_close($insert_stmt);
                    throw new Exception("Failed to insert appointment.");
                }

                mysqli_stmt_close($insert_stmt);

                // Update request status only if still matched
                $update_request_stmt = mysqli_prepare($conn, "
                    UPDATE blood_requests
                    SET status = 'scheduled', fulfilled_branch_id = ?, fulfillment_source = 'donor'
                    WHERE id = ? 
                      AND status = 'matched' 
                      AND assigned_blood_bank_id = ?
                ");
                mysqli_stmt_bind_param($update_request_stmt, "iii", $branch_id, $request_id, $user_id);
                mysqli_stmt_execute($update_request_stmt);

                if(mysqli_stmt_affected_rows($update_request_stmt) !== 1){
                    mysqli_stmt_close($update_request_stmt);
                    throw new Exception("Failed to update request status.");
                }

                mysqli_stmt_close($update_request_stmt);

                // Save activity log
                logActivity(
                    $conn,
                    $user_id,
                    'blood_bank',
                    'schedule_appointment',
                    "Central Blood Bank Network scheduled request-based appointment for request #{$request_id} at branch {$branch['branch_name']}."
                );

                // Notify donor
                $message_donor = "A donation appointment has been scheduled for you at {$branch['branch_name']} on {$appointment_date}.";
                $notif_stmt1 = mysqli_prepare($conn, "INSERT INTO notifications (user_id, message) VALUES (?, ?)");
                mysqli_stmt_bind_param($notif_stmt1, "is", $donor_id, $message_donor);
                mysqli_stmt_execute($notif_stmt1);
                mysqli_stmt_close($notif_stmt1);

                // Notify recipient
                $message_recipient = "Your blood request has been scheduled at {$branch['branch_name']} on {$appointment_date}.";
                $notif_stmt2 = mysqli_prepare($conn, "INSERT INTO notifications (user_id, message) VALUES (?, ?)");
                mysqli_stmt_bind_param($notif_stmt2, "is", $recipient_id, $message_recipient);
                mysqli_stmt_execute($notif_stmt2);
                mysqli_stmt_close($notif_stmt2);

                // Save all changes
                mysqli_commit($conn);

                header("Location: blood_bank_scheduled.php");
                exit();

            } catch(Exception $e){

                // Rollback if any step fails
                mysqli_rollback($conn);
                $error = "Failed to schedule appointment. Please try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Schedule Appointment</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>

<div class="topbar topbar-full bloodbank-shell">
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
                    <span class="notify-badge"><?php echo (int)$notif_count; ?></span>
                </a>
                <span>Welcome, Central Blood Bank Network (Network Control)</span>
                <a class="btn btn-logout" href="../auth/logout.php">Logout</a>
            </div>
        </div>

        <div class="topbar-menu">
            <a href="blood_bank_dashboard.php">Dashboard</a>
            <a href="blood_bank_pending.php">Pending</a>
            <a href="blood_bank_approved.php">Approved</a>
            <a href="blood_bank_matched.php" class="active">Matched</a>
            <a href="blood_bank_scheduled.php">Scheduled</a>
            <a href="blood_bank_confirmed.php">Confirmed</a>
            <a href="blood_bank_history.php">History</a>
            <a href="blood_bank_stock_requests.php">Stock Requests</a>
            <a href="notifications.php">Notifications</a>
        </div>
    </div>
</div>

<div class="container">
    <div class="hero">
        <h1>Schedule Branch Appointment</h1>
        <p>Arrange a request-based donation appointment at the nearest suitable branch.</p>
    </div>

    <?php if(!$canScheduleAppointment){ ?>
        <div class="alert alert-error">
            Appointment scheduling is blocked.
            <?php echo htmlspecialchars($donorBlockMessage); ?>
        </div>
    <?php } ?>

    <div class="card">
        <h3>Request Summary</h3>
        <p>
            <strong>Request ID:</strong> <?php echo htmlspecialchars($request['id']); ?><br>
            <strong>Recipient:</strong> <?php echo htmlspecialchars($request['recipient_name']); ?><br>
            <strong>Matched Donor:</strong> <?php echo htmlspecialchars($request['donor_name'] ?? 'N/A'); ?><br>
            <strong>Donor Availability:</strong> <?php echo htmlspecialchars($donorEligibility['availability'] ?? 'not_available'); ?><br>
            <strong>Blood Group:</strong> <?php echo htmlspecialchars($request['blood_group']); ?><br>
            <strong>Request Address:</strong> <?php echo htmlspecialchars($request['address_line'] ?? ''); ?><br>
            <strong>Request City:</strong> <?php echo htmlspecialchars($request['city'] ?? ''); ?><br>
            <strong>Request Zipcode:</strong> <?php echo htmlspecialchars($request['zipcode'] ?? ''); ?><br>
            <strong>Quantity:</strong> <?php echo htmlspecialchars($request['quantity']); ?><br>
            <strong>Urgency:</strong> <?php echo htmlspecialchars($request['urgency']); ?>
        </p>
    </div>

    <div class="card">
        <h3>Suggested Branch</h3>
        <?php if($suggested_branch){ ?>
            <p>
                <strong>Branch:</strong> <?php echo htmlspecialchars($suggested_branch['branch_name']); ?><br>
                <strong>Location:</strong> <?php echo htmlspecialchars(buildBranchFullLocation($suggested_branch)); ?>
            </p>
        <?php } else { ?>
            <p>No suggested branch available.</p>
        <?php } ?>
    </div>

    <div class="card">
        <h3>Appointment Details</h3>

        <?php
        if(isset($error)){
            echo "<div class='alert alert-error'>" . htmlspecialchars($error) . "</div>";
        }
        ?>

        <form method="POST" class="form">
            <label>Appointment Date & Time</label>
            <input 
                class="input" 
                type="datetime-local" 
                name="appointment_date" 
                <?php echo !$canScheduleAppointment ? 'disabled' : ''; ?> 
                required
            >

            <label>Select Branch</label>
            <select 
                class="select" 
                name="branch_id" 
                <?php echo !$canScheduleAppointment ? 'disabled' : ''; ?> 
                required
            >
                <option value="">Select Branch</option>
                <?php
                $branch_list_stmt = mysqli_prepare($conn, "
                    SELECT * 
                    FROM branches 
                    WHERE blood_bank_user_id = ? AND is_active = 1 
                    ORDER BY branch_name ASC
                ");
                mysqli_stmt_bind_param($branch_list_stmt, "i", $user_id);
                mysqli_stmt_execute($branch_list_stmt);
                $branches = mysqli_stmt_get_result($branch_list_stmt);

                while($branch = mysqli_fetch_assoc($branches)){
                    $selected = ($suggested_branch && (int)$suggested_branch['id'] === (int)$branch['id']) ? 'selected' : '';

                    echo "<option value='" . (int)$branch['id'] . "' $selected>" .
                        htmlspecialchars($branch['branch_name'] . ' - ' . buildBranchFullLocation($branch)) .
                    "</option>";
                }

                mysqli_stmt_close($branch_list_stmt);
                ?>
            </select>

            <label>Notes</label>
            <textarea 
                class="input" 
                name="notes" 
                rows="4" 
                placeholder="Optional notes for donor or recipient"
                <?php echo !$canScheduleAppointment ? 'disabled' : ''; ?>
            ></textarea>

            <button 
                class="btn btn-primary" 
                type="submit" 
                name="schedule_appointment"
                <?php echo !$canScheduleAppointment ? 'disabled' : ''; ?>
            >
                Schedule Appointment
            </button>
        </form>
    </div>

</div>

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
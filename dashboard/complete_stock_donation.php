<?php
session_start();
include '../config/db.php';
include '../functions/stock_helper.php';
include '../functions/log_activity.php';
include '../functions/eligibility_check.php';

// =========================================================
// Access control: only blood bank can complete stock donation
// =========================================================
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'blood_bank'){
    header("Location: ../auth/login.php");
    exit();
}

// =========================================================
// Validate stock donation request ID
// =========================================================
if(!isset($_GET['id']) || !is_numeric($_GET['id'])){
    header("Location: blood_bank_stock_requests.php?complete=invalid");
    exit();
}

$blood_bank_id = (int)$_SESSION['user_id'];
$blood_bank_name = $_SESSION['name'] ?? 'Central Blood Bank Network';
$stock_request_id = (int)$_GET['id'];

// =========================================================
// Fetch the confirmed stock donation request
// Important: stock donation can be completed only after donor confirms.
// =========================================================
$stmt = mysqli_prepare($conn, "
    SELECT
        sdr.id,
        sdr.donor_id,
        sdr.blood_bank_user_id,
        sdr.branch_id,
        sdr.preferred_date,
        sdr.scheduled_date,
        sdr.completed_at,
        sdr.completion_note,
        sdr.notes,
        sdr.status,
        donor.name AS donor_name,
        donor.blood_group,
        b.branch_name
    FROM stock_donation_requests sdr
    JOIN users donor ON sdr.donor_id = donor.id
    LEFT JOIN branches b ON sdr.branch_id = b.id
    WHERE sdr.id = ? AND sdr.blood_bank_user_id = ?
    LIMIT 1
");
mysqli_stmt_bind_param($stmt, "ii", $stock_request_id, $blood_bank_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if(mysqli_num_rows($result) !== 1){
    mysqli_stmt_close($stmt);
    header("Location: blood_bank_stock_requests.php?complete=invalid");
    exit();
}

$request = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

// =========================================================
// Only confirmed stock appointments can be completed
// pending/scheduled means donor has not confirmed yet.
// completed/cancelled should not be completed again.
// =========================================================
if($request['status'] !== 'confirmed'){
    header("Location: blood_bank_stock_requests.php?complete=not_confirmed");
    exit();
}

// =========================================================
// Required values for completion
// =========================================================
$donor_id = (int)$request['donor_id'];
$branch_id = !empty($request['branch_id']) ? (int)$request['branch_id'] : null;
$blood_group = $request['blood_group'];
$scheduled_date = $request['scheduled_date'] ?: $request['preferred_date'];
$notes = $request['notes'] ?? '';
$donation_date = date('Y-m-d');
$completed_at = date('Y-m-d H:i:s');
$completion_note = "Stock donation completed by {$blood_bank_name} on {$completed_at}.";

mysqli_begin_transaction($conn);

try {
    // =========================================================
    // Step 1: Mark stock donation request as completed
    // Status condition prevents duplicate completion on refresh/double click.
    // =========================================================
    $update_request = mysqli_prepare($conn, "
        UPDATE stock_donation_requests
        SET status = 'completed', completed_at = ?, completion_note = ?
        WHERE id = ? AND blood_bank_user_id = ? AND status = 'confirmed'
    ");
    mysqli_stmt_bind_param($update_request, "ssii", $completed_at, $completion_note, $stock_request_id, $blood_bank_id);
    mysqli_stmt_execute($update_request);

    if(mysqli_stmt_affected_rows($update_request) !== 1){
        mysqli_stmt_close($update_request);
        throw new Exception('Stock donation request could not be completed.');
    }
    mysqli_stmt_close($update_request);

    // =========================================================
    // Step 2: Increase branch stock by 1 unit
    // Stock donation means donor is donating into branch inventory.
    // =========================================================
    if($branch_id !== null){
        $stockUpdated = increaseBranchStock($conn, $branch_id, $blood_group, 1);
        if(!$stockUpdated){
            throw new Exception('Branch stock could not be updated.');
        }
    }

    // =========================================================
    // Step 3: Insert donation history record
    // This creates donor history for stock donation.
    // =========================================================
    $insert_donation = mysqli_prepare($conn, "
        INSERT INTO donations
            (donor_id, blood_bank_id, request_id, donation_date, scheduled_date, completed_at, notes, status, branch_id, donation_type)
        VALUES
            (?, ?, NULL, ?, ?, ?, ?, 'confirmed', ?, 'stock_donation')
    ");

    mysqli_stmt_bind_param(
        $insert_donation,
        "iissssi",
        $donor_id,
        $blood_bank_id,
        $donation_date,
        $scheduled_date,
        $completed_at,
        $notes,
        $branch_id
    );

    mysqli_stmt_execute($insert_donation);

    if(mysqli_stmt_affected_rows($insert_donation) !== 1){
        mysqli_stmt_close($insert_donation);
        throw new Exception('Donation history could not be created.');
    }

    mysqli_stmt_close($insert_donation);

    // =========================================================
    // Step 4: Make donor unavailable until next eligible date
    // This uses the latest donation record inserted above.
    // =========================================================
    $eligibility = syncDonorAvailabilityStatus($conn, $donor_id);

    // =========================================================
    // Step 5: Activity log for admin/blood bank history
    // =========================================================
    logActivity(
        $conn,
        $blood_bank_id,
        'blood_bank',
        'complete_stock_donation',
        "{$blood_bank_name} completed stock donation request #{$stock_request_id} from donor {$request['donor_name']} and added 1 unit of {$blood_group} to branch stock."
    );

    // =========================================================
    // Step 6: Notify donor
    // =========================================================
    $message_donor = "Your stock donation has been marked as completed. You are temporarily unavailable for donation.";

    if(!empty($eligibility['next_eligible_date'])){
        $message_donor .= " Next Eligible Date: " . $eligibility['next_eligible_date'] . ".";
    }

    $notif_donor = mysqli_prepare($conn, "INSERT INTO notifications (user_id, message) VALUES (?, ?)");
    mysqli_stmt_bind_param($notif_donor, "is", $donor_id, $message_donor);
    mysqli_stmt_execute($notif_donor);
    mysqli_stmt_close($notif_donor);

    // Final: commit all changes together
    mysqli_commit($conn);

    header("Location: blood_bank_stock_requests.php?complete=success");
    exit();

} catch (Exception $e) {
    // =========================================================
    // If any step fails, rollback so partial data is not saved
    // =========================================================
    mysqli_rollback($conn);

    header("Location: blood_bank_stock_requests.php?complete=error");
    exit();
}
?>
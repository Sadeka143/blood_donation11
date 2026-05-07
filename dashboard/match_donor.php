<?php
session_start();
include '../config/db.php';
include '../functions/blood_compatibility.php';
include '../functions/eligibility_check.php';

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'blood_bank'){
    header("Location: ../auth/login.php");
    exit();
}

if(!isset($_GET['id'])){
    header("Location: blood_bank_dashboard.php");
    exit();
}

$request_id = (int) $_GET['id'];

/* Get request details */
$request_stmt = mysqli_prepare($conn, "SELECT r.*, u.name AS recipient_name FROM blood_requests r JOIN users u ON r.recipient_id = u.id WHERE r.id = ?");
mysqli_stmt_bind_param($request_stmt, "i", $request_id);
mysqli_stmt_execute($request_stmt);
$request_result = mysqli_stmt_get_result($request_stmt);

if(mysqli_num_rows($request_result) != 1){
    header("Location: blood_bank_dashboard.php");
    exit();
}

$request = mysqli_fetch_assoc($request_result);
mysqli_stmt_close($request_stmt);

if($request['status'] != 'approved'){
    header("Location: blood_bank_dashboard.php");
    exit();
}

$request_blood_group = $request['blood_group'];

/* Find compatible donors for this recipient blood group */
$all_donors_sql = "SELECT id, name, email, blood_group, location, date_of_birth, weight_kg, availability FROM users WHERE role = 'donor' AND availability = 'available'";
$all_donors_result = mysqli_query($conn, $all_donors_sql);

$compatible_donors = [];

while($donor = mysqli_fetch_assoc($all_donors_result)){
    $donor_groups = getCompatibleRecipientGroups($donor['blood_group']);

    if(in_array($request_blood_group, $donor_groups)){
        $eligibility = checkDonorEligibility($conn, $donor['id'], $donor['date_of_birth'], $donor['weight_kg']);

        $donor['eligibility'] = $eligibility;
        $compatible_donors[] = $donor;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Match Donor</title>
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
            <span><?php echo htmlspecialchars($_SESSION['institution_name'] ?: $_SESSION['name']); ?> (Blood Bank)</span>
            <a class="btn btn-logout" href="../auth/logout.php">Logout</a>
        </div>
    </div>

    <div class="card">
        <h3>Match Donor for Request #<?php echo htmlspecialchars($request['id']); ?></h3>
        <p><a class="link" href="blood_bank_dashboard.php">← Back to Blood Bank Dashboard</a></p>

        <p><strong>Recipient:</strong> <?php echo htmlspecialchars($request['recipient_name']); ?></p>
        <p><strong>Blood Group Needed:</strong> <?php echo htmlspecialchars($request['blood_group']); ?></p>
        <p><strong>Location:</strong> <?php echo htmlspecialchars($request['location']); ?></p>
        <p><strong>Quantity:</strong> <?php echo htmlspecialchars($request['quantity']); ?></p>
        <p><strong>Urgency:</strong> <?php echo htmlspecialchars($request['urgency']); ?></p>
    </div>

    <div class="card">
        <h3>Compatible Donors</h3>

        <?php if(count($compatible_donors) > 0){ ?>
            <table>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Blood Group</th>
                    <th>Location</th>
                    <th>Weight</th>
                    <th>Age</th>
                    <th>Last Donation</th>
                    <th>Eligibility</th>
                    <th>Reason</th>
                    <th>Action</th>
                </tr>

                <?php foreach($compatible_donors as $donor){ ?>
                    <tr>
                        <td><?php echo htmlspecialchars($donor['id']); ?></td>
                        <td><?php echo htmlspecialchars($donor['name']); ?></td>
                        <td><?php echo htmlspecialchars($donor['blood_group']); ?></td>
                        <td><?php echo htmlspecialchars($donor['location']); ?></td>
                        <td><?php echo htmlspecialchars($donor['weight_kg']); ?> kg</td>
                        <td><?php echo htmlspecialchars($donor['eligibility']['age'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($donor['eligibility']['last_donation_date'] ?? 'No donations yet'); ?></td>
                        <td>
                            <?php if($donor['eligibility']['eligible']){ ?>
                                <span class="badge badge-completed">Eligible</span>
                            <?php } else { ?>
                                <span class="badge badge-pending">Not Eligible</span>
                            <?php } ?>
                        </td>
                        <td><?php echo htmlspecialchars($donor['eligibility']['reason'] ?: '-'); ?></td>
                        <td>
                            <?php if($donor['eligibility']['eligible']){ ?>
                                <a class="link" href="select_donor.php?request_id=<?php echo $request['id']; ?>&donor_id=<?php echo $donor['id']; ?>" onclick="return confirm('Match this donor with the request?');">Select</a>
                            <?php } else { ?>
                                <span class="small-muted">Unavailable</span>
                            <?php } ?>
                        </td>
                    </tr>
                <?php } ?>
            </table>
        <?php } else { ?>
            <p>No compatible donors found for this request.</p>
        <?php } ?>
    </div>

</div>

</body>
</html>
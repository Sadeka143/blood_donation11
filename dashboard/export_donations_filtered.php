<?php
session_start();
include '../config/db.php';

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin'){
    header("Location: ../auth/login.php");
    exit();
}

$donor_search = isset($_GET['donor_search']) ? trim($_GET['donor_search']) : "";
$blood_group = isset($_GET['blood_group']) ? trim($_GET['blood_group']) : "all";
$donation_type = isset($_GET['donation_type']) ? trim($_GET['donation_type']) : "all";
$status_filter = isset($_GET['status']) ? trim($_GET['status']) : "all";
$date_from = isset($_GET['date_from']) ? trim($_GET['date_from']) : "";
$date_to = isset($_GET['date_to']) ? trim($_GET['date_to']) : "";

$allowed_groups = ['all','A+','A-','B+','B-','O+','O-','AB+','AB-'];
$allowed_type = ['all','request_based','stock_donation'];
$allowed_status = ['all','confirmed','cancelled'];

if(!in_array($blood_group, $allowed_groups)) $blood_group = 'all';
if(!in_array($donation_type, $allowed_type)) $donation_type = 'all';
if(!in_array($status_filter, $allowed_status)) $status_filter = 'all';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=donations_filtered_report.csv');

$output = fopen('php://output', 'w');

fputcsv($output, [
    'Donation ID',
    'Donor Name',
    'Donor Email',
    'Donation Type',
    'Blood Group',
    'Branch',
    'Blood Bank',
    'Location',
    'Quantity',
    'Scheduled Date',
    'Completed At',
    'Donation Date',
    'Notes',
    'Status'
]);

$sql = "
    SELECT 
        d.*,
        donor.name AS donor_name,
        donor.email AS donor_email,
        COALESCE(r.blood_group, donor.blood_group) AS blood_group,
        CASE 
            WHEN d.donation_type = 'stock_donation' THEN 'Branch Stock Donation'
            ELSE r.location
        END AS location,
        CASE 
            WHEN d.donation_type = 'stock_donation' THEN 1
            ELSE r.quantity
        END AS quantity,
        b.branch_name,
        bb.name AS blood_bank_name,
        bb.institution_name
    FROM donations d
    JOIN users donor ON d.donor_id = donor.id
    LEFT JOIN blood_requests r ON d.request_id = r.id
    LEFT JOIN branches b ON d.branch_id = b.id
    LEFT JOIN users bb ON d.blood_bank_id = bb.id
    WHERE 1=1
";

if($donor_search !== ""){
    $donor_safe = mysqli_real_escape_string($conn, $donor_search);
    $sql .= " AND donor.name LIKE '%$donor_safe%'";
}

if($blood_group !== "all"){
    $group_safe = mysqli_real_escape_string($conn, $blood_group);
    $sql .= " AND COALESCE(r.blood_group, donor.blood_group) = '$group_safe'";
}

if($donation_type !== "all"){
    $type_safe = mysqli_real_escape_string($conn, $donation_type);
    $sql .= " AND d.donation_type = '$type_safe'";
}

if($status_filter !== "all"){
    $status_safe = mysqli_real_escape_string($conn, $status_filter);
    $sql .= " AND d.status = '$status_safe'";
}

if($date_from !== ""){
    $date_from_safe = mysqli_real_escape_string($conn, $date_from);
    $sql .= " AND DATE(d.donation_date) >= '$date_from_safe'";
}

if($date_to !== ""){
    $date_to_safe = mysqli_real_escape_string($conn, $date_to);
    $sql .= " AND DATE(d.donation_date) <= '$date_to_safe'";
}

$sql .= " ORDER BY d.id DESC";

$result = mysqli_query($conn, $sql);

while($row = mysqli_fetch_assoc($result)){
    $bloodBankDisplay = $row['institution_name'] ?: $row['blood_bank_name'];

    fputcsv($output, [
        $row['id'],
        $row['donor_name'],
        $row['donor_email'],
        $row['donation_type'] ?? '',
        $row['blood_group'] ?? 'N/A',
        $row['branch_name'] ?? 'Not assigned',
        $bloodBankDisplay ?? '',
        $row['location'] ?? 'N/A',
        $row['quantity'] ?? 'N/A',
        $row['scheduled_date'] ?? '',
        $row['completed_at'] ?? '',
        $row['donation_date'] ?? '',
        $row['notes'] ?? '',
        $row['status'] ?? ''
    ]);
}

fclose($output);
exit();
?>
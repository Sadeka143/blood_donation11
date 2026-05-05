<?php
session_start();
include '../config/db.php';

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin'){
    header("Location: ../auth/login.php");
    exit();
}

$donor_search = isset($_GET['donor_search']) ? trim($_GET['donor_search']) : "";
$recipient_search = isset($_GET['recipient_search']) ? trim($_GET['recipient_search']) : "";
$blood_group = isset($_GET['blood_group']) ? trim($_GET['blood_group']) : "all";
$status_filter = isset($_GET['status']) ? trim($_GET['status']) : "all";
$location_search = isset($_GET['location_search']) ? trim($_GET['location_search']) : "";
$date_from = isset($_GET['date_from']) ? trim($_GET['date_from']) : "";
$date_to = isset($_GET['date_to']) ? trim($_GET['date_to']) : "";

$allowed_groups = ['all','A+','A-','B+','B-','O+','O-','AB+','AB-'];
$allowed_status = ['all','confirmed','cancelled'];

if(!in_array($blood_group, $allowed_groups)) $blood_group = 'all';
if(!in_array($status_filter, $allowed_status)) $status_filter = 'all';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=donations_filtered_report.csv');

$output = fopen('php://output', 'w');

fputcsv($output, [
    'Donation ID','Donor Name','Donor Email','Recipient Name','Blood Group','Location','Quantity',
    'Blood Bank','Scheduled Date','Completed At','Donation Date','Notes','Status'
]);

$sql = "
    SELECT 
        d.id, d.donation_date, d.scheduled_date, d.completed_at, d.notes, d.status,
        donor.name AS donor_name, donor.email AS donor_email,
        rec.name AS recipient_name,
        r.blood_group, r.location, r.quantity,
        bb.name AS blood_bank_name, bb.institution_name
    FROM donations d
    JOIN users donor ON d.donor_id = donor.id
    JOIN blood_requests r ON d.request_id = r.id
    JOIN users rec ON r.recipient_id = rec.id
    LEFT JOIN users bb ON d.blood_bank_id = bb.id
    WHERE 1=1
";

if($donor_search !== ""){
    $donor_search_safe = mysqli_real_escape_string($conn, $donor_search);
    $sql .= " AND donor.name LIKE '%$donor_search_safe%'";
}
if($recipient_search !== ""){
    $recipient_search_safe = mysqli_real_escape_string($conn, $recipient_search);
    $sql .= " AND rec.name LIKE '%$recipient_search_safe%'";
}
if($blood_group !== "all"){
    $blood_group_safe = mysqli_real_escape_string($conn, $blood_group);
    $sql .= " AND r.blood_group = '$blood_group_safe'";
}
if($status_filter !== "all"){
    $status_safe = mysqli_real_escape_string($conn, $status_filter);
    $sql .= " AND d.status = '$status_safe'";
}
if($location_search !== ""){
    $location_search_safe = mysqli_real_escape_string($conn, $location_search);
    $sql .= " AND r.location LIKE '%$location_search_safe%'";
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
        $row['recipient_name'],
        $row['blood_group'],
        $row['location'],
        $row['quantity'],
        $bloodBankDisplay,
        $row['scheduled_date'],
        $row['completed_at'],
        $row['donation_date'],
        $row['notes'],
        $row['status']
    ]);
}

fclose($output);
exit();
?>
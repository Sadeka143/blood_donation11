<?php
session_start();
include '../config/db.php';

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin'){
    header("Location: ../auth/login.php");
    exit();
}

$recipient_search = isset($_GET['recipient_search']) ? trim($_GET['recipient_search']) : "";
$donor_search = isset($_GET['donor_search']) ? trim($_GET['donor_search']) : "";
$blood_group = isset($_GET['blood_group']) ? trim($_GET['blood_group']) : "all";
$status_filter = isset($_GET['status']) ? trim($_GET['status']) : "all";
$location_search = isset($_GET['location_search']) ? trim($_GET['location_search']) : "";
$date_from = isset($_GET['date_from']) ? trim($_GET['date_from']) : "";
$date_to = isset($_GET['date_to']) ? trim($_GET['date_to']) : "";

$allowed_groups = ['all','A+','A-','B+','B-','O+','O-','AB+','AB-'];
$allowed_status = ['all','scheduled','confirmed','declined','completed','cancelled'];

if(!in_array($blood_group, $allowed_groups)) $blood_group = 'all';
if(!in_array($status_filter, $allowed_status)) $status_filter = 'all';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=appointments_filtered_report.csv');

$output = fopen('php://output', 'w');

fputcsv($output, [
    'Appointment ID','Request ID','Recipient','Donor','Blood Group','Quantity','Urgency',
    'Blood Bank','Appointment Date','Appointment Location','Status'
]);

$sql = "
    SELECT a.*, 
           r.blood_group, 
           r.quantity,
           r.urgency,
           u.name AS recipient_name, 
           d.name AS donor_name, 
           bb.name AS blood_bank_name, 
           bb.institution_name
    FROM appointments a
    JOIN blood_requests r ON a.request_id = r.id
    JOIN users u ON a.recipient_id = u.id
    JOIN users d ON a.donor_id = d.id
    JOIN users bb ON a.blood_bank_id = bb.id
    WHERE 1=1
";

if($recipient_search !== ""){
    $recipient_search_safe = mysqli_real_escape_string($conn, $recipient_search);
    $sql .= " AND u.name LIKE '%$recipient_search_safe%'";
}
if($donor_search !== ""){
    $donor_search_safe = mysqli_real_escape_string($conn, $donor_search);
    $sql .= " AND d.name LIKE '%$donor_search_safe%'";
}
if($blood_group !== "all"){
    $blood_group_safe = mysqli_real_escape_string($conn, $blood_group);
    $sql .= " AND r.blood_group = '$blood_group_safe'";
}
if($status_filter !== "all"){
    $status_safe = mysqli_real_escape_string($conn, $status_filter);
    $sql .= " AND a.status = '$status_safe'";
}
if($location_search !== ""){
    $location_search_safe = mysqli_real_escape_string($conn, $location_search);
    $sql .= " AND a.appointment_location LIKE '%$location_search_safe%'";
}
if($date_from !== ""){
    $date_from_safe = mysqli_real_escape_string($conn, $date_from);
    $sql .= " AND DATE(a.appointment_date) >= '$date_from_safe'";
}
if($date_to !== ""){
    $date_to_safe = mysqli_real_escape_string($conn, $date_to);
    $sql .= " AND DATE(a.appointment_date) <= '$date_to_safe'";
}

$sql .= " ORDER BY a.appointment_date DESC";

$result = mysqli_query($conn, $sql);

while($row = mysqli_fetch_assoc($result)){
    $bloodBankDisplay = $row['institution_name'] ?: $row['blood_bank_name'];

    fputcsv($output, [
        $row['id'],
        $row['request_id'],
        $row['recipient_name'],
        $row['donor_name'],
        $row['blood_group'],
        $row['quantity'],
        $row['urgency'],
        $bloodBankDisplay,
        $row['appointment_date'],
        $row['appointment_location'],
        $row['status']
    ]);
}

fclose($output);
exit();
?>
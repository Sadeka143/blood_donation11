<?php
session_start();
include '../config/db.php';

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin'){
    header("Location: ../auth/login.php");
    exit();
}

$request_status = isset($_GET['request_status']) ? trim($_GET['request_status']) : 'all';
$blood_group = isset($_GET['blood_group']) ? trim($_GET['blood_group']) : 'all';
$urgency = isset($_GET['urgency']) ? trim($_GET['urgency']) : 'all';
$location_search = isset($_GET['location_search']) ? trim($_GET['location_search']) : '';
$date_from = isset($_GET['date_from']) ? trim($_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? trim($_GET['date_to']) : '';

$allowed_status = ['all','pending_review','approved','rejected','matched','scheduled','completed','cancelled'];
$allowed_groups = ['all','A+','A-','B+','B-','O+','O-','AB+','AB-'];
$allowed_urgency = ['all','normal','urgent'];

if(!in_array($request_status, $allowed_status)) $request_status = 'all';
if(!in_array($blood_group, $allowed_groups)) $blood_group = 'all';
if(!in_array($urgency, $allowed_urgency)) $urgency = 'all';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=filtered_requests_report.csv');

$output = fopen('php://output', 'w');
fputcsv($output, [
    'Request ID','Recipient Name','Recipient Email','Blood Group','Location','Quantity','Urgency',
    'Status','Matched Donor','Blood Bank','Approved At','Appointment Date','Appointment Location',
    'Rejection Reason','Created At'
]);

$sql = "
    SELECT 
        r.id, r.blood_group, r.location, r.quantity, r.urgency, r.status, r.created_at, r.approved_at, r.rejection_reason,
        u.name AS recipient_name, u.email AS recipient_email,
        d.name AS donor_name,
        bb.name AS blood_bank_name, bb.institution_name,
        a.appointment_date, a.appointment_location
    FROM blood_requests r
    JOIN users u ON r.recipient_id = u.id
    LEFT JOIN users d ON r.matched_donor_id = d.id
    LEFT JOIN users bb ON r.assigned_blood_bank_id = bb.id
    LEFT JOIN appointments a ON r.id = a.request_id
    WHERE 1=1
";

if($request_status != 'all'){
    $request_status_safe = mysqli_real_escape_string($conn, $request_status);
    $sql .= " AND r.status = '$request_status_safe'";
}
if($blood_group != 'all'){
    $blood_group_safe = mysqli_real_escape_string($conn, $blood_group);
    $sql .= " AND r.blood_group = '$blood_group_safe'";
}
if($urgency != 'all'){
    $urgency_safe = mysqli_real_escape_string($conn, $urgency);
    $sql .= " AND r.urgency = '$urgency_safe'";
}
if($location_search != ''){
    $location_search_safe = mysqli_real_escape_string($conn, $location_search);
    $sql .= " AND r.location LIKE '%$location_search_safe%'";
}
if($date_from != ''){
    $date_from_safe = mysqli_real_escape_string($conn, $date_from);
    $sql .= " AND DATE(r.created_at) >= '$date_from_safe'";
}
if($date_to != ''){
    $date_to_safe = mysqli_real_escape_string($conn, $date_to);
    $sql .= " AND DATE(r.created_at) <= '$date_to_safe'";
}

$sql .= " ORDER BY r.created_at DESC";
$result = mysqli_query($conn, $sql);

while($row = mysqli_fetch_assoc($result)){
    $bloodBankDisplay = $row['institution_name'] ?: $row['blood_bank_name'];

    fputcsv($output, [
        $row['id'],
        $row['recipient_name'],
        $row['recipient_email'],
        $row['blood_group'],
        $row['location'],
        $row['quantity'],
        $row['urgency'],
        $row['status'],
        $row['donor_name'],
        $bloodBankDisplay,
        $row['approved_at'],
        $row['appointment_date'],
        $row['appointment_location'],
        $row['rejection_reason'],
        $row['created_at']
    ]);
}

fclose($output);
exit();
?>
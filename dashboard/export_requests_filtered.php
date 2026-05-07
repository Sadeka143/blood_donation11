<?php
session_start();
include '../config/db.php';

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin'){
    header("Location: ../auth/login.php");
    exit();
}

$recipient_search = isset($_GET['recipient_search']) ? trim($_GET['recipient_search']) : "";
$request_status = isset($_GET['request_status']) ? trim($_GET['request_status']) : "all";
$blood_group = isset($_GET['blood_group']) ? trim($_GET['blood_group']) : "all";
$urgency = isset($_GET['urgency']) ? trim($_GET['urgency']) : "all";
$fulfillment_source = isset($_GET['fulfillment_source']) ? trim($_GET['fulfillment_source']) : "all";
$location_search = isset($_GET['location_search']) ? trim($_GET['location_search']) : "";
$date_from = isset($_GET['date_from']) ? trim($_GET['date_from']) : "";
$date_to = isset($_GET['date_to']) ? trim($_GET['date_to']) : "";

$allowed_status = ['all','pending_review','approved','rejected','matched','scheduled','completed','cancelled'];
$allowed_groups = ['all','A+','A-','B+','B-','O+','O-','AB+','AB-'];
$allowed_urgency = ['all','normal','urgent'];
$allowed_source = ['all','none','stock','donor'];

if(!in_array($request_status, $allowed_status)) $request_status = 'all';
if(!in_array($blood_group, $allowed_groups)) $blood_group = 'all';
if(!in_array($urgency, $allowed_urgency)) $urgency = 'all';
if(!in_array($fulfillment_source, $allowed_source)) $fulfillment_source = 'all';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=requests_filtered_report.csv');

$output = fopen('php://output', 'w');

fputcsv($output, [
    'Request ID',
    'Recipient Name',
    'Recipient Email',
    'Blood Group',
    'Location',
    'Quantity',
    'Urgency',
    'Status',
    'Fulfillment Source',
    'Matched Donor',
    'Blood Bank',
    'Branch',
    'Approved At',
    'Created At'
]);

$sql = "
    SELECT 
        r.*,
        u.name AS recipient_name,
        u.email AS recipient_email,
        d.name AS donor_name,
        bb.name AS blood_bank_name,
        bb.institution_name,
        b.branch_name
    FROM blood_requests r
    JOIN users u ON r.recipient_id = u.id
    LEFT JOIN users d ON r.matched_donor_id = d.id
    LEFT JOIN users bb ON r.assigned_blood_bank_id = bb.id
    LEFT JOIN branches b ON r.fulfilled_branch_id = b.id
    WHERE 1=1
";

if($recipient_search !== ""){
    $recipient_safe = mysqli_real_escape_string($conn, $recipient_search);
    $sql .= " AND u.name LIKE '%$recipient_safe%'";
}

if($blood_group !== "all"){
    $group_safe = mysqli_real_escape_string($conn, $blood_group);
    $sql .= " AND r.blood_group = '$group_safe'";
}

if($request_status !== "all"){
    $status_safe = mysqli_real_escape_string($conn, $request_status);
    $sql .= " AND r.status = '$status_safe'";
}

if($urgency !== "all"){
    $urgency_safe = mysqli_real_escape_string($conn, $urgency);
    $sql .= " AND r.urgency = '$urgency_safe'";
}

if($fulfillment_source !== "all"){
    $source_safe = mysqli_real_escape_string($conn, $fulfillment_source);
    $sql .= " AND r.fulfillment_source = '$source_safe'";
}

if($location_search !== ""){
    $location_safe = mysqli_real_escape_string($conn, $location_search);
    $sql .= " AND (
        r.location LIKE '%$location_safe%' OR
        r.address_line LIKE '%$location_safe%' OR
        r.city LIKE '%$location_safe%' OR
        r.zipcode LIKE '%$location_safe%'
    )";
}

if($date_from !== ""){
    $date_from_safe = mysqli_real_escape_string($conn, $date_from);
    $sql .= " AND DATE(r.created_at) >= '$date_from_safe'";
}

if($date_to !== ""){
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
        $row['fulfillment_source'] ?? '',
        $row['donor_name'] ?? '',
        $bloodBankDisplay ?? '',
        $row['branch_name'] ?? '',
        $row['approved_at'] ?? '',
        $row['created_at'] ?? ''
    ]);
}

fclose($output);
exit();
?>
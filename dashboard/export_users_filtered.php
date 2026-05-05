<?php
session_start();
include '../config/db.php';

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin'){
    header("Location: ../auth/login.php");
    exit();
}

$user_search = isset($_GET['user_search']) ? trim($_GET['user_search']) : "";
$role_filter = isset($_GET['role']) ? trim($_GET['role']) : "all";
$status_filter = isset($_GET['account_status']) ? trim($_GET['account_status']) : "all";
$availability_filter = isset($_GET['availability']) ? trim($_GET['availability']) : "all";

$allowed_roles = ['all','admin','blood_bank','donor','recipient'];
$allowed_status = ['all','active','inactive'];
$allowed_availability = ['all','available','not_available'];

if(!in_array($role_filter, $allowed_roles)) $role_filter = 'all';
if(!in_array($status_filter, $allowed_status)) $status_filter = 'all';
if(!in_array($availability_filter, $allowed_availability)) $availability_filter = 'all';

$user_search_safe = mysqli_real_escape_string($conn, $user_search);

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=users_filtered_report.csv');

$output = fopen('php://output', 'w');

fputcsv($output, [
    'ID','Name','Email','Contact Number','Role','Blood Group',
    'Location','Institution Name','Availability','Account Status','Created At'
]);

$sql = "SELECT * FROM users WHERE 1=1";

if($user_search_safe !== ""){
    $sql .= " AND (
        name LIKE '%$user_search_safe%' OR
        email LIKE '%$user_search_safe%' OR
        role LIKE '%$user_search_safe%' OR
        location LIKE '%$user_search_safe%' OR
        institution_name LIKE '%$user_search_safe%' OR
        blood_group LIKE '%$user_search_safe%'
    )";
}

if($role_filter !== "all"){
    $role_safe = mysqli_real_escape_string($conn, $role_filter);
    $sql .= " AND role = '$role_safe'";
}

if($status_filter !== "all"){
    $status_safe = mysqli_real_escape_string($conn, $status_filter);
    $sql .= " AND account_status = '$status_safe'";
}

if($availability_filter !== "all"){
    $availability_safe = mysqli_real_escape_string($conn, $availability_filter);
    $sql .= " AND availability = '$availability_safe'";
}

$sql .= " ORDER BY id DESC";

$result = mysqli_query($conn, $sql);

while($row = mysqli_fetch_assoc($result)){
    fputcsv($output, [
        $row['id'],
        $row['name'],
        $row['email'],
        $row['contact_number'] ?? '',
        $row['role'],
        $row['blood_group'] ?? '',
        $row['location'] ?? '',
        $row['institution_name'] ?? '',
        $row['availability'] ?? '',
        $row['account_status'] ?? '',
        $row['created_at'] ?? ''
    ]);
}

fclose($output);
exit();
?>
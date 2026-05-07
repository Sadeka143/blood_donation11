<?php
session_start();
include '../config/db.php';

// Only admin can export user data
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin'){
    header("Location: ../auth/login.php");
    exit();
}

// Receive current filter values from admin_users.php
$search = isset($_GET['search']) ? trim($_GET['search']) : "";
$role_filter = isset($_GET['role']) ? trim($_GET['role']) : "all";
$blood_group = isset($_GET['blood_group']) ? trim($_GET['blood_group']) : "all";
$city = isset($_GET['city']) ? trim($_GET['city']) : "";

$allowed_roles = ['all','admin','blood_bank','donor','recipient'];
$allowed_groups = ['all','A+','A-','B+','B-','O+','O-','AB+','AB-'];

if(!in_array($role_filter, $allowed_roles)) $role_filter = 'all';
if(!in_array($blood_group, $allowed_groups)) $blood_group = 'all';

$search_safe = mysqli_real_escape_string($conn, $search);
$city_safe = mysqli_real_escape_string($conn, $city);

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=users_filtered_report.csv');

$output = fopen('php://output', 'w');

fputcsv($output, [
    'ID',
    'Name',
    'Email',
    'Contact Number',
    'Role',
    'Blood Group',
    'Location',
    'City',
    'Zipcode',
    'Institution Name',
    'Availability',
    'Account Status',
    'Created At'
]);

$sql = "SELECT * FROM users WHERE 1=1";

if($search_safe !== ""){
    $sql .= " AND (
        name LIKE '%$search_safe%' OR
        email LIKE '%$search_safe%' OR
        institution_name LIKE '%$search_safe%'
    )";
}

if($role_filter !== "all"){
    $role_safe = mysqli_real_escape_string($conn, $role_filter);
    $sql .= " AND role = '$role_safe'";
}

if($blood_group !== "all"){
    $group_safe = mysqli_real_escape_string($conn, $blood_group);
    $sql .= " AND blood_group = '$group_safe'";
}

if($city_safe !== ""){
    $sql .= " AND city LIKE '%$city_safe%'";
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
        $row['city'] ?? '',
        $row['zipcode'] ?? '',
        $row['institution_name'] ?? '',
        $row['availability'] ?? '',
        $row['status'] ?? '',
        $row['created_at'] ?? ''
    ]);
}

fclose($output);
exit();
?>
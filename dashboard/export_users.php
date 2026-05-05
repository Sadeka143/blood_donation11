<?php
session_start();
include '../config/db.php';

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin'){
    header("Location: ../auth/login.php");
    exit();
}

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=users_report.csv');

$output = fopen('php://output', 'w');

fputcsv($output, ['ID','Name','Email','Contact Number','Role','Blood Group','Location','Institution Name','Availability','Date of Birth','Weight (kg)','Created At']);

$sql = "SELECT id, name, email, contact_number, role, blood_group, location, institution_name, availability, date_of_birth, weight_kg, created_at FROM users ORDER BY id DESC";
$result = mysqli_query($conn, $sql);

while($row = mysqli_fetch_assoc($result)){
    fputcsv($output, [
        $row['id'],
        $row['name'],
        $row['email'],
        $row['contact_number'],
        $row['role'],
        $row['blood_group'],
        $row['location'],
        $row['institution_name'],
        $row['availability'],
        $row['date_of_birth'],
        $row['weight_kg'],
        $row['created_at']
    ]);
}

fclose($output);
exit();
?>
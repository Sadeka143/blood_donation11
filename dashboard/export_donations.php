<?php
session_start();
include '../config/db.php';

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin'){
    header("Location: ../auth/login.php");
    exit();
}

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=donations_report.csv');

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
    ORDER BY d.id DESC
";

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
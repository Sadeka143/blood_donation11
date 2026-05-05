<?php
session_start();
include '../config/db.php';

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin'){
    header("Location: ../auth/login.php");
    exit();
}

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=blood_requests_report.csv');

$output = fopen('php://output', 'w');

fputcsv($output, [
    'Request ID','Recipient Name','Recipient Email','Blood Group','Location','Quantity','Urgency',
    'Status','Matched Donor','Blood Bank','Approved At','Appointment Date','Appointment Location','Rejection Reason','Created At'
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
    ORDER BY r.created_at DESC
";

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
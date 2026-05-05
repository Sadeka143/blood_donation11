<?php

// Calculate donor age from date of birth
function calculateAge($dateOfBirth) {
    if (empty($dateOfBirth)) {
        return null;
    }

    $dob = new DateTime($dateOfBirth);
    $today = new DateTime();

    return $today->diff($dob)->y;
}

// Check donor eligibility using age, weight, and 3-month donation gap
function checkDonorEligibility($conn, $donorId, $dateOfBirth, $weightKg) {
    $result = [
        'eligible' => true,
        'reason' => '',
        'age' => null,
        'last_donation_date' => null,
        'next_eligible_date' => null
    ];

    // Check donor age
    $age = calculateAge($dateOfBirth);
    $result['age'] = $age;

    if ($age === null) {
        $result['eligible'] = false;
        $result['reason'] = 'Date of birth is missing.';
        return $result;
    }

    if ($age < 18 || $age > 65) {
        $result['eligible'] = false;
        $result['reason'] = 'Donor age must be between 18 and 65.';
        return $result;
    }

    // Check donor weight
    if ($weightKg === null || $weightKg < 50) {
        $result['eligible'] = false;
        $result['reason'] = 'Donor weight must be at least 50 kg.';
        return $result;
    }

    // Check latest completed donation from donations table
    $stmt = mysqli_prepare($conn, "
        SELECT donation_date
        FROM donations
        WHERE donor_id = ? AND status = 'confirmed'
        ORDER BY donation_date DESC, id DESC
        LIMIT 1
    ");
    mysqli_stmt_bind_param($stmt, "i", $donorId);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);

    if ($row = mysqli_fetch_assoc($res)) {
        $lastDonationDate = $row['donation_date'];
        $result['last_donation_date'] = $lastDonationDate;

        $lastDate = new DateTime($lastDonationDate);
        $nextEligible = clone $lastDate;
        $nextEligible->modify('+3 months');

        $today = new DateTime();
        $result['next_eligible_date'] = $nextEligible->format('Y-m-d');

        // Donor cannot donate again before next eligible date
        if ($today < $nextEligible) {
            $result['eligible'] = false;
            $result['reason'] = 'Donor is not yet eligible because the last donation was within 3 months.';
            mysqli_stmt_close($stmt);
            return $result;
        }
    }

    mysqli_stmt_close($stmt);

    return $result;
}

// Get donor eligibility without changing database status
function getDonorEligibilitySnapshot($conn, $donorId) {
    $stmt = mysqli_prepare($conn, "
        SELECT date_of_birth, weight_kg, availability, next_eligible_date
        FROM users
        WHERE id = ? AND role = 'donor'
    ");
    mysqli_stmt_bind_param($stmt, "i", $donorId);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($res) !== 1) {
        mysqli_stmt_close($stmt);
        return [
            'eligible' => false,
            'reason' => 'Donor not found.',
            'age' => null,
            'last_donation_date' => null,
            'next_eligible_date' => null,
            'availability' => 'not_available'
        ];
    }

    $donor = mysqli_fetch_assoc($res);
    mysqli_stmt_close($stmt);

    $eligibility = checkDonorEligibility(
        $conn,
        $donorId,
        $donor['date_of_birth'] ?? null,
        $donor['weight_kg'] ?? null
    );

    $eligibility['availability'] = $donor['availability'] ?? 'not_available';

    return $eligibility;
}

// Sync donor availability after donation completion or before appointment confirmation
function syncDonorAvailabilityStatus($conn, $donorId) {
    $stmt = mysqli_prepare($conn, "
        SELECT date_of_birth, weight_kg, availability, next_eligible_date
        FROM users
        WHERE id = ? AND role = 'donor'
    ");
    mysqli_stmt_bind_param($stmt, "i", $donorId);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($res) !== 1) {
        mysqli_stmt_close($stmt);
        return [
            'eligible' => false,
            'reason' => 'Donor not found.',
            'age' => null,
            'last_donation_date' => null,
            'next_eligible_date' => null,
            'availability' => 'not_available'
        ];
    }

    $donor = mysqli_fetch_assoc($res);
    mysqli_stmt_close($stmt);

    // Recalculate eligibility from latest confirmed donation
    $eligibility = checkDonorEligibility(
        $conn,
        $donorId,
        $donor['date_of_birth'] ?? null,
        $donor['weight_kg'] ?? null
    );

    $desiredAvailability = $eligibility['eligible'] ? 'available' : 'not_available';
    $nextEligibleDate = $eligibility['next_eligible_date'] ?? null;

    // Store donor availability and next eligible date in users table
    $update_stmt = mysqli_prepare($conn, "
        UPDATE users
        SET availability = ?, next_eligible_date = ?
        WHERE id = ? AND role = 'donor'
    ");
    mysqli_stmt_bind_param($update_stmt, "ssi", $desiredAvailability, $nextEligibleDate, $donorId);
    mysqli_stmt_execute($update_stmt);
    mysqli_stmt_close($update_stmt);

    $eligibility['availability'] = $desiredAvailability;

    return $eligibility;
}
?>
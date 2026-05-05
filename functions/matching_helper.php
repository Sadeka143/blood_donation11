<?php

function normalizeTextValue($value){
    return strtolower(trim((string)$value));
}

function calculateDonorPriorityScore(array $request, array $donor, array $eligibility){
    $score = 0;
    $reasons = [];

    $requestZip = normalizeTextValue($request['zipcode'] ?? '');
    $requestCity = normalizeTextValue($request['city'] ?? '');

    $donorZip = normalizeTextValue($donor['zipcode'] ?? '');
    $donorCity = normalizeTextValue($donor['city'] ?? '');

    if($requestZip !== '' && $donorZip !== '' && $requestZip === $donorZip){
        $score += 50;
        $reasons[] = 'Same zipcode';
    }

    if($requestCity !== '' && $donorCity !== '' && $requestCity === $donorCity){
        $score += 30;
        $reasons[] = 'Same city';
    }

    if(($donor['availability'] ?? '') === 'available'){
        $score += 10;
        $reasons[] = 'Available';
    }

    if(!empty($eligibility['eligible'])){
        $score += 20;
        $reasons[] = 'Eligible';
    } else {
        $score -= 100;
        $reasons[] = 'Not eligible';
    }

    return [
        'score' => $score,
        'reasons' => $reasons
    ];
}

function getPriorityBadgeClass($score){
    if($score >= 80){
        return 'badge-completed';
    }
    if($score >= 40){
        return 'badge-accepted';
    }
    return 'badge-pending';
}

function getPriorityLabel($score){
    if($score >= 80){
        return 'Top Match';
    }
    if($score >= 40){
        return 'Good Match';
    }
    return 'Standard';
}
?>
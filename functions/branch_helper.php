<?php

if (!function_exists('normalizeBranchValue')) {
    function normalizeBranchValue($value){
        return strtolower(trim((string)$value));
    }
}

if (!function_exists('buildBranchFullLocation')) {
    function buildBranchFullLocation($branch){
        $parts = array_filter([
            $branch['address_line'] ?? '',
            $branch['city'] ?? '',
            $branch['zipcode'] ?? ''
        ], function($v){
            return trim((string)$v) !== '';
        });

        return implode(', ', $parts);
    }
}

if (!function_exists('getNearestBranchForLocation')) {
    function getNearestBranchForLocation($conn, $bloodBankUserId, $city, $zipcode){
        $cityNorm = normalizeBranchValue($city);
        $zipNorm = normalizeBranchValue($zipcode);

        $sql = "SELECT * FROM branches WHERE blood_bank_user_id = ? AND is_active = 1";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $bloodBankUserId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        $bestBranch = null;
        $bestScore = -1;

        while($branch = mysqli_fetch_assoc($result)){
            $score = 0;

            if($zipNorm !== '' && normalizeBranchValue($branch['zipcode']) === $zipNorm){
                $score += 50;
            }

            if($cityNorm !== '' && normalizeBranchValue($branch['city']) === $cityNorm){
                $score += 30;
            }

            if($score > $bestScore){
                $bestScore = $score;
                $bestBranch = $branch;
            }
        }

        mysqli_stmt_close($stmt);

        if($bestBranch !== null){
            return $bestBranch;
        }

        $fallback = mysqli_query(
            $conn,
            "SELECT * FROM branches WHERE blood_bank_user_id = " . (int)$bloodBankUserId . " AND is_active = 1 ORDER BY id ASC LIMIT 1"
        );

        return mysqli_fetch_assoc($fallback);
    }
}
?>
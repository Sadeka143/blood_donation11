<?php
include_once 'branch_helper.php';

if (!function_exists('getBranchStockSummary')) {
    function getBranchStockSummary($conn, $bloodBankUserId){
        $sql = "
            SELECT b.id, b.branch_name, b.city, b.zipcode, COALESCE(SUM(bs.units_available), 0) AS total_units
            FROM branches b
            LEFT JOIN blood_stock bs ON b.id = bs.branch_id
            WHERE b.blood_bank_user_id = ?
            GROUP BY b.id, b.branch_name, b.city, b.zipcode
            ORDER BY b.branch_name ASC
        ";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $bloodBankUserId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        $rows = [];
        while($row = mysqli_fetch_assoc($result)){
            $rows[] = $row;
        }

        mysqli_stmt_close($stmt);
        return $rows;
    }
}

if (!function_exists('getTotalNetworkStockUnits')) {
    function getTotalNetworkStockUnits($conn, $bloodBankUserId){
        $sql = "
            SELECT COALESCE(SUM(bs.units_available), 0) AS total_units
            FROM blood_stock bs
            JOIN branches b ON bs.branch_id = b.id
            WHERE b.blood_bank_user_id = ?
        ";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $bloodBankUserId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        return (int)($row['total_units'] ?? 0);
    }
}

if (!function_exists('findNearestBranchWithStock')) {
    function findNearestBranchWithStock($conn, $bloodBankUserId, $bloodGroup, $requiredUnits, $city, $zipcode){
        $cityNorm = strtolower(trim((string)$city));
        $zipNorm = strtolower(trim((string)$zipcode));
        $requiredUnits = (int)$requiredUnits;

        $sql = "
            SELECT 
                b.*,
                bs.units_available
            FROM branches b
            JOIN blood_stock bs ON b.id = bs.branch_id
            WHERE b.blood_bank_user_id = ?
              AND b.is_active = 1
              AND bs.blood_group = ?
              AND bs.units_available >= ?
        ";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "isi", $bloodBankUserId, $bloodGroup, $requiredUnits);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        $bestBranch = null;
        $bestScore = -1;

        while($branch = mysqli_fetch_assoc($result)){
            $score = 0;

            if($zipNorm !== '' && strtolower(trim((string)$branch['zipcode'])) === $zipNorm){
                $score += 50;
            }

            if($cityNorm !== '' && strtolower(trim((string)$branch['city'])) === $cityNorm){
                $score += 30;
            }

            $score += (int)$branch['units_available'];

            if($score > $bestScore){
                $bestScore = $score;
                $bestBranch = $branch;
            }
        }

        mysqli_stmt_close($stmt);
        return $bestBranch;
    }
}

if (!function_exists('reduceBranchStock')) {
    function reduceBranchStock($conn, $branchId, $bloodGroup, $units){
        $units = (int)$units;

        $stmt = mysqli_prepare($conn, "
            UPDATE blood_stock
            SET units_available = units_available - ?
            WHERE branch_id = ? AND blood_group = ? AND units_available >= ?
        ");
        mysqli_stmt_bind_param($stmt, "iisi", $units, $branchId, $bloodGroup, $units);
        mysqli_stmt_execute($stmt);
        $affected = mysqli_stmt_affected_rows($stmt);
        mysqli_stmt_close($stmt);

        return $affected > 0;
    }
}

if (!function_exists('increaseBranchStock')) {
    function increaseBranchStock($conn, $branchId, $bloodGroup, $units){
        $units = (int)$units;

        $stmt = mysqli_prepare($conn, "
            INSERT INTO blood_stock (branch_id, blood_group, units_available)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE units_available = units_available + VALUES(units_available)
        ");
        mysqli_stmt_bind_param($stmt, "isi", $branchId, $bloodGroup, $units);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        return $ok;
    }
}
?>
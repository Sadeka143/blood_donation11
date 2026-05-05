<?php

function getUrgencyOrderSql($column = 'urgency'){
    return "CASE WHEN {$column} = 'urgent' THEN 0 ELSE 1 END";
}

function getUrgencyBadgeClass($urgency){
    return strtolower(trim((string)$urgency)) === 'urgent' ? 'urgent-badge' : 'normal-badge';
}

function getUrgencyLabel($urgency){
    return strtolower(trim((string)$urgency)) === 'urgent' ? 'Urgent' : 'Normal';
}
?>
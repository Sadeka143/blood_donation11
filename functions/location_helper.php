<?php

function buildFullLocation($address = '', $city = '', $zipcode = '', $fallback = ''){
    $parts = array_filter([
        trim((string)$address),
        trim((string)$city),
        trim((string)$zipcode)
    ], function($v){
        return $v !== '';
    });

    if(!empty($parts)){
        return implode(', ', $parts);
    }

    return trim((string)$fallback);
}

function buildMapUrl($address = '', $city = '', $zipcode = '', $fallback = ''){
    $fullLocation = buildFullLocation($address, $city, $zipcode, $fallback);

    if($fullLocation === ''){
        return '';
    }

    return "https://www.google.com/maps?q=" . urlencode($fullLocation);
}

function buildEmbedMapUrl($address = '', $city = '', $zipcode = '', $fallback = ''){
    $fullLocation = buildFullLocation($address, $city, $zipcode, $fallback);

    if($fullLocation === ''){
        return '';
    }

    return "https://maps.google.com/maps?q=" . urlencode($fullLocation) . "&output=embed";
}
?>
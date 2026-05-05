<?php

function getSystemName(){
    return 'Blood Donation Management System';
}

function getSystemTagline(){
    return 'Donate Blood, Save Lives';
}

function getBloodBankNetworkName(){
    return 'Central Blood Bank Network';
}

function getBloodBankWelcomeLabel(){
    return 'Central Blood Bank Network';
}

/*
 * For this redesigned system, all blood_bank role displays
 * should use the unified network label instead of old stored names.
 */
function getBloodBankDisplayName($institutionName = '', $userName = ''){
    return getBloodBankNetworkName();
}

function renderFooterTitle(){
    return 'Blood Donation Management System';
}

function renderFooterDescription(){
    return 'Blood Donation Management System is a final year project prototype that simulates donor, recipient, blood bank, branch coordination, stock monitoring, donor matching, and appointment management.';
}
?>
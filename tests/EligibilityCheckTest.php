<?php

use PHPUnit\Framework\TestCase;

// Unit tests for donor eligibility helper functions
final class EligibilityCheckTest extends TestCase
{
    // Check that age calculation works correctly
    public function testCalculateAgeReturnsCorrectAge(): void
    {
        $dateOfBirth = (new DateTime('-20 years'))->format('Y-m-d');

        $this->assertSame(20, calculateAge($dateOfBirth));
    }

    // Missing date of birth should make donor not eligible
    public function testMissingDateOfBirthMakesDonorNotEligible(): void
    {
        $result = checkDonorEligibility(null, 1, '', 60);

        $this->assertFalse($result['eligible']);
        $this->assertStringContainsString('Date of birth is missing', $result['reason']);
    }

    // Donor under 18 should not be eligible
    public function testUnderAgeDonorIsNotEligible(): void
    {
        $dateOfBirth = (new DateTime('-17 years'))->format('Y-m-d');

        $result = checkDonorEligibility(null, 1, $dateOfBirth, 60);

        $this->assertFalse($result['eligible']);
        $this->assertStringContainsString('between 18 and 65', $result['reason']);
    }

    // Donor below 50 kg should not be eligible
    public function testLowWeightDonorIsNotEligible(): void
    {
        $dateOfBirth = (new DateTime('-25 years'))->format('Y-m-d');

        $result = checkDonorEligibility(null, 1, $dateOfBirth, 45);

        $this->assertFalse($result['eligible']);
        $this->assertStringContainsString('50 kg', $result['reason']);
    }
}
<?php

use PHPUnit\Framework\TestCase;

// Unit tests for blood compatibility helper function
final class BloodCompatibilityTest extends TestCase
{
    // O- donor should be compatible with all recipient blood groups
    public function testONegativeDonorCanDonateToAllGroups(): void
    {
        $compatibleGroups = getCompatibleRecipientGroups('O-');

        $this->assertContains('A+', $compatibleGroups);
        $this->assertContains('AB+', $compatibleGroups);
        $this->assertCount(8, $compatibleGroups);
    }

    // A+ donor should donate only to A+ and AB+
    public function testAPositiveDonorCanDonateToAPositiveAndABPositive(): void
    {
        $compatibleGroups = getCompatibleRecipientGroups('A+');

        $this->assertSame(['A+', 'AB+'], $compatibleGroups);
    }

    // Invalid blood group should return empty result
    public function testInvalidBloodGroupReturnsEmptyArray(): void
    {
        $compatibleGroups = getCompatibleRecipientGroups('XYZ');

        $this->assertSame([], $compatibleGroups);
    }
}
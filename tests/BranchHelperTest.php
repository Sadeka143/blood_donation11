<?php

use PHPUnit\Framework\TestCase;

// Unit tests for branch helper functions
final class BranchHelperTest extends TestCase
{
    // Check full branch location formatting
    public function testBuildBranchFullLocationReturnsFormattedAddress(): void
    {
        $branch = [
            'address_line' => 'Italiensvej 1',
            'city' => 'Copenhagen',
            'zipcode' => '2300'
        ];

        $result = buildBranchFullLocation($branch);

        $this->assertSame('Italiensvej 1, Copenhagen, 2300', $result);
    }

    // Empty values should be skipped in location formatting
    public function testBuildBranchFullLocationSkipsEmptyValues(): void
    {
        $branch = [
            'address_line' => 'Blegdamsvej 56',
            'city' => '',
            'zipcode' => '2100'
        ];

        $result = buildBranchFullLocation($branch);

        $this->assertSame('Blegdamsvej 56, 2100', $result);
    }

    // Check branch value normalization
    public function testNormalizeBranchValueTrimsAndLowercasesText(): void
    {
        $result = normalizeBranchValue('  Copenhagen  ');

        $this->assertSame('copenhagen', $result);
    }
}
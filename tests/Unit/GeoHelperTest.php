<?php

namespace Tests\Unit;

use App\Helpers\GeoHelper;
use PHPUnit\Framework\TestCase;

class GeoHelperTest extends TestCase
{
    public function test_distance_calculation(): void
    {
        $distance = GeoHelper::distanceInMeters(12.9698, 77.7499, 12.9700, 77.7500);
        $this->assertLessThan(50, $distance);
    }

    public function test_within_radius(): void
    {
        $this->assertTrue(GeoHelper::isWithinRadius(12.9698, 77.7499, 12.9700, 77.7500, 100));
        $this->assertFalse(GeoHelper::isWithinRadius(12.9698, 77.7499, 13.0000, 77.8000, 100));
    }
}

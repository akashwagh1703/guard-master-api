<?php

namespace Database\Factories;

use App\Enums\RecordStatus;
use App\Models\Site;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Site> */
class SiteFactory extends Factory
{
    protected $model = Site::class;

    public function definition(): array
    {
        return [
            'name' => fake()->company() . ' Site',
            'client_name' => fake()->company(),
            'contact_person' => fake()->name(),
            'phone' => fake()->phoneNumber(),
            'address' => fake()->address(),
            'latitude' => fake()->latitude(12.8, 13.1),
            'longitude' => fake()->longitude(77.4, 77.8),
            'attendance_radius' => 100,
            'status' => RecordStatus::Active,
        ];
    }
}

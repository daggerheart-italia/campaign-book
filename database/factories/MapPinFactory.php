<?php

namespace Database\Factories;

use App\Models\Campaign;
use App\Models\CampaignMap;
use App\Models\MapPin;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MapPin>
 */
class MapPinFactory extends Factory
{
    public function definition(): array
    {
        return [
            'campaign_id' => Campaign::factory(),
            'campaign_map_id' => CampaignMap::factory(),
            'title' => fake()->word(),
            'x' => fake()->randomFloat(2, 0, 100),
            'y' => fake()->randomFloat(2, 0, 100),
            'description' => fake()->optional()->sentence(),
            'color' => '#e11d48',
            'created_by' => User::factory(),
            'locked' => false,
        ];
    }
}

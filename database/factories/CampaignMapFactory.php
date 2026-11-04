<?php

namespace Database\Factories;

use App\Models\Campaign;
use App\Models\CampaignMap;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CampaignMap>
 */
class CampaignMapFactory extends Factory
{
    public function definition(): array
    {
        return [
            'campaign_id' => Campaign::factory(),
            'title' => fake()->sentence(2),
            'image_path' => 'campaign-maps/'.fake()->uuid().'.webp',
            'is_default' => false,
        ];
    }
}

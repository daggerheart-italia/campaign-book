<?php

namespace Database\Factories;

use App\Models\Campaign;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Campaign>
 */
class CampaignFactory extends Factory
{
    public function definition(): array
    {
        return [
            'owner_id' => User::factory(),
            'name' => fake()->sentence(3),
            'description' => fake()->optional()->paragraph(),
            'system' => 'Daggerheart',
            'banner_url' => null,
            'invite_code_player' => Str::random(16),
            'invite_code_gm' => Str::random(16),
            'public_slug' => null,
        ];
    }
}

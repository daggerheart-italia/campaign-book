<?php

namespace Database\Factories;

use App\Models\Campaign;
use App\Models\SessionNote;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SessionNote>
 */
class SessionNoteFactory extends Factory
{
    public function definition(): array
    {
        return [
            'campaign_id' => Campaign::factory(),
            'user_id' => User::factory(),
            'title' => fake()->sentence(3),
            'content' => fake()->paragraphs(2, true),
            'session_date' => now()->toDateString(),
            'is_pinned' => false,
        ];
    }
}

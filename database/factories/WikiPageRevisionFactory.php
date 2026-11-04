<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\WikiPage;
use App\Models\WikiPageRevision;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WikiPageRevision>
 */
class WikiPageRevisionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'wiki_page_id' => WikiPage::factory(),
            'user_id' => User::factory(),
            'title' => fake()->sentence(4),
            'category' => fake()->randomElement(['Lore', 'NPC', 'Luogo', 'Oggetto', 'Altro']),
            'content' => fake()->paragraphs(3, true),
            'summary' => fake()->optional()->sentence(),
        ];
    }
}

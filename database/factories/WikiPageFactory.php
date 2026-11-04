<?php

namespace Database\Factories;

use App\Models\Campaign;
use App\Models\User;
use App\Models\WikiPage;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<WikiPage>
 */
class WikiPageFactory extends Factory
{
    public function definition(): array
    {
        $title = fake()->sentence(4);

        return [
            'campaign_id' => Campaign::factory(),
            'title' => $title,
            'slug' => Str::slug($title).'-'.Str::random(5),
            'category' => fake()->randomElement(['Lore', 'NPC', 'Luogo', 'Oggetto', 'Altro']),
            'parent_id' => null,
            'content' => fake()->paragraphs(3, true),
            'created_by' => User::factory(),
            'updated_by' => User::factory(),
        ];
    }
}

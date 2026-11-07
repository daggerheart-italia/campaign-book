<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class IconLabelTest extends TestCase
{
    public function test_icon_label_renders_each_visibility_combination(): void
    {
        $cases = [
            ['', 'icon-label-mobile'],
            [':show-on-mobile="true"', ''],
            [':hide-on-desktop="true"', 'icon-label-mobile icon-label-hide-desktop'],
            [':show-on-mobile="true" :hide-on-desktop="true"', 'sm:sr-only'],
        ];

        foreach ($cases as [$attributes, $expectedClass]) {
            $html = Blade::render('<x-icon-label icon="search" label="Cerca" '.$attributes.' />');

            $this->assertStringContainsString('class="'.$expectedClass.'">Cerca</span>', $html);
        }
    }

    public function test_icon_label_provides_a_browser_tooltip_when_hidden_on_desktop(): void
    {
        foreach ([
            ':hide-on-desktop="true"',
            ':show-on-mobile="true" :hide-on-desktop="true"',
        ] as $attributes) {
            $html = Blade::render('<x-icon-label icon="search" label="Cerca" '.$attributes.' />');

            $this->assertStringContainsString('title="Cerca"', $html);
        }

        $html = Blade::render('<x-icon-label icon="search" label="Cerca" />');

        $this->assertStringNotContainsString('title="Cerca"', $html);
    }
}

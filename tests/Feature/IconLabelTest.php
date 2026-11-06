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

            $this->assertStringContainsString('class="'.$expectedClass.'" data-tooltip="Cerca">Cerca</span>', $html);
        }
    }
}

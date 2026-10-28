<?php

namespace Tests\Feature;

use Tests\TestCase;

class ApplicationVersionTest extends TestCase
{
    public function test_landing_page_displays_the_version_from_the_version_file(): void
    {
        $versionContents = file_get_contents(base_path('VERSION'));
        $this->assertIsString($versionContents);
        $version = trim($versionContents);

        $response = $this->get(route('landing'));

        $response->assertOk();
        $response->assertSee($version);
    }
}

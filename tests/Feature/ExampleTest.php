<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_home_page_can_be_rendered_when_application_is_not_initialized(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }
}

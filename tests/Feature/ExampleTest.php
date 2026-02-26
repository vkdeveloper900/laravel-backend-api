<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_the_api_returns_successful_response(): void
    {
        $response = $this->getJson('/api/init');

        $response->assertStatus(200);
        $response->assertJson(['message' => 'Api Running.']);
    }
}

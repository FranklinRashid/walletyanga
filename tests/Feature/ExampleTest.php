<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic test example.
     */
    public function test_guests_are_redirected_to_login_from_the_dashboard(): void
    {
        $this->withoutVite();

        $response = $this->get('/');

        $response->assertRedirect('/dashboard');
        $this->get('/dashboard')->assertRedirect(route('login'));
    }
}

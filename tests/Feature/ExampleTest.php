<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * The root path is not a page of its own; it hands off to the dashboard,
     * which in turn requires a session.
     */
    public function test_the_root_path_redirects_into_the_dashboard(): void
    {
        $this->get('/')->assertRedirect('/dashboard');
    }

    public function test_the_login_screen_is_reachable(): void
    {
        $this->get('/login')->assertOk();
    }
}

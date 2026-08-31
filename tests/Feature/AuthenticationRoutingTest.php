<?php

namespace Tests\Feature;

use Tests\TestCase;

class AuthenticationRoutingTest extends TestCase
{
    public function test_root_redirects_to_dashboard(): void
    {
        $this->get('/')
            ->assertRedirect('/dashboard');
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/dashboard')
            ->assertRedirectToRoute('login');
    }
}

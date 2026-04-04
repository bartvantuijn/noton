<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthRedirectTest extends TestCase
{
    use RefreshDatabase;

    public function test_root_redirects_to_register_when_no_users_exist(): void
    {
        $this->get('/')
            ->assertRedirect(route('filament.admin.auth.register'));
    }

    public function test_register_redirects_to_login_when_users_exist(): void
    {
        User::factory()->create();

        $this->get(route('filament.admin.auth.register'))
            ->assertRedirect(route('filament.admin.auth.login'));
    }

    public function test_login_is_available_when_users_exist(): void
    {
        User::factory()->create();

        $this->get(route('filament.admin.auth.login'))
            ->assertOk();
    }
}

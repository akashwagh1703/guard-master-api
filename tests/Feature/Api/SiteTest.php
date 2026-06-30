<?php

namespace Tests\Feature\Api;

use App\Enums\UserRole;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SiteTest extends TestCase
{
    use RefreshDatabase;

    private function adminToken(): string
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
            'password' => Hash::make('password123'),
        ]);

        return $admin->createToken('test')->plainTextToken;
    }

    public function test_admin_can_list_sites(): void
    {
        Site::factory()->count(3)->create();
        $token = $this->adminToken();

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/sites');

        $response->assertOk()->assertJsonPath('success', true);
    }

    public function test_guard_cannot_access_sites(): void
    {
        $guard = User::factory()->create(['role' => UserRole::Guard]);
        $token = $guard->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/sites');

        $response->assertForbidden();
    }
}

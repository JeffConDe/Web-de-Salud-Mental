<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_administrators_can_create_and_change_staff_roles(): void
    {
        $administrator = User::factory()->create(['role' => UserRole::Administrator]);
        $adminToken = $administrator->createToken('admin')->plainTextToken;

        $response = $this->withToken($adminToken)->postJson('/api/admin/users', [
            'name' => 'Recepción',
            'email' => 'RECEPCION@example.test',
            'role' => 'receptionist',
            'password' => 'A-secure-password-123',
            'password_confirmation' => 'A-secure-password-123',
        ])->assertCreated()
            ->assertJsonPath('user.email', 'recepcion@example.test')
            ->assertJsonPath('user.role', 'receptionist')
            ->assertJsonMissingPath('user.password');

        $staffId = $response->json('user.id');
        $staff = User::findOrFail($staffId);
        $staffToken = $staff->createToken('staff')->plainTextToken;

        $this->withToken($adminToken)->patchJson("/api/admin/users/{$staffId}/role", [
            'role' => 'doctor',
        ])->assertOk()
            ->assertJsonPath('user.role', 'doctor');

        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $staffId,
            'name' => 'staff',
        ]);

        $this->withToken($adminToken)->deleteJson("/api/admin/users/{$staffId}")
            ->assertNoContent();
        $this->assertDatabaseMissing('users', ['id' => $staffId]);

        $this->withToken($adminToken)->getJson('/api/admin/users')
            ->assertOk()
            ->assertJsonFragment(['role' => 'administrator'])
            ->assertJsonMissing(['role' => 'doctor']);

        $this->app['auth']->forgetGuards();
        $this->withToken($staffToken)->getJson('/api/admin/users')->assertUnauthorized();
    }

    public function test_administrators_cannot_lock_out_the_last_admin_or_self_demote(): void
    {
        $administrator = User::factory()->create(['role' => UserRole::Administrator]);
        $token = $administrator->createToken('admin')->plainTextToken;

        $this->withToken($token)->patchJson("/api/admin/users/{$administrator->id}/role", [
            'role' => 'receptionist',
        ])->assertUnprocessable()
            ->assertJsonPath('message', 'No puedes quitar el rol del único administrador.');
        $this->withToken($token)->deleteJson("/api/admin/users/{$administrator->id}")
            ->assertUnprocessable()
            ->assertJsonPath('message', 'No puedes eliminar tu propia cuenta de administrador.');

        $secondAdministrator = User::factory()->create(['role' => UserRole::Administrator]);
        $this->withToken($token)->patchJson("/api/admin/users/{$administrator->id}/role", [
            'role' => 'doctor',
        ])->assertUnprocessable()
            ->assertJsonPath('message', 'No puedes cambiar tu propio rol de administrador.');

        $this->assertSame(UserRole::Administrator, $secondAdministrator->fresh()->role);

        $this->app['auth']->forgetGuards();
        $secondAdminToken = $secondAdministrator->createToken('second-admin')->plainTextToken;
        $this->withToken($secondAdminToken)->deleteJson("/api/admin/users/{$administrator->id}")
            ->assertNoContent();

        $this->app['auth']->forgetGuards();
        $this->withToken($secondAdminToken)->patchJson("/api/admin/users/{$secondAdministrator->id}/role", [
            'role' => 'doctor',
        ])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'No puedes quitar el rol del único administrador.');

        $this->app['auth']->forgetGuards();
        $this->withToken($secondAdminToken)->deleteJson("/api/admin/users/{$secondAdministrator->id}")
            ->assertUnprocessable()
            ->assertJsonPath('message', 'No puedes eliminar tu propia cuenta de administrador.');
    }

    public function test_staff_role_endpoints_are_forbidden_to_non_administrators(): void
    {
        $receptionist = User::factory()->create(['role' => UserRole::Receptionist]);
        $token = $receptionist->createToken('staff')->plainTextToken;

        $this->withToken($token)->getJson('/api/admin/users')->assertForbidden();
        $this->withToken($token)->postJson('/api/admin/users', [])->assertForbidden();
    }
}

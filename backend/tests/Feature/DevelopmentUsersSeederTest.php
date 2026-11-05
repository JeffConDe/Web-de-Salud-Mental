<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\DoctorProfile;
use App\Models\User;
use Database\Seeders\DevelopmentUsersSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Hash;
use LogicException;
use Tests\TestCase;

class DevelopmentUsersSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_refuses_to_create_demo_users_outside_local_environment(): void
    {
        config(['app.env' => 'production']);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Las cuentas de prueba solo se pueden crear en entorno local.');

        app(DevelopmentUsersSeeder::class)->run();
    }

    public function test_it_creates_idempotent_demo_users_for_each_role_in_local_environment(): void
    {
        config(['app.env' => 'local']);

        $seeder = app(DevelopmentUsersSeeder::class);
        $seeder->run();
        $seeder->run();

        $users = [
            'admin@saludmental.test' => UserRole::Administrator,
            'recepcion@saludmental.test' => UserRole::Receptionist,
            'medico@saludmental.test' => UserRole::Doctor,
            'paciente@saludmental.test' => UserRole::Patient,
        ];

        $this->assertDatabaseCount('users', count($users));

        foreach ($users as $email => $role) {
            $user = User::where('email', $email)->firstOrFail();
            $this->assertSame($role, $user->role);
            $this->assertTrue(Hash::check('SaludMental2026!', $user->password));
        }

        $doctor = User::where('email', 'medico@saludmental.test')->firstOrFail();
        $this->assertSame('Psicología general', DoctorProfile::where('user_id', $doctor->id)->firstOrFail()->specialty->name);
    }

    public function test_demo_accounts_can_log_in(): void
    {
        config(['app.env' => 'local']);
        app(DevelopmentUsersSeeder::class)->run();

        $this->withoutMiddleware(ThrottleRequests::class);
        $this->postJson('/api/login', [
            'email' => 'admin@saludmental.test',
            'password' => 'SaludMental2026!',
        ])->assertOk()
            ->assertJsonPath('user.role', 'administrator')
            ->assertJsonStructure(['token']);
    }
}

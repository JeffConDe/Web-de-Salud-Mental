<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\DoctorProfile;
use App\Models\Specialty;
use App\Models\User;
use Illuminate\Database\Seeder;
use LogicException;

class DevelopmentUsersSeeder extends Seeder
{
    private const PASSWORD = 'SaludMental2026!';

    public function run(): void
    {
        if (config('app.env') !== 'local') {
            throw new LogicException('Las cuentas de prueba solo se pueden crear en entorno local.');
        }

        $users = [
            ['name' => 'Admin Demo', 'email' => 'admin@saludmental.test', 'role' => UserRole::Administrator],
            ['name' => 'Recepción Demo', 'email' => 'recepcion@saludmental.test', 'role' => UserRole::Receptionist],
            ['name' => 'Médico Demo', 'email' => 'medico@saludmental.test', 'role' => UserRole::Doctor],
            ['name' => 'Paciente Demo', 'email' => 'paciente@saludmental.test', 'role' => UserRole::Patient],
        ];

        foreach ($users as $user) {
            User::updateOrCreate(
                ['email' => $user['email']],
                [
                    'name' => $user['name'],
                    'role' => $user['role'],
                    'password' => self::PASSWORD,
                ],
            );
        }

        $specialty = Specialty::updateOrCreate(
            ['name' => 'Psicología general'],
            ['description' => 'Especialidad de demostración para pruebas locales.', 'active' => true],
        );
        $doctor = User::where('email', 'medico@saludmental.test')->firstOrFail();

        DoctorProfile::updateOrCreate(
            ['user_id' => $doctor->id],
            [
                'specialty_id' => $specialty->id,
                'license_number' => 'DEMO-PSI-001',
                'biography' => 'Perfil de demostración; no corresponde a un profesional real.',
                'active' => true,
            ],
        );
    }
}

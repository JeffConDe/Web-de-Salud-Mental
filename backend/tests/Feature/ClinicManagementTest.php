<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\DoctorProfile;
use App\Models\DoctorSchedule;
use App\Models\Specialty;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClinicManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_can_create_specialty_doctor_and_weekly_schedule(): void
    {
        $administrator = User::factory()->create(['role' => UserRole::Administrator]);
        $token = $administrator->createToken('test')->plainTextToken;

        $specialtyResponse = $this->withToken($token)->postJson('/api/admin/specialties', [
            'name' => '  Psicología clínica ',
            'description' => 'Atención psicológica.',
        ])->assertCreated()
            ->assertJsonPath('specialty.name', 'Psicología clínica');

        $specialtyId = $specialtyResponse->json('specialty.id');
        $doctorResponse = $this->withToken($token)->postJson('/api/admin/doctors', [
            'name' => 'Dra. Ana Prueba',
            'email' => ' DOCTORA@example.test ',
            'password' => 'A-secure-password-123',
            'password_confirmation' => 'A-secure-password-123',
            'specialty_id' => $specialtyId,
            'license_number' => 'TP-123456',
            'biography' => '',
        ])->assertCreated()
            ->assertJsonPath('doctor.name', 'Dra. Ana Prueba')
            ->assertJsonPath('doctor.email', 'doctora@example.test')
            ->assertJsonPath('doctor.specialty.id', $specialtyId)
            ->assertJsonPath('doctor.biography', null);

        $doctorId = $doctorResponse->json('doctor.id');
        $this->withToken($token)->postJson("/api/doctors/{$doctorId}/schedules", [
            'day_of_week' => 1,
            'start_time' => '09:00',
            'end_time' => '12:00',
            'slot_duration_minutes' => 30,
        ])->assertCreated()
            ->assertJsonPath('schedule.start_time', '09:00')
            ->assertJsonPath('schedule.end_time', '12:00');

        $this->withToken($token)->getJson('/api/doctors')
            ->assertOk()
            ->assertJsonPath('data.0.schedules.0.day_of_week', 1);
        $this->assertDatabaseHas('users', [
            'email' => 'doctora@example.test',
            'role' => UserRole::Doctor->value,
        ]);
    }

    public function test_active_schedules_cannot_overlap_and_doctors_can_manage_only_their_own(): void
    {
        $administrator = User::factory()->create(['role' => UserRole::Administrator]);
        $administratorToken = $administrator->createToken('admin')->plainTextToken;
        $doctorUser = User::factory()->create(['role' => UserRole::Doctor]);
        $otherDoctorUser = User::factory()->create(['role' => UserRole::Doctor]);
        $specialty = Specialty::create(['name' => 'Psicología']);
        $doctor = DoctorProfile::create([
            'user_id' => $doctorUser->id,
            'specialty_id' => $specialty->id,
            'license_number' => 'LIC-1',
        ]);
        $otherDoctor = DoctorProfile::create([
            'user_id' => $otherDoctorUser->id,
            'specialty_id' => $specialty->id,
            'license_number' => 'LIC-2',
        ]);

        $this->withToken($administratorToken)->postJson("/api/doctors/{$doctor->id}/schedules", [
            'day_of_week' => 2,
            'start_time' => '09:00',
            'end_time' => '12:00',
            'slot_duration_minutes' => 30,
        ])->assertCreated();

        $this->withToken($administratorToken)->postJson("/api/doctors/{$doctor->id}/schedules", [
            'day_of_week' => 2,
            'start_time' => '11:00',
            'end_time' => '13:00',
            'slot_duration_minutes' => 30,
        ])->assertUnprocessable()
            ->assertJsonPath('message', 'El horario se cruza con otro bloque activo del mismo día.');

        $this->app['auth']->forgetGuards();
        $doctorToken = $doctorUser->createToken('doctor')->plainTextToken;
        $this->withToken($doctorToken)->postJson("/api/doctors/{$doctor->id}/schedules", [
            'day_of_week' => 3,
            'start_time' => '09:00',
            'end_time' => '12:00',
            'slot_duration_minutes' => 30,
        ])->assertCreated();

        $this->withToken($doctorToken)->postJson("/api/doctors/{$otherDoctor->id}/schedules", [
            'day_of_week' => 3,
            'start_time' => '13:00',
            'end_time' => '15:00',
            'slot_duration_minutes' => 30,
        ])->assertForbidden();

        $this->assertSame(2, DoctorSchedule::count());
    }

    public function test_reception_can_read_but_not_change_clinic_data_and_specialties_with_doctors_cannot_be_archived(): void
    {
        $specialty = Specialty::create(['name' => 'Psiquiatría']);
        $doctorUser = User::factory()->create(['role' => UserRole::Doctor]);
        $doctor = DoctorProfile::create([
            'user_id' => $doctorUser->id,
            'specialty_id' => $specialty->id,
            'license_number' => 'LIC-3',
        ]);
        $receptionist = User::factory()->create(['role' => UserRole::Receptionist]);
        $token = $receptionist->createToken('reception')->plainTextToken;

        $this->withToken($token)->getJson('/api/specialties')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Psiquiatría');
        $this->withToken($token)->getJson('/api/doctors')
            ->assertOk()
            ->assertJsonPath('data.0.id', $doctor->id);
        $this->withToken($token)->postJson('/api/admin/specialties', ['name' => 'Neurología'])
            ->assertForbidden();
        $this->withToken($token)->postJson("/api/doctors/{$doctor->id}/schedules", [])
            ->assertForbidden();

        $this->app['auth']->forgetGuards();
        $administrator = User::factory()->create(['role' => UserRole::Administrator]);
        $adminToken = $administrator->createToken('admin')->plainTextToken;
        $this->withToken($adminToken)->deleteJson("/api/admin/specialties/{$specialty->id}")
            ->assertUnprocessable();
        $this->assertDatabaseHas('specialties', ['id' => $specialty->id, 'active' => true]);
    }

    public function test_administrator_can_reactivate_an_archived_specialty(): void
    {
        $administrator = User::factory()->create(['role' => UserRole::Administrator]);
        $token = $administrator->createToken('admin')->plainTextToken;
        $specialty = Specialty::create(['name' => 'Neurología']);

        $this->withToken($token)->deleteJson("/api/admin/specialties/{$specialty->id}")
            ->assertNoContent();
        $this->withToken($token)->putJson("/api/admin/specialties/{$specialty->id}", [
            'name' => 'Neurología',
            'description' => '',
            'active' => true,
        ])->assertOk()
            ->assertJsonPath('specialty.active', true);
    }
}

<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PatientManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_receptionist_can_search_update_and_archive_patient_records(): void
    {
        $receptionist = User::factory()->create(['role' => UserRole::Receptionist]);
        $token = $receptionist->createToken('test')->plainTextToken;

        $this->withToken($token)->postJson('/api/patients', [
            'document' => '1.234.567-8',
            'name' => 'Camila Prueba',
            'phone' => '  300 123 4567 ',
            'email' => ' CAMILA@example.test ',
        ])->assertCreated()
            ->assertJsonPath('patient.document', '12345678')
            ->assertJsonPath('patient.email', 'camila@example.test');

        $patient = Patient::firstOrFail();
        $this->withToken($token)->getJson('/api/patients?search=12345678')
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.id', $patient->id);

        $this->withToken($token)->putJson("/api/patients/{$patient->id}", [
            'document' => '12345678',
            'name' => 'Camila Actualizada',
            'phone' => '',
            'email' => '',
        ])->assertOk()
            ->assertJsonPath('patient.name', 'Camila Actualizada')
            ->assertJsonPath('patient.phone', null)
            ->assertJsonPath('patient.email', null);

        $this->withToken($token)->deleteJson("/api/patients/{$patient->id}")
            ->assertNoContent();
        $this->assertSoftDeleted($patient);
        $this->withToken($token)->getJson('/api/patients')
            ->assertOk()
            ->assertJsonPath('total', 0);
    }

    public function test_patient_data_is_restricted_to_administrators_and_receptionists(): void
    {
        $patient = User::factory()->create(['role' => UserRole::Patient]);
        $doctor = User::factory()->create(['role' => UserRole::Doctor]);

        foreach ([$patient, $doctor] as $user) {
            $token = $user->createToken('test')->plainTextToken;
            $this->withToken($token)->getJson('/api/patients')->assertForbidden();
            $this->withToken($token)->postJson('/api/patients', [
                'document' => '987654321',
                'name' => 'No autorizado',
            ])->assertForbidden();
        }

        $this->app['auth']->forgetGuards();
        $administrator = User::factory()->create(['role' => UserRole::Administrator]);
        $adminToken = $administrator->createToken('test')->plainTextToken;
        $this->withToken($adminToken)->postJson('/api/patients', [
            'document' => '987654321',
            'name' => 'Paciente Autorizado',
        ])->assertCreated();
    }

    public function test_patient_records_validate_required_fields_and_unique_documents(): void
    {
        $receptionist = User::factory()->create(['role' => UserRole::Receptionist]);
        $token = $receptionist->createToken('test')->plainTextToken;
        Patient::create([
            'document' => '12345',
            'name' => 'Paciente existente',
        ]);

        $this->withToken($token)->postJson('/api/patients', [
            'document' => '12.345',
            'name' => 'Documento duplicado',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('document');

        $this->withToken($token)->postJson('/api/patients', [
            'document' => '',
            'name' => '',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['document', 'name']);
    }
}

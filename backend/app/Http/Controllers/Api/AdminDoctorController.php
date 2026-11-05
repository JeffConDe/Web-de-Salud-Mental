<?php

namespace App\Http\Controllers\Api;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\DoctorProfile;
use App\Models\Specialty;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class AdminDoctorController extends Controller
{
    public function store(Request $request)
    {
        $request->merge($this->normalizedInput($request));
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)->max(72)],
            'specialty_id' => ['required', 'integer', 'exists:specialties,id'],
            'license_number' => ['required', 'string', 'max:50', 'unique:doctor_profiles,license_number'],
            'biography' => ['nullable', 'string', 'max:2000'],
        ]);

        $doctor = DB::transaction(function () use ($validated) {
            $specialty = Specialty::query()->lockForUpdate()->findOrFail($validated['specialty_id']);
            if (! $specialty->active) {
                abort(422, 'Selecciona una especialidad activa.');
            }

            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => $validated['password'],
                'role' => UserRole::Doctor,
            ]);

            return DoctorProfile::create([
                'user_id' => $user->id,
                'specialty_id' => $specialty->id,
                'license_number' => $validated['license_number'],
                'biography' => $validated['biography'],
            ]);
        });

        return response()->json([
            'doctor' => $this->doctorData($doctor->load(['user:id,name,email', 'specialty:id,name', 'schedules'])),
        ], 201);
    }

    public function update(Request $request, DoctorProfile $doctor)
    {
        $request->merge($this->normalizedInput($request));
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($doctor->user_id)],
            'specialty_id' => ['required', 'integer', 'exists:specialties,id'],
            'license_number' => ['required', 'string', 'max:50', Rule::unique('doctor_profiles', 'license_number')->ignore($doctor->id)],
            'biography' => ['nullable', 'string', 'max:2000'],
            'active' => ['required', 'boolean'],
        ]);

        DB::transaction(function () use ($validated, $doctor) {
            $specialty = Specialty::query()->lockForUpdate()->findOrFail($validated['specialty_id']);
            if (! $specialty->active && $specialty->id !== $doctor->specialty_id) {
                abort(422, 'Selecciona una especialidad activa.');
            }

            $doctor->user()->update([
                'name' => $validated['name'],
                'email' => $validated['email'],
            ]);
            $doctor->update([
                'specialty_id' => $specialty->id,
                'license_number' => $validated['license_number'],
                'biography' => $validated['biography'],
                'active' => $validated['active'],
            ]);
        });

        $doctor->refresh()->load(['user:id,name,email', 'specialty:id,name', 'schedules']);

        return response()->json(['doctor' => $this->doctorData($doctor)]);
    }

    private function normalizedInput(Request $request): array
    {
        $description = trim((string) $request->input('biography', ''));

        return [
            'name' => trim((string) $request->input('name', '')),
            'email' => mb_strtolower(trim((string) $request->input('email', ''))),
            'license_number' => trim((string) $request->input('license_number', '')),
            'biography' => $description === '' ? null : $description,
        ];
    }

    private function doctorData(DoctorProfile $doctor): array
    {
        return [
            'id' => $doctor->id,
            'user_id' => $doctor->user_id,
            'name' => $doctor->user->name,
            'email' => $doctor->user->email,
            'specialty' => ['id' => $doctor->specialty->id, 'name' => $doctor->specialty->name],
            'license_number' => $doctor->license_number,
            'biography' => $doctor->biography,
            'active' => $doctor->active,
            'schedules' => $doctor->schedules->map(fn ($schedule) => [
                'id' => $schedule->id,
                'day_of_week' => $schedule->day_of_week,
                'start_time' => substr((string) $schedule->start_time, 0, 5),
                'end_time' => substr((string) $schedule->end_time, 0, 5),
                'slot_duration_minutes' => $schedule->slot_duration_minutes,
                'active' => $schedule->active,
            ])->values(),
        ];
    }
}

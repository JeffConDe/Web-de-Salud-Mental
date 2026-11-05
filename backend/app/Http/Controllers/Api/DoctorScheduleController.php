<?php

namespace App\Http\Controllers\Api;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\DoctorProfile;
use App\Models\DoctorSchedule;
use Illuminate\Http\Request;

class DoctorScheduleController extends Controller
{
    public function index(DoctorProfile $doctor)
    {
        return response()->json([
            'data' => $doctor->schedules()
                ->orderBy('day_of_week')
                ->orderBy('start_time')
                ->get()
                ->map(fn (DoctorSchedule $schedule) => $this->scheduleData($schedule)),
        ]);
    }

    public function store(Request $request, DoctorProfile $doctor)
    {
        $this->authorizeManage($request, $doctor);
        $validated = $this->validatedSchedule($request);
        $this->ensureNoOverlap($doctor, $validated);

        $schedule = $doctor->schedules()->create($validated);

        return response()->json(['schedule' => $this->scheduleData($schedule)], 201);
    }

    public function update(Request $request, DoctorProfile $doctor, DoctorSchedule $schedule)
    {
        $this->authorizeManage($request, $doctor);
        abort_unless($schedule->doctor_profile_id === $doctor->id, 404);
        $validated = $this->validatedSchedule($request);
        $this->ensureNoOverlap($doctor, $validated, $schedule->id);

        $schedule->update($validated);

        return response()->json(['schedule' => $this->scheduleData($schedule->fresh())]);
    }

    public function destroy(Request $request, DoctorProfile $doctor, DoctorSchedule $schedule)
    {
        $this->authorizeManage($request, $doctor);
        abort_unless($schedule->doctor_profile_id === $doctor->id, 404);
        $schedule->delete();

        return response()->noContent();
    }

    private function validatedSchedule(Request $request): array
    {
        $validated = $request->validate([
            'day_of_week' => ['required', 'integer', 'between:0,6'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'slot_duration_minutes' => ['required', 'integer', 'between:10,240'],
            'active' => ['sometimes', 'boolean'],
        ]);
        $validated['active'] = $validated['active'] ?? true;

        return $validated;
    }

    private function ensureNoOverlap(DoctorProfile $doctor, array $validated, ?int $exceptId = null): void
    {
        if (! $validated['active']) {
            return;
        }

        $overlap = $doctor->schedules()
            ->where('active', true)
            ->where('day_of_week', $validated['day_of_week'])
            ->when($exceptId !== null, fn ($query) => $query->where('id', '<>', $exceptId))
            ->where('start_time', '<', $validated['end_time'])
            ->where('end_time', '>', $validated['start_time'])
            ->exists();

        if ($overlap) {
            abort(422, 'El horario se cruza con otro bloque activo del mismo día.');
        }
    }

    private function authorizeManage(Request $request, DoctorProfile $doctor): void
    {
        $user = $request->user();
        abort_unless(
            $user->role === UserRole::Administrator || ($user->role === UserRole::Doctor && $doctor->user_id === $user->id),
            403,
        );
    }

    private function scheduleData(DoctorSchedule $schedule): array
    {
        return [
            'id' => $schedule->id,
            'day_of_week' => $schedule->day_of_week,
            'start_time' => substr((string) $schedule->start_time, 0, 5),
            'end_time' => substr((string) $schedule->end_time, 0, 5),
            'slot_duration_minutes' => $schedule->slot_duration_minutes,
            'active' => $schedule->active,
        ];
    }
}

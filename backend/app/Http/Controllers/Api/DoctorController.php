<?php

namespace App\Http\Controllers\Api;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\DoctorProfile;
use Illuminate\Http\Request;

class DoctorController extends Controller
{
    public function index(Request $request)
    {
        $search = mb_strtolower(trim((string) $request->query('search', '')));
        $isAdministrator = $request->user()->role === UserRole::Administrator;

        $doctors = DoctorProfile::query()
            ->with(['user:id,name,email', 'specialty:id,name', 'schedules' => fn ($query) => $query->orderBy('day_of_week')->orderBy('start_time')])
            ->when(! $isAdministrator, fn ($query) => $query->where('active', true))
            ->when($search !== '', function ($query) use ($search) {
                $like = '%'.$search.'%';
                $query->where(function ($query) use ($like) {
                    $query->whereHas('user', fn ($userQuery) => $userQuery->whereRaw('LOWER(name) LIKE ?', [$like]))
                        ->orWhereHas('specialty', fn ($specialtyQuery) => $specialtyQuery->whereRaw('LOWER(name) LIKE ?', [$like]));
                });
            })
            ->orderBy('id')
            ->limit(100)
            ->get();

        return response()->json([
            'data' => $doctors->map(fn (DoctorProfile $doctor) => $this->doctorData($doctor)),
        ]);
    }

    public function show(DoctorProfile $doctor)
    {
        $doctor->load(['user:id,name,email', 'specialty:id,name', 'schedules' => fn ($query) => $query->orderBy('day_of_week')->orderBy('start_time')]);

        return response()->json(['doctor' => $this->doctorData($doctor)]);
    }

    private function doctorData(DoctorProfile $doctor): array
    {
        return [
            'id' => $doctor->id,
            'user_id' => $doctor->user_id,
            'name' => $doctor->user->name,
            'email' => $doctor->user->email,
            'specialty' => [
                'id' => $doctor->specialty->id,
                'name' => $doctor->specialty->name,
            ],
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

<?php

namespace App\Http\Controllers\Api;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Specialty;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SpecialtyController extends Controller
{
    public function index()
    {
        $specialties = Specialty::query()
            ->when(request()->user()->role !== UserRole::Administrator, fn ($query) => $query->where('active', true))
            ->orderBy('name')
            ->get(['id', 'name', 'description', 'active']);

        return response()->json(['data' => $specialties]);
    }

    public function store(Request $request)
    {
        $request->merge($this->normalizedInput($request));
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:specialties,name'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        $specialty = Specialty::create($validated);

        return response()->json(['specialty' => $specialty], 201);
    }

    public function update(Request $request, Specialty $specialty)
    {
        $request->merge($this->normalizedInput($request));
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('specialties', 'name')->ignore($specialty->id)],
            'description' => ['nullable', 'string', 'max:2000'],
            'active' => ['sometimes', 'boolean'],
        ]);

        if (($validated['active'] ?? true) === false && $specialty->doctorProfiles()->exists()) {
            return response()->json([
                'message' => 'No puedes archivar una especialidad que tiene médicos asociados.',
            ], 422);
        }

        $specialty->update($validated);

        return response()->json(['specialty' => $specialty->fresh()]);
    }

    public function destroy(Specialty $specialty)
    {
        if ($specialty->doctorProfiles()->exists()) {
            return response()->json([
                'message' => 'No puedes archivar una especialidad que tiene médicos asociados.',
            ], 422);
        }

        $specialty->update(['active' => false]);

        return response()->noContent();
    }

    private function normalizedInput(Request $request): array
    {
        $description = trim((string) $request->input('description', ''));

        return [
            'name' => trim((string) $request->input('name', '')),
            'description' => $description === '' ? null : $description,
        ];
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PatientController extends Controller
{
    public function index(Request $request)
    {
        $search = mb_strtolower(trim((string) $request->query('search', '')));

        $patients = Patient::query()
            ->when($search !== '', function ($query) use ($search) {
                $like = '%'.$search.'%';
                $query->where(function ($query) use ($like) {
                    $query->whereRaw('LOWER(name) LIKE ?', [$like])
                        ->orWhereRaw('LOWER(document) LIKE ?', [$like])
                        ->orWhereRaw('LOWER(COALESCE(email, \'\')) LIKE ?', [$like])
                        ->orWhereRaw('LOWER(COALESCE(phone, \'\')) LIKE ?', [$like]);
                });
            })
            ->orderBy('name')
            ->paginate(min(max($request->integer('per_page', 15), 1), 50));

        return response()->json($patients);
    }

    public function store(Request $request)
    {
        $request->merge($this->normalizedInput($request));

        $validated = $request->validate([
            'document' => ['required', 'string', 'max:30', 'unique:patients,document'],
            'name' => ['required', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
        ]);

        $patient = Patient::create($validated);

        return response()->json([
            'patient' => $this->patientData($patient),
        ], 201);
    }

    public function show(Patient $patient)
    {
        return response()->json([
            'patient' => $this->patientData($patient),
        ]);
    }

    public function update(Request $request, Patient $patient)
    {
        $request->merge($this->normalizedInput($request));

        $validated = $request->validate([
            'document' => [
                'required',
                'string',
                'max:30',
                Rule::unique('patients', 'document')->ignore($patient->id),
            ],
            'name' => ['required', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
        ]);

        $patient->update($validated);

        return response()->json([
            'patient' => $this->patientData($patient),
        ]);
    }

    public function destroy(Patient $patient)
    {
        $patient->delete();

        return response()->noContent();
    }

    private function normalizedInput(Request $request): array
    {
        $document = trim((string) $request->input('document', ''));

        return [
            'document' => preg_replace('/[\s.-]+/u', '', $document),
            'name' => trim((string) $request->input('name', '')),
            'phone' => $this->nullableTrimmed($request->input('phone')),
            'email' => $this->nullableTrimmed(mb_strtolower((string) $request->input('email', ''))),
        ];
    }

    private function nullableTrimmed(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));

        return $value === '' ? null : $value;
    }

    private function patientData(Patient $patient): array
    {
        return [
            'id' => $patient->id,
            'document' => $patient->document,
            'name' => $patient->name,
            'phone' => $patient->phone,
            'email' => $patient->email,
            'created_at' => $patient->created_at->toISOString(),
            'updated_at' => $patient->updated_at->toISOString(),
        ];
    }
}

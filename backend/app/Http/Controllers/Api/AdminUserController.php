<?php

namespace App\Http\Controllers\Api;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class AdminUserController extends Controller
{
    public function index()
    {
        $users = User::query()
            ->select(['id', 'name', 'email', 'role', 'created_at'])
            ->orderBy('name')
            ->paginate(20);

        $users->getCollection()->transform(fn (User $user) => $this->userData($user));

        return response()->json($users);
    }

    public function store(Request $request)
    {
        $request->merge([
            'email' => mb_strtolower(trim((string) $request->input('email'))),
        ]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', Rule::in([
                UserRole::Administrator->value,
                UserRole::Receptionist->value,
                UserRole::Doctor->value,
            ])],
            'password' => ['required', 'confirmed', Password::min(8)->max(72)],
        ]);

        $user = User::create($validated);

        return response()->json([
            'user' => $this->userData($user),
        ], 201);
    }

    public function updateRole(Request $request, User $user)
    {
        $validated = $request->validate([
            'role' => ['required', Rule::in([
                UserRole::Administrator->value,
                UserRole::Receptionist->value,
                UserRole::Doctor->value,
                UserRole::Patient->value,
            ])],
        ]);

        $nextRole = UserRole::from($validated['role']);

        $updatedUser = DB::transaction(function () use ($request, $user, $nextRole) {
            $administrators = User::where('role', UserRole::Administrator->value)
                ->lockForUpdate()
                ->get();

            $user->refresh();
            if ($user->role === UserRole::Administrator && $nextRole !== UserRole::Administrator) {
                if ($administrators->count() <= 1) {
                    abort(422, 'No puedes quitar el rol del único administrador.');
                }
            }

            if ($request->user()->is($user) && $nextRole !== UserRole::Administrator) {
                abort(422, 'No puedes cambiar tu propio rol de administrador.');
            }

            $user->role = $nextRole;
            $user->save();
            $user->tokens()->delete();

            return $user;
        });

        return response()->json(['user' => $this->userData($updatedUser)]);
    }

    public function destroy(Request $request, User $user)
    {
        if ($request->user()->is($user)) {
            return response()->json([
                'message' => 'No puedes eliminar tu propia cuenta de administrador.',
            ], 422);
        }

        $deleted = DB::transaction(function () use ($user) {
            $administrators = User::where('role', UserRole::Administrator->value)
                ->lockForUpdate()
                ->get();

            $user->refresh();
            if ($user->role === UserRole::Administrator && $administrators->count() <= 1) {
                return false;
            }

            $user->tokens()->delete();
            $user->delete();

            return true;
        });

        if (! $deleted) {
            return response()->json([
                'message' => 'No puedes eliminar el único administrador.',
            ], 422);
        }

        return response()->noContent();
    }

    private function userData(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role->value,
        ];
    }
}

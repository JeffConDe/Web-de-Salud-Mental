<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Console\Command;

class AssignUserRole extends Command
{
    protected $signature = 'app:assign-user-role {email} {role}';

    protected $description = 'Assign a role to an existing user account';

    public function handle(): int
    {
        $role = UserRole::tryFrom((string) $this->argument('role'));
        if (! $role) {
            $this->error('Rol inválido. Opciones: '.implode(', ', UserRole::values()));

            return self::FAILURE;
        }

        $user = User::where('email', mb_strtolower(trim((string) $this->argument('email'))))->first();
        if (! $user) {
            $this->error('No existe una cuenta con ese correo. Regístrala primero.');

            return self::FAILURE;
        }

        $user->role = $role;
        $user->save();
        $user->tokens()->delete();

        $this->info("Rol actualizado a {$role->value} para {$user->email}. Inicia sesión de nuevo.");

        return self::SUCCESS;
    }
}

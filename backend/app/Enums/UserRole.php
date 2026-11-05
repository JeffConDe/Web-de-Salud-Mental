<?php

namespace App\Enums;

enum UserRole: string
{
    case Administrator = 'administrator';
    case Receptionist = 'receptionist';
    case Doctor = 'doctor';
    case Patient = 'patient';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $role) => $role->value, self::cases());
    }
}

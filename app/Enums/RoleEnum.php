<?php

namespace App\Enums;

enum RoleEnum : string
{
    case SUPERADMIN = 'superadmin';
    case ADMIN = 'admin';
    case MANAGER = 'manager';

    public function id(): int
    {
        return match ($this) {
            static::SUPERADMIN => 1,
            static::ADMIN => 2,
            static::MANAGER => 3,
        };
    }

    // extra helper to allow for greater customization of displayed values, without disclosing the name/value data directly
    public function label(): string
    {
        return match ($this) {
            static::SUPERADMIN => 'Super Admin',
            static::ADMIN => 'Admin',
            static::MANAGER => 'Manager',
        };
    }
}

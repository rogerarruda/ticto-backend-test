<?php

namespace App\Enums\User;

enum Role: string
{
    case Admin    = 'admin';
    case Employee = 'employee';

    public function label(): string
    {
        return match ($this) {
            self::Admin    => 'Supervisor',
            self::Employee => 'Funcionário',
        };
    }

    public function isAdmin(): bool
    {
        return $this === self::Admin;
    }

    public function isEmployee(): bool
    {
        return $this === self::Employee;
    }
}

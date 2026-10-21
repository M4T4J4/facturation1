<?php

namespace App;

enum UserRole: string
{
    case Administrator = 'administrator';
    case FieldAgent = 'field_agent';
    case Customer = 'customer';

    public function label(): string
    {
        return match ($this) {
            self::Administrator => 'Administrateur',
            self::FieldAgent => 'Agent de terrain',
            self::Customer => 'Client / Abonné',
        };
    }
}

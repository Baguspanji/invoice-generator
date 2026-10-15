<?php

namespace App\Enums;

enum UserRole: string
{
    case STAFF = 'staff';
    case CUSTOMER = 'customer';

    public function label(): string
    {
        return match ($this) {
            self::STAFF => 'Staff',
            self::CUSTOMER => 'Customer',
        };
    }
}

<?php

namespace App\Enums;

enum InvoiceStatus: string
{
    case UNPAID = 'UNPAID';
    case PAID = 'PAID';
    case CANCELLED = 'CANCELLED';

    public function label(): string
    {
        return match ($this) {
            self::UNPAID => 'Unpaid',
            self::PAID => 'Paid',
            self::CANCELLED => 'Cancelled',
        };
    }
}

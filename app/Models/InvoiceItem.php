<?php

namespace App\Models;

use Database\Factories\InvoiceItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'invoice_id',
    'item_name',
    'category',
    'quantity',
    'unit_price',
    'discount_amount',
    'tax_amount',
    'total_price',
])]
class InvoiceItem extends Model
{
    /** @use HasFactory<InvoiceItemFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total_price' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<Invoice, $this>
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function calculateTotalPrice(): float
    {
        return ((float) $this->quantity * (float) $this->unit_price)
            - (float) $this->discount_amount
            + (float) $this->tax_amount;
    }

    protected static function booted(): void
    {
        static::saving(function (InvoiceItem $item): void {
            $item->total_price = $item->calculateTotalPrice();
        });
    }
}

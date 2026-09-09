<?php

namespace App\Models;

use App\Enums\InvoiceStatus;
use Database\Factories\InvoiceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'customer_id',
    'invoice_number',
    'invoice_date',
    'due_date',
    'subtotal',
    'tax_amount',
    'discount_amount',
    'total_amount',
    'status',
    'paid_at',
    'pdf_r2_path',
])]
class Invoice extends Model
{
    /** @use HasFactory<InvoiceFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'invoice_date' => 'date',
            'due_date' => 'date',
            'paid_at' => 'datetime',
            'subtotal' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'status' => InvoiceStatus::class,
        ];
    }

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * @return HasMany<InvoiceItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    /**
     * @param  Builder<Invoice>  $query
     * @return Builder<Invoice>
     */
    public function scopePaid(Builder $query): Builder
    {
        return $query->where('status', InvoiceStatus::PAID);
    }

    /**
     * @param  Builder<Invoice>  $query
     * @return Builder<Invoice>
     */
    public function scopeUnpaid(Builder $query): Builder
    {
        return $query->where('status', InvoiceStatus::UNPAID);
    }

    /**
     * @param  Builder<Invoice>  $query
     * @return Builder<Invoice>
     */
    public function scopeCancelled(Builder $query): Builder
    {
        return $query->where('status', InvoiceStatus::CANCELLED);
    }

    public function isPaid(): bool
    {
        return $this->status === InvoiceStatus::PAID;
    }

    public function markAsPaid(): bool
    {
        return $this->update([
            'status' => InvoiceStatus::PAID,
            'paid_at' => now(),
        ]);
    }

    public function markAsCancelled(): bool
    {
        return $this->update([
            'status' => InvoiceStatus::CANCELLED,
        ]);
    }

    public function recalculateTotals(): bool
    {
        $items = $this->items()->get();

        $subtotal = (float) $items->sum(fn (InvoiceItem $item): float => (float) $item->quantity * (float) $item->unit_price);
        $discount = (float) $items->sum('discount_amount');
        $tax = (float) $items->sum('tax_amount');

        return $this->update([
            'subtotal' => $subtotal,
            'discount_amount' => $discount,
            'tax_amount' => $tax,
            'total_amount' => $subtotal - $discount + $tax,
        ]);
    }
}

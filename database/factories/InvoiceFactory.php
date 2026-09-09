<?php

namespace Database\Factories;

use App\Enums\InvoiceStatus;
use App\Models\Customer;
use App\Models\Invoice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
{
    protected static int $sequence = 0;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $invoiceDate = fake()->dateTimeBetween('-11 months', 'now');
        $dueDate = (clone $invoiceDate)->modify('+14 days');

        return [
            'customer_id' => Customer::factory(),
            'invoice_number' => $this->generateInvoiceNumber($invoiceDate),
            'invoice_date' => $invoiceDate->format('Y-m-d'),
            'due_date' => $dueDate->format('Y-m-d'),
            'subtotal' => 0,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total_amount' => 0,
            'status' => InvoiceStatus::UNPAID,
            'paid_at' => null,
            'pdf_r2_path' => null,
        ];
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => InvoiceStatus::PAID,
            'paid_at' => fake()->dateTimeBetween($attributes['invoice_date'], 'now'),
        ]);
    }

    public function unpaid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => InvoiceStatus::UNPAID,
            'paid_at' => null,
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => InvoiceStatus::CANCELLED,
            'paid_at' => null,
        ]);
    }

    private function generateInvoiceNumber(\DateTime $date): string
    {
        static::$sequence++;

        return sprintf(
            'INV/%s/%02d/%04d',
            $date->format('Y'),
            (int) $date->format('m'),
            static::$sequence
        );
    }
}

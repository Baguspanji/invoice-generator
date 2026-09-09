<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InvoiceItem>
 */
class InvoiceItemFactory extends Factory
{
    /**
     * @var array<int, string>
     */
    private static array $categories = ['Produk', 'Jasa', 'Lisensi', 'Konsultasi', 'Maintenance'];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantity = fake()->numberBetween(1, 10);
        $unitPrice = fake()->randomFloat(2, 50000, 5000000);
        $discount = fake()->randomFloat(2, 0, $quantity * $unitPrice * 0.1);
        $taxable = ($quantity * $unitPrice) - $discount;
        $tax = round($taxable * 0.11, 2);

        return [
            'invoice_id' => Invoice::factory(),
            'item_name' => fake()->words(3, true),
            'category' => fake()->randomElement(self::$categories),
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'discount_amount' => $discount,
            'tax_amount' => $tax,
            'total_price' => ($quantity * $unitPrice) - $discount + $tax,
        ];
    }
}

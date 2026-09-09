<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Seeder;

class InvoiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $customers = Customer::factory(10)->create();

        foreach ($customers as $customer) {
            // Invoice PAID: untuk KPI total pendapatan & grafik bulanan.
            Invoice::factory(2)
                ->paid()
                ->for($customer)
                ->create()
                ->each(function (Invoice $invoice): void {
                    $items = InvoiceItem::factory(rand(1, 4))->for($invoice)->create();
                    $this->syncInvoiceTotals($invoice, $items);
                });

            // Invoice UNPAID: untuk KPI piutang.
            Invoice::factory(1)
                ->unpaid()
                ->for($customer)
                ->create()
                ->each(function (Invoice $invoice): void {
                    $items = InvoiceItem::factory(rand(1, 3))->for($invoice)->create();
                    $this->syncInvoiceTotals($invoice, $items);
                });
        }

        // Beberapa invoice CANCELLED agar semua status terwakili.
        Invoice::factory(3)
            ->cancelled()
            ->create()
            ->each(function (Invoice $invoice): void {
                $items = InvoiceItem::factory(2)->for($invoice)->create();
                $this->syncInvoiceTotals($invoice, $items);
            });
    }

    /**
     * @param  Collection<int, InvoiceItem>  $items
     */
    private function syncInvoiceTotals(Invoice $invoice, $items): void
    {
        $subtotal = (float) $items->sum(fn (InvoiceItem $item): float => (float) $item->quantity * (float) $item->unit_price);
        $discount = (float) $items->sum('discount_amount');
        $tax = (float) $items->sum('tax_amount');

        $invoice->update([
            'subtotal' => $subtotal,
            'discount_amount' => $discount,
            'tax_amount' => $tax,
            'total_amount' => $subtotal - $discount + $tax,
        ]);
    }
}

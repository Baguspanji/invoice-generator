<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ProjectInvoiceService
{
    /**
     * @param  array<int, int>  $taskIds
     */
    public function createFromProject(Project $project, array $taskIds, ?string $dueDate = null): Invoice
    {
        return DB::transaction(function () use ($project, $taskIds, $dueDate) {
            $tasks = Task::where('project_id', $project->id)
                ->whereIn('id', $taskIds)
                ->whereNull('invoice_id')
                ->where('billable', true)
                ->with('timeLogs')
                ->get();

            if ($tasks->isEmpty()) {
                throw new InvalidArgumentException('Tidak ada task billable yang valid untuk dibuatkan invoice.');
            }

            $now = Carbon::now();
            $due = $dueDate ? Carbon::parse($dueDate) : $now->copy()->addDays(14);

            $prefix = sprintf('INV/%s/%02d/', $now->format('Y'), (int) $now->format('m'));
            $last = Invoice::where('invoice_number', 'like', $prefix.'%')
                ->orderByDesc('invoice_number')
                ->value('invoice_number');
            $sequence = $last ? (int) substr($last, -4) + 1 : 1;
            $invoiceNumber = $prefix.sprintf('%04d', $sequence);

            $invoice = Invoice::create([
                'customer_id' => $project->customer_id,
                'invoice_number' => $invoiceNumber,
                'invoice_date' => $now->toDateString(),
                'due_date' => $due->toDateString(),
                'status' => InvoiceStatus::UNPAID,
                'subtotal' => 0,
                'discount_amount' => 0,
                'tax_amount' => 0,
                'total_amount' => 0,
            ]);

            $subtotal = 0;

            foreach ($tasks as $task) {
                if ($project->pricing_type === 'fixed' || ($task->fixed_price !== null && (float) $task->fixed_price > 0 && (float) $project->rate == 0)) {
                    $qty = 1;
                    $unitPrice = (float) ($task->fixed_price ?? $project->rate);
                    $total = $unitPrice;
                } else {
                    $minutes = $task->totalLoggedMinutes();
                    $qty = max(1, (int) ceil($minutes / 60));
                    $unitPrice = (float) $project->rate;
                    $total = $qty * $unitPrice;
                }

                $invoice->items()->create([
                    'item_name' => $task->title,
                    'category' => 'Jasa Proyek',
                    'quantity' => $qty,
                    'unit_price' => $unitPrice,
                    'discount_amount' => 0,
                    'tax_amount' => 0,
                    'total_price' => $total,
                ]);

                $task->update(['invoice_id' => $invoice->id]);
                $subtotal += $total;
            }

            $invoice->update([
                'subtotal' => $subtotal,
                'discount_amount' => 0,
                'tax_amount' => 0,
                'total_amount' => $subtotal,
            ]);

            return $invoice;
        });
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\ProjectInvoiceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProjectInvoiceController extends Controller
{
    public function store(Request $request, Project $project, ProjectInvoiceService $service): RedirectResponse
    {
        $validated = $request->validate([
            'task_ids' => ['required', 'array', 'min:1'],
            'task_ids.*' => ['exists:tasks,id'],
            'due_date' => ['nullable', 'date'],
        ]);

        $invoice = $service->createFromProject(
            $project,
            $validated['task_ids'],
            $validated['due_date'] ?? null
        );

        return redirect()
            ->route('invoices.show', $invoice)
            ->with('success', "Invoice {$invoice->invoice_number} berhasil dibuat dari project {$project->name}.");
    }
}

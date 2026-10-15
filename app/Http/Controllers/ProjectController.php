<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Label;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProjectController extends Controller
{
    public function index(Request $request): View
    {
        $projects = Project::with(['customer', 'users'])
            ->withCount([
                'tasks as all_tasks_count',
                'tasks as completed_tasks_count' => fn ($query) => $query->whereNotNull('completed_at'),
            ])
            ->when($request->query('search'), fn ($query, $search) => $query->where('name', 'like', "%{$search}%"))
            ->when($request->query('customer_id'), fn ($query, $customerId) => $query->where('customer_id', $customerId))
            ->when($request->boolean('archived'), fn ($query) => $query->archived(), fn ($query) => $query->active())
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('projects.index', [
            'projects' => $projects,
            'customers' => Customer::orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('projects.create', [
            'customers' => Customer::orderBy('name')->get(),
            'users' => User::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'rate' => ['required', 'numeric', 'min:0'],
            'pricing_type' => ['required', 'in:fixed,hourly'],
            'user_ids' => ['nullable', 'array'],
            'user_ids.*' => ['exists:users,id'],
        ]);

        $project = Project::create([
            'customer_id' => $validated['customer_id'],
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'rate' => $validated['rate'],
            'pricing_type' => $validated['pricing_type'],
        ]);

        if (! empty($validated['user_ids'])) {
            $project->users()->attach($validated['user_ids']);
        }

        $defaultGroups = [
            ['name' => 'To Do', 'color' => '#64748b', 'order_column' => 0],
            ['name' => 'In Progress', 'color' => '#3b82f6', 'order_column' => 1],
            ['name' => 'QA', 'color' => '#f59e0b', 'order_column' => 2],
            ['name' => 'Done', 'color' => '#10b981', 'order_column' => 3],
        ];

        foreach ($defaultGroups as $group) {
            $project->taskGroups()->create($group);
        }

        return redirect()
            ->route('projects.show', $project)
            ->with('success', "Project {$project->name} berhasil dibuat.");
    }

    public function show(Request $request, Project $project): View
    {
        $hasFilters = $request->filled('search')
            || $request->filled('priority')
            || $request->filled('user_id')
            || $request->filled('label_id')
            || $request->filled('billing');

        $project->load([
            'customer',
            'users',
            'taskGroups.tasks' => function ($taskQuery) use ($request, $hasFilters) {
                if ($hasFilters) {
                    if ($request->filled('search')) {
                        $search = $request->query('search');
                        $taskQuery->where(function ($q) use ($search) {
                            $q->where('title', 'like', "%{$search}%")
                                ->orWhere('description', 'like', "%{$search}%");
                        });
                    }

                    if ($request->filled('priority')) {
                        $taskQuery->where('priority', $request->query('priority'));
                    }

                    if ($request->filled('user_id')) {
                        if ($request->query('user_id') === 'unassigned') {
                            $taskQuery->whereNull('assigned_to_user_id');
                        } else {
                            $taskQuery->where('assigned_to_user_id', $request->query('user_id'));
                        }
                    }

                    if ($request->filled('label_id')) {
                        $taskQuery->whereHas('labels', fn ($q) => $q->where('labels.id', $request->query('label_id')));
                    }

                    if ($request->filled('billing')) {
                        match ($request->query('billing')) {
                            'billable' => $taskQuery->where('billable', true)->whereNull('invoice_id'),
                            'non_billable' => $taskQuery->where('billable', false),
                            'invoiced' => $taskQuery->whereNotNull('invoice_id'),
                            default => null,
                        };
                    }
                }

                $taskQuery->with([
                    'assignedUser',
                    'timeLogs',
                    'invoice',
                    'labels',
                    'attachments',
                ]);
            },
        ]);

        $unbilledTasks = $project->tasks()
            ->whereNull('invoice_id')
            ->where('billable', true)
            ->get();

        $viewMode = $request->query('view', 'list');

        $labels = Label::orderBy('name')->get();
        if ($labels->isEmpty()) {
            $defaultLabels = [
                ['name' => 'Bug', 'color' => '#ef4444'],
                ['name' => 'Feature', 'color' => '#3b82f6'],
                ['name' => 'UI/UX', 'color' => '#8b5cf6'],
                ['name' => 'Refactor', 'color' => '#10b981'],
            ];
            foreach ($defaultLabels as $dl) {
                Label::create($dl);
            }
            $labels = Label::orderBy('name')->get();
        }

        return view('projects.show', [
            'project' => $project,
            'unbilledTasks' => $unbilledTasks,
            'users' => User::orderBy('name')->get(),
            'labels' => $labels,
            'viewMode' => in_array($viewMode, ['board', 'list'], true) ? $viewMode : 'list',
            'filters' => $request->only(['search', 'priority', 'user_id', 'label_id', 'billing']),
            'hasFilters' => $hasFilters,
        ]);
    }

    public function edit(Project $project): View
    {
        return view('projects.edit', [
            'project' => $project->load('users'),
            'customers' => Customer::orderBy('name')->get(),
            'users' => User::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Project $project): RedirectResponse
    {
        $validated = $request->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'rate' => ['required', 'numeric', 'min:0'],
            'pricing_type' => ['required', 'in:fixed,hourly'],
            'user_ids' => ['nullable', 'array'],
            'user_ids.*' => ['exists:users,id'],
        ]);

        $project->update([
            'customer_id' => $validated['customer_id'],
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'rate' => $validated['rate'],
            'pricing_type' => $validated['pricing_type'],
        ]);

        $project->users()->sync($validated['user_ids'] ?? []);

        return redirect()
            ->route('projects.show', $project)
            ->with('success', 'Project berhasil diperbarui.');
    }

    public function destroy(Project $project): RedirectResponse
    {
        $project->delete();

        return redirect()
            ->route('projects.index')
            ->with('success', 'Project berhasil dihapus.');
    }
}

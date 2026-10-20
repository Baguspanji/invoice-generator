<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProjectController extends Controller
{
    public function index(Request $request): View
    {
        $customerId = $request->user()->customer_id;

        $projects = Project::with(['customer'])
            ->withCount([
                'tasks as all_tasks_count',
                'tasks as completed_tasks_count' => fn ($query) => $query->whereNotNull('completed_at'),
            ])
            ->where('customer_id', $customerId)
            ->when($request->query('search'), fn ($query, $search) => $query->where('name', 'like', "%{$search}%"))
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('portal.projects.index', ['projects' => $projects]);
    }

    public function show(Request $request, Project $project): View
    {
        abort_unless((int) $project->customer_id === (int) $request->user()->customer_id, 403);

        $project->load([
            'customer',
            'taskGroups.tasks' => fn ($query) => $query
                ->topLevel()
                ->with(['assignedUser', 'labels', 'attachments', 'subtasks.assignedUser'])
                ->orderBy('order_column'),
        ]);

        return view('portal.projects.show', ['project' => $project]);
    }
}

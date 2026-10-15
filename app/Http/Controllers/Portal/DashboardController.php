<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $customerId = $user->customer_id;

        $projects = $user->customer
            ? $user->customer->projects()->withCount([
                'tasks as all_tasks_count',
                'tasks as completed_tasks_count' => fn ($query) => $query->whereNotNull('completed_at'),
            ])->latest()->limit(6)->get()
            : collect();

        $invoices = $customerId
            ? Invoice::with('customer')
                ->where('customer_id', $customerId)
                ->latest('invoice_date')
                ->limit(5)
                ->get()
            : collect();

        return view('portal.dashboard', [
            'customer' => $user->customer,
            'projects' => $projects,
            'invoices' => $invoices,
        ]);
    }
}

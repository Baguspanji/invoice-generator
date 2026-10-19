<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Documentation\ActivityController as DocumentationActivityController;
use App\Http\Controllers\Documentation\AttachmentController as DocumentationAttachmentController;
use App\Http\Controllers\Documentation\CommentController as DocumentationCommentController;
use App\Http\Controllers\Documentation\ExportController as DocumentationExportController;
use App\Http\Controllers\Documentation\PageController as DocumentationPageController;
use App\Http\Controllers\Documentation\PreviewController as DocumentationPreviewController;
use App\Http\Controllers\Documentation\SearchController as DocumentationSearchController;
use App\Http\Controllers\Documentation\TaskLinkController as DocumentationTaskLinkController;
use App\Http\Controllers\Documentation\VersionController as DocumentationVersionController;
use App\Http\Controllers\Documentation\WorkspaceController as DocumentationWorkspaceController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\Portal\DashboardController as PortalDashboardController;
use App\Http\Controllers\Portal\InvoiceController as PortalInvoiceController;
use App\Http\Controllers\Portal\ProjectController as PortalProjectController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProjectInvoiceController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TimeLogController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (! Auth::check()) {
        return view('welcome');
    }

    $user = Auth::user();

    if ($user && method_exists($user, 'isCustomer') && $user->isCustomer()) {
        return redirect()->route('portal.dashboard');
    }

    return redirect()->route('dashboard');
})->name('home');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);
});

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
});

Route::middleware(['auth', 'role:staff'])->group(function (): void {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/export', [ReportController::class, 'export'])->name('reports.export');
    Route::get('/reports/export-excel', [ReportController::class, 'exportExcel'])->name('reports.export-excel');
    Route::get('/reports/recap', [ReportController::class, 'recapPdf'])->name('reports.recap');

    Route::get('/settings', [SettingController::class, 'edit'])->name('settings.edit');
    Route::put('/settings', [SettingController::class, 'update'])->name('settings.update');
    Route::post('/settings/mcp-tokens', [SettingController::class, 'storeToken'])->name('settings.mcp-tokens.store');
    Route::delete('/settings/mcp-tokens/{mcpToken}', [SettingController::class, 'destroyToken'])->name('settings.mcp-tokens.destroy');

    Route::resource('projects', ProjectController::class);
    Route::prefix('projects/{project}')->name('projects.')->group(function (): void {
        Route::post('tasks', [TaskController::class, 'store'])->name('tasks.store');
        Route::put('tasks/{task}', [TaskController::class, 'update'])->name('tasks.update');
        Route::delete('tasks/{task}', [TaskController::class, 'destroy'])->name('tasks.destroy');
        Route::post('tasks/move', [TaskController::class, 'move'])->name('tasks.move');
        Route::post('tasks/{task}/time-log', [TimeLogController::class, 'store'])->name('tasks.time-log.store');
        Route::delete('tasks/{task}/time-log/{timeLog}', [TimeLogController::class, 'destroy'])->name('tasks.time-log.destroy');
        Route::delete('tasks/{task}/attachments/{attachment}', [TaskController::class, 'destroyAttachment'])->name('tasks.attachments.destroy');
        Route::post('invoices/generate', [ProjectInvoiceController::class, 'store'])->name('invoices.generate');
    });

    Route::get('projects/{project}/documentation', [DocumentationWorkspaceController::class, 'show'])
        ->name('projects.documentation.show');
    Route::post('projects/{project}/documentation/pages', [DocumentationPageController::class, 'store'])
        ->name('projects.documentation.pages.store');

    Route::prefix('documentation')->name('documentation.')->group(function (): void {
        Route::get('search', [DocumentationSearchController::class, 'index'])->name('search');

        Route::patch('pages/{page}', [DocumentationPageController::class, 'update'])->name('pages.update');
        Route::delete('pages/{page}', [DocumentationPageController::class, 'destroy'])->name('pages.destroy');
        Route::post('pages/{page}/publish', [DocumentationPageController::class, 'publish'])->name('pages.publish');
        Route::post('pages/{page}/submit-review', [DocumentationPageController::class, 'submitReview'])->name('pages.submit-review');
        Route::post('pages/{page}/request-changes', [DocumentationPageController::class, 'requestChanges'])->name('pages.request-changes');

        Route::get('pages/{page}/versions', [DocumentationVersionController::class, 'index'])->name('versions.index');
        Route::get('pages/{page}/versions/{version}', [DocumentationVersionController::class, 'show'])->name('versions.show');
        Route::post('pages/{page}/restore/{version}', [DocumentationVersionController::class, 'restore'])->name('versions.restore');

        Route::get('pages/{page}/activities', [DocumentationActivityController::class, 'index'])->name('activities.index');
        Route::get('pages/{page}/comments', [DocumentationCommentController::class, 'index'])->name('comments.index');
        Route::post('pages/{page}/comments', [DocumentationCommentController::class, 'store'])->name('comments.store');
        Route::delete('pages/{page}/comments/{comment}', [DocumentationCommentController::class, 'destroy'])->name('comments.destroy');
        Route::get('pages/{page}/export/markdown', [DocumentationExportController::class, 'markdown'])->name('pages.export.markdown');
        Route::get('pages/{page}/export/pdf', [DocumentationExportController::class, 'pdf'])->name('pages.export.pdf');
        Route::post('pages/{page}/preview', [DocumentationPreviewController::class, 'show'])->name('pages.preview');

        Route::post('pages/{page}/tasks', [DocumentationTaskLinkController::class, 'store'])->name('tasks.store');
        Route::delete('pages/{page}/tasks/{task}', [DocumentationTaskLinkController::class, 'destroy'])->name('tasks.destroy');

        Route::post('pages/{page}/attachments', [DocumentationAttachmentController::class, 'store'])->name('attachments.store');
        Route::delete('pages/{page}/attachments/{attachment}', [DocumentationAttachmentController::class, 'destroy'])->name('attachments.destroy');
    });

    Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
    Route::get('/customers/create', [CustomerController::class, 'create'])->name('customers.create');
    Route::post('/customers', [CustomerController::class, 'store'])->name('customers.store');
    Route::get('/customers/{customer}', [CustomerController::class, 'show'])->name('customers.show');
    Route::get('/customers/{customer}/edit', [CustomerController::class, 'edit'])->name('customers.edit');
    Route::put('/customers/{customer}', [CustomerController::class, 'update'])->name('customers.update');
    Route::delete('/customers/{customer}', [CustomerController::class, 'destroy'])->name('customers.destroy');

    Route::get('/invoices', [InvoiceController::class, 'index'])->name('invoices.index');
    Route::get('/invoices/create', [InvoiceController::class, 'create'])->name('invoices.create');
    Route::post('/invoices', [InvoiceController::class, 'store'])->name('invoices.store');
    Route::get('/invoices/export', [InvoiceController::class, 'export'])->name('invoices.export');
    Route::get('/invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');
    Route::get('/invoices/{invoice}/pdf', [InvoiceController::class, 'downloadPdf'])->name('invoices.pdf');
    Route::get('/invoices/{invoice}/excel', [InvoiceController::class, 'exportItems'])->name('invoices.excel');

    Route::post('/invoices/{invoice}/pay', [InvoiceController::class, 'markAsPaid'])
        ->middleware('invoice.unpaid')
        ->name('invoices.pay');
    Route::post('/invoices/{invoice}/cancel', [InvoiceController::class, 'markAsCancelled'])
        ->middleware('invoice.unpaid')
        ->name('invoices.cancel');
});

Route::middleware(['auth', 'role:customer'])->prefix('portal')->name('portal.')->group(function (): void {
    Route::get('/', [PortalDashboardController::class, 'index'])->name('dashboard');
    Route::get('/projects', [PortalProjectController::class, 'index'])->name('projects.index');
    Route::get('/projects/{project}', [PortalProjectController::class, 'show'])->name('projects.show');
    Route::get('/invoices', [PortalInvoiceController::class, 'index'])->name('invoices.index');
    Route::get('/invoices/{invoice}', [PortalInvoiceController::class, 'show'])->name('invoices.show');
    Route::get('/invoices/{invoice}/pdf', [PortalInvoiceController::class, 'downloadPdf'])->name('invoices.pdf');
});

<?php

namespace App\Mcp\Servers;

use App\Mcp\Resources\InvoicePdf;
use App\Mcp\Tools\CancelInvoice;
use App\Mcp\Tools\CreateTask;
use App\Mcp\Tools\DownloadInvoicePdf;
use App\Mcp\Tools\GenerateInvoice;
use App\Mcp\Tools\InvoiceDetail;
use App\Mcp\Tools\ListInvoices;
use App\Mcp\Tools\ListProjects;
use App\Mcp\Tools\MarkInvoicePaid;
use App\Mcp\Tools\ProjectBoard;
use App\Mcp\Tools\UnbilledSummary;
use App\Mcp\Tools\UpdateTask;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

#[Name('IceSum Server')]
#[Version('1.0.0')]
#[Instructions('Access to IceSum projects, kanban boards and invoices. Read tools: list-projects to discover IDs, project-board for task status, list-invoices and invoice-detail for billing, unbilled-summary before generating an invoice. Use download-invoice-pdf for PDF metadata plus a resource_link, then read resource invoice://{invoice_id}/pdf to download the PDF binary. Write tools: create-task and update-task for task management, generate-invoice to bill project tasks, mark-invoice-paid and cancel-invoice (UNPAID only, cancel cannot be undone).')]
class IceSumServer extends Server
{
    protected array $tools = [
        ListProjects::class,
        ProjectBoard::class,
        CreateTask::class,
        UpdateTask::class,
        ListInvoices::class,
        InvoiceDetail::class,
        UnbilledSummary::class,
        GenerateInvoice::class,
        MarkInvoicePaid::class,
        CancelInvoice::class,
        DownloadInvoicePdf::class,
    ];

    protected array $resources = [
        InvoicePdf::class,
    ];

    protected array $prompts = [
        //
    ];
}

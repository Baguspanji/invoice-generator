<?php

namespace App\Mcp\Servers;

use App\Mcp\Tools\InvoiceDetail;
use App\Mcp\Tools\ListInvoices;
use App\Mcp\Tools\ListProjects;
use App\Mcp\Tools\ProjectBoard;
use App\Mcp\Tools\UnbilledSummary;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

#[Name('IceSum Server')]
#[Version('1.0.0')]
#[Instructions('Read-only access to IceSum projects, kanban boards and invoices. Use list-projects to discover project IDs, project-board for task status, list-invoices and invoice-detail for billing, and unbilled-summary before generating an invoice.')]
class IceSumServer extends Server
{
    protected array $tools = [
        ListProjects::class,
        ProjectBoard::class,
        ListInvoices::class,
        InvoiceDetail::class,
        UnbilledSummary::class,
    ];

    protected array $resources = [
        //
    ];

    protected array $prompts = [
        //
    ];
}

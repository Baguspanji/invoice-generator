<?php

namespace App\Http\Middleware;

use App\Enums\InvoiceStatus;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureInvoiceIsUnpaid
{
    /**
     * Pastikan transisi status (Paid/Cancelled) hanya berlaku
     * untuk invoice yang masih UNPAID, sesuai alur PRD:
     * Unpaid → Paid → Cancelled.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $invoice = $request->route('invoice');

        if ($invoice && $invoice->status !== InvoiceStatus::UNPAID) {
            return back()->withErrors([
                'status' => 'Hanya invoice berstatus UNPAID yang dapat diubah statusnya.',
            ]);
        }

        return $next($request);
    }
}

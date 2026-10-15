<?php

namespace App\Http\Middleware;

use App\Models\McpToken;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureMcpToken
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $plaintext = $request->bearerToken();

        $token = $plaintext ? McpToken::findForPlaintext($plaintext) : null;

        if (! $token || $token->isExpired()) {
            return response()->json(['message' => 'Invalid or expired MCP token.'], 401);
        }

        $token->forceFill(['last_used_at' => now()])->save();

        return $next($request);
    }
}

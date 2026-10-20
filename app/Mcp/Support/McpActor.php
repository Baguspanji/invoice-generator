<?php

namespace App\Mcp\Support;

use App\Enums\UserRole;
use App\Models\User;

class McpActor
{
    /**
     * Token MCP tidak terikat user, sehingga aksi tulis dicatat atas nama staff pertama.
     */
    public static function resolve(): ?User
    {
        return User::where('role', UserRole::STAFF->value)->oldest('id')->first();
    }
}

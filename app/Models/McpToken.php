<?php

namespace App\Models;

use Database\Factories\McpTokenFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

#[Fillable([
    'name',
    'token',
    'prefix',
    'last_used_at',
    'expires_at',
])]
class McpToken extends Model
{
    /** @use HasFactory<McpTokenFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'last_used_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    /**
     * Create a token and return [model, plaintext]. Only the hash is stored.
     *
     * @return array{0: static, 1: string}
     */
    public static function createToken(string $name, ?\DateTimeInterface $expiresAt = null): array
    {
        $plaintext = 'icesum_'.Str::random(40);

        $token = static::create([
            'name' => $name,
            'token' => hash('sha256', $plaintext),
            'prefix' => substr($plaintext, 0, 12),
            'expires_at' => $expiresAt,
        ]);

        return [$token, $plaintext];
    }

    public static function findForPlaintext(string $plaintext): ?static
    {
        return static::where('token', hash('sha256', $plaintext))->first();
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }
}

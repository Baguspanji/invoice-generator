<?php

namespace Database\Factories;

use App\Models\McpToken;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<McpToken>
 */
class McpTokenFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $plaintext = 'icesum_'.Str::random(40);

        return [
            'name' => fake()->words(2, true),
            'token' => hash('sha256', $plaintext),
            'prefix' => substr($plaintext, 0, 12),
            'last_used_at' => null,
            'expires_at' => null,
        ];
    }
}

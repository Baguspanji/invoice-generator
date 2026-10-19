<?php

namespace App\Services\Documentation;

use RuntimeException;

/**
 * Dilempar saat `lock_version` halaman tidak cocok dengan yang dikirim klien
 * (optimistic concurrency control). Dipetakan ke HTTP 409 di controller.
 */
class StaleContentException extends RuntimeException
{
    public function __construct(
        public readonly int $currentLockVersion,
        string $message = 'Halaman telah diubah oleh pengguna lain.',
    ) {
        parent::__construct($message);
    }
}

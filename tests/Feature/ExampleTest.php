<?php

test('halaman welcome dapat ditampilkan untuk tamu', function () {
    $response = $this->get('/');

    $response->assertOk();
    $response->assertSee('Auto-Invoice Engine');
    $response->assertSee('Revenue Summary');
});

<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $defaults = [
            // Identitas pengirim (blok DARI pada PDF invoice).
            'sender_id_number' => '1234567890123',
            'sender_name' => 'Muhammad Bagus Panji',
            'sender_address' => 'Bakalan Purwosari Pasuruan',
            'sender_phone' => '+62 857-8580-0430',

            // Instruksi pembayaran.
            'bank_name' => 'Bank Central Asia',
            'bank_account_number' => '12345678',
            'bank_account_name' => 'Muhammad Bagus Panji',
            'payment_note' => 'Mohon sertakan Nomor Invoice pada berita transfer.',

            // Judul dokumen.
            'invoice_title' => 'INVOICE',
            'invoice_subtitle' => 'TAGIHAN PENGEMBANGAN APLIKASI',

            // Tanda tangan pengirim (base64 PNG dari canvas halaman pengaturan).
            'signature_image' => null,
        ];

        foreach ($defaults as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingController extends Controller
{
    /**
     * @var array<string, array<string, string>>
     */
    private array $groups = [
        'sender' => [
            'sender_id_number' => 'No. KTP Pengirim',
            'sender_name' => 'Nama Pengirim',
            'sender_address' => 'Alamat Pengirim',
            'sender_phone' => 'No. HP Pengirim',
        ],
        'bank' => [
            'bank_name' => 'Nama Bank',
            'bank_account_number' => 'No. Rekening',
            'bank_account_name' => 'Atas Nama',
            'payment_note' => 'Catatan Pembayaran',
        ],
        'document' => [
            'invoice_title' => 'Judul Dokumen',
            'invoice_subtitle' => 'Subjudul Dokumen',
        ],
    ];

    /**
     * @var array<string, string>
     */
    private array $textareas = [
        'sender_address' => '1',
        'payment_note' => '1',
    ];

    public function edit(): View
    {
        $keys = [];

        foreach ($this->groups as $fields) {
            foreach ($fields as $key => $label) {
                $keys[] = $key;
            }
        }

        return view('settings.edit', [
            'groups' => $this->groupLabels(),
            'fields' => $this->groups,
            'textareas' => $this->textareas,
            'values' => Setting::getMany($keys),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'settings' => ['required', 'array'],
            'settings.*' => ['nullable', 'string'],
        ]);

        foreach ($validated['settings'] as $key => $value) {
            Setting::set($key, $value);
        }

        return back()->with('success', 'Pengaturan berhasil disimpan.');
    }

    /**
     * @return array<string, string>
     */
    private function groupLabels(): array
    {
        return [
            'sender' => 'Identitas Pengirim (Blok DARI)',
            'bank' => 'Instruksi Pembayaran',
            'document' => 'Judul Dokumen',
        ];
    }
}

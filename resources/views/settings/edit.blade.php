@extends('layouts.app')

@section('title', 'Pengaturan — IceSum')

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-dark dark:text-white">Pengaturan</h1>
        <p class="text-sm text-slate-500 dark:text-slate-400">Data ini digunakan pada PDF invoice (blok DARI & instruksi pembayaran).</p>
    </div>

    <form method="POST" action="{{ route('settings.update') }}" class="space-y-6">
        @csrf
        @method('PUT')

        @foreach ($fields as $group => $groupFields)
            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900">
                <h3 class="mb-4 text-lg font-bold text-dark dark:text-white">{{ $groups[$group] }}</h3>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    @foreach ($groupFields as $key => $label)
                        <div class="{{ isset($textareas[$key]) ? 'md:col-span-2' : '' }}">
                            <label for="setting-{{ $key }}" class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ $label }}</label>
                            @if (isset($textareas[$key]))
                                <textarea name="settings[{{ $key }}]" id="setting-{{ $key }}" rows="2"
                                    class="mt-1 block w-full rounded-lg border border-slate-200 py-2.5 px-3 text-sm focus:border-primary focus:ring-primary dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">{{ old('settings.'.$key, $values[$key] ?? '') }}</textarea>
                            @else
                                <input type="text" name="settings[{{ $key }}]" id="setting-{{ $key }}" value="{{ old('settings.'.$key, $values[$key] ?? '') }}"
                                    class="mt-1 block w-full rounded-lg border border-slate-200 py-2.5 px-3 text-sm focus:border-primary focus:ring-primary dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach

        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900">
            <h3 class="mb-1 text-lg font-bold text-dark dark:text-white">Tanda Tangan</h3>
            <p class="mb-4 text-sm text-slate-500 dark:text-slate-400">Gambar tanda tangan di bawah ini. Tersimpan sebagai gambar dan tampil di PDF invoice.</p>

            <input type="hidden" name="settings[signature_image]" id="signature-input" value="{{ old('settings.signature_image', $values['signature_image'] ?? '') }}">

            <div class="overflow-hidden rounded-lg border border-slate-200 bg-white dark:border-slate-700">
                <canvas id="signature-canvas" class="block h-40 w-full cursor-crosshair touch-none"></canvas>
            </div>

            <div class="mt-3 flex items-center gap-2">
                <button type="button" onclick="clearSignature()"
                    class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-600 shadow-sm transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700">
                    Hapus
                </button>
                <span class="text-xs text-slate-400">Tersimpan otomatis ke formulir saat menggambar.</span>
            </div>
        </div>

        <div class="flex items-center justify-end">
            <button type="submit"
                class="rounded-lg bg-primary px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700">
                Simpan Pengaturan
            </button>
        </div>
    </form>

    <div class="mt-6 rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900">
        <h3 class="text-lg font-bold text-dark dark:text-white">Token API MCP</h3>
        <p class="mb-4 text-sm text-slate-500 dark:text-slate-400">
            Diperlukan sebagai header <code class="rounded bg-slate-100 px-1 font-mono text-xs dark:bg-slate-800">Authorization: Bearer &lt;token&gt;</code>
            untuk mengakses endpoint <code class="rounded bg-slate-100 px-1 font-mono text-xs dark:bg-slate-800">POST {{ url('/mcp/icesum') }}</code>.
        </p>

        @if (session('mcp_token_plaintext'))
            <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 p-4 dark:border-emerald-900 dark:bg-emerald-950">
                <p class="text-sm font-semibold text-emerald-700 dark:text-emerald-300">Salin token ini sekarang — hanya ditampilkan sekali:</p>
                <p class="mt-2 break-all rounded bg-white px-3 py-2 font-mono text-sm dark:bg-slate-900">{{ session('mcp_token_plaintext') }}</p>
            </div>
        @endif

        @if ($mcpTokens->isEmpty())
            <p class="mb-4 text-sm italic text-slate-400">Belum ada token.</p>
        @else
            <div class="mb-4 overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-700">
                    <thead>
                        <tr class="text-left text-xs uppercase tracking-wider text-slate-500">
                            <th class="py-2 pr-4">Nama</th>
                            <th class="py-2 pr-4">Prefix</th>
                            <th class="py-2 pr-4">Terakhir dipakai</th>
                            <th class="py-2 pr-4">Kedaluwarsa</th>
                            <th class="py-2 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach ($mcpTokens as $mcpToken)
                            <tr>
                                <td class="py-2 pr-4 font-medium">{{ $mcpToken->name }}</td>
                                <td class="py-2 pr-4 font-mono text-xs">{{ $mcpToken->prefix }}…</td>
                                <td class="py-2 pr-4 text-slate-500">{{ $mcpToken->last_used_at?->format('d/m/Y H:i') ?? '-' }}</td>
                                <td class="py-2 pr-4 text-slate-500">{{ $mcpToken->expires_at?->format('d/m/Y') ?? 'Tidak pernah' }}</td>
                                <td class="py-2 text-right">
                                    <form method="POST" action="{{ route('settings.mcp-tokens.destroy', $mcpToken) }}"
                                        onsubmit="return confirm('Cabut token {{ $mcpToken->name }}?')" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-sm font-medium text-red-600 hover:underline">Cabut</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <form method="POST" action="{{ route('settings.mcp-tokens.store') }}" class="flex flex-col gap-3 sm:flex-row sm:items-end">
            @csrf
            <div class="flex-1">
                <label for="mcp-token-name" class="block text-sm font-medium text-slate-700 dark:text-slate-300">Nama token</label>
                <input type="text" name="name" id="mcp-token-name" value="{{ old('name') }}" required placeholder="mis. Claude Desktop"
                    class="mt-1 block w-full rounded-lg border border-slate-200 py-2.5 px-3 text-sm focus:border-primary focus:ring-primary dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">
                @error('name')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="mcp-token-expiry" class="block text-sm font-medium text-slate-700 dark:text-slate-300">Masa berlaku</label>
                <select name="expires_in" id="mcp-token-expiry"
                    class="mt-1 block rounded-lg border border-slate-200 bg-white py-2.5 px-3 text-sm focus:border-primary focus:ring-primary dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">
                    <option value="never">Tidak kedaluwarsa</option>
                    <option value="30">30 hari</option>
                    <option value="90">90 hari</option>
                    <option value="365">1 tahun</option>
                </select>
            </div>
            <button type="submit"
                class="rounded-lg bg-primary px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700">
                Generate Token
            </button>
        </form>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            const canvas = document.getElementById('signature-canvas');
            const input = document.getElementById('signature-input');
            if (!canvas || !input) return;

            const ctx = canvas.getContext('2d');
            let drawing = false;

            function setupCanvas() {
                const ratio = window.devicePixelRatio || 1;
                const rect = canvas.getBoundingClientRect();
                canvas.width = rect.width * ratio;
                canvas.height = rect.height * ratio;
                ctx.scale(ratio, ratio);
                ctx.lineWidth = 2.5;
                ctx.lineCap = 'round';
                ctx.lineJoin = 'round';
                ctx.strokeStyle = '#0f172a';
            }

            function canvasPos(event) {
                const rect = canvas.getBoundingClientRect();
                const point = event.touches ? event.touches[0] : event;
                return { x: point.clientX - rect.left, y: point.clientY - rect.top };
            }

            function startDraw(event) {
                event.preventDefault();
                drawing = true;
                const pos = canvasPos(event);
                ctx.beginPath();
                ctx.moveTo(pos.x, pos.y);
            }

            function moveDraw(event) {
                if (!drawing) return;
                event.preventDefault();
                const pos = canvasPos(event);
                ctx.lineTo(pos.x, pos.y);
                ctx.stroke();
            }

            function endDraw() {
                if (!drawing) return;
                drawing = false;
                input.value = canvas.toDataURL('image/png');
            }

            window.clearSignature = function () {
                const rect = canvas.getBoundingClientRect();
                ctx.save();
                ctx.setTransform(1, 0, 0, 1, 0, 0);
                ctx.clearRect(0, 0, canvas.width, canvas.height);
                ctx.restore();
                input.value = '';
            };

            canvas.addEventListener('mousedown', startDraw);
            canvas.addEventListener('mousemove', moveDraw);
            canvas.addEventListener('mouseup', endDraw);
            canvas.addEventListener('mouseleave', endDraw);
            canvas.addEventListener('touchstart', startDraw, { passive: false });
            canvas.addEventListener('touchmove', moveDraw, { passive: false });
            canvas.addEventListener('touchend', endDraw);

            setupCanvas();
            restoreFromInput();
            window.addEventListener('resize', function () {
                setupCanvas();
                restoreFromInput();
            });

            function restoreFromInput() {
                if (!input.value || input.value.indexOf('data:image') !== 0) return;
                const img = new Image();
                img.onload = function () {
                    const rect = canvas.getBoundingClientRect();
                    ctx.drawImage(img, 0, 0, rect.width, rect.height);
                };
                img.src = input.value;
            }
        })();
    </script>
@endpush

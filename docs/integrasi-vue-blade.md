# Integrasi Vue 3 + Blade (Laravel + Vite)

Dokumen ini merangkum pola integrasi Vue 3 ke Blade yang dipakai di proyek ini,
agar bisa diterapkan ulang di proyek Laravel lain. Prinsip utama: **Blade tetap
menjadi shell** (routing, auth, data awal, SEO/initial paint), **Vue mengambil
alih interaktivitas** per halaman melalui SFC (`.vue`).

## 1. Arsitektur singkat

```
Blade (@extends layout)
 └─ <div id="xxx-app" data-config='@json($cfg)' data-title="...">…loader…</div>
 └─ @vite(['resources/js/pages/xxx.js'])          ← entry per halaman
        └─ createApp(Root.vue, props).mount(el)   ← props dibaca dari data-*
               └─ Root.vue + Section.vue + components/ bersama
```

- Satu halaman = satu entry `resources/js/pages/*.js` + satu root SFC.
- Data server → klien hanya lewat **JSON** (`data-*` + `@json`), tidak ada
  logika tanggal/format di JS — semua diformat di Blade/PHP.
- Submit form memakai **FormData dari elemen `<form>`** (`new FormData(formRef)`),
  sehingga `name` input adalah kontrak payload; `v-model` hanya untuk state UI.

## 2. Instalasi di proyek lain

```bash
pnpm add vue
pnpm add -D @vitejs/plugin-vue
```

`vite.config.js`:

```js
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    resolve: {
        // Build penuh: dibutuhkan bila memakai window.mountVue (template in-DOM).
        // Bila murni SFC, alias ini boleh dihapus agar bundle lebih ramping.
        alias: { vue: 'vue/dist/vue.esm-bundler.js' },
    },
    define: {
        __VUE_OPTIONS_API__: true,
        __VUE_PROD_DEVTOOLS__: false,
    },
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/pages/contoh-form.js', // ← tambah entry per halaman
            ],
            refresh: ['resources/views/**', 'routes/**'],
        }),
        vue(),
    ],
});
```

> Versi acuan proyek ini: `vue ^3.5`, `@vitejs/plugin-vue ^6`, `vite ^5`,
> `laravel-vite-plugin ^1`. Sesuaikan pasangan versi plugin-vue ↔ vite bila beda.

## 3. Struktur direktori acuan

```
resources/js/
├── app.js                  # bundle global (lihat §4)
├── bootstrap.js            # axios bawaan Laravel
├── vue-globals.js          # window.mountVue + helper select2 (lihat §6)
├── components/
│   ├── Select2.vue         # pembungkus Select2 (jQuery CDN)
│   ├── GeoPicker.vue       # GPS browser + Leaflet (Leaflet via CDN)
│   ├── FotoUpload.vue      # upload langsung + kompresi (props: max, fieldName)
│   ├── RadioChips.vue      # grup radio gaya chip
│   └── <fitur>/            # komponen khusus halaman (App + Section/*)
└── pages/
    └── <halaman>.js        # entry tipis per halaman (lihat §5)
```

## 4. Bundle global (`app.js`)

Dimuat layout lewat `@vite(['resources/css/app.css', 'resources/js/app.js'])`.
Library berat non-Vue **tetap via CDN** di layout agar tidak masuk bundle dan
bisa dipakai bertahap: jQuery, Select2, toastr/SweetAlert2, flatpickr, Leaflet.

```js
import './bootstrap';
import './vue-globals';

if (window.jQuery) {
    window.jQuery.ajaxSetup({
        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content },
    });
}
```

Di dalam SFC, global CDN diakses lewat `window.*` (`window.$`, `window.flatpickr`,
`window.toastr`, `window.imageCompression`, `window.L`). Jangan `import` ulang
dari npm bila sudah ada versi CDN-nya — pilih satu sumber per library.

## 5. Pola utama: halaman SFC penuh

### 5.1 Entry tipis (`resources/js/pages/xxx.js`)

```js
import { createApp } from 'vue';
import XxxApp from '../components/xxx/XxxApp.vue';

const el = document.getElementById('xxx-app');

if (el) {
    let config = {};
    try {
        config = JSON.parse(el.dataset.config || '{}');
    } catch {
        config = {};
    }

    createApp(XxxApp, {
        config,
        title: el.dataset.title || '',
        backUrl: el.dataset.backUrl || '',
    }).mount(el);
}
```

`if (el)` membuat entry aman dimuat walau elemen tidak ada.

### 5.2 Shell Blade tipis

```blade
@section('content')
    @php
        // Siapkan SEMUA yang dibutuhkan Vue: flags, urls, options [{id,text}],
        // values (old() diutamakan agar input tidak hilang saat validasi gagal),
        // foto [{path, url}], teks tampilan, dll.
        $formConfig = [/* ... */];
    @endphp

    <div class="p-4 sm:p-6 space-y-5">
        @if ($errors->any()) {{-- box error server, tetap di Blade --}} @endif

        <div id="xxx-app"
            data-config='@json($formConfig)'
            data-title="..."
            data-back-url="...">
            {{-- placeholder tampil sebelum Vue mount --}}
            <div class="...">Memuat…</div>
        </div>
    </div>
@endsection

@section('scripts')
    {{-- CDN yang dibutuhkan halaman ini (locale flatpickr, Leaflet, compression) --}}
    @vite(['resources/js/pages/xxx.js'])
@endsection
```

Catatan:

- `@json` aman di dalam atribut kutip-satu (quote di-escape).
- `data-*` hanya menampung string; objek/array selalu lewat satu blob JSON.

### 5.3 Root SFC: state di root, anak presentasional

```vue
<script setup>
import { reactive, ref } from 'vue';
import SectionA from './SectionA.vue';

const props = defineProps({ config: Object, title: String, backUrl: String });
const cfg = props.config;

const form = reactive({ ...(cfg.values || {}) }); // SATU objek form untuk semua section
const fotos = ref([...(cfg.fotos || [])]);

function save() {
    const data = new FormData(formRef.value); // payload dari DOM, bukan dari state
    // ...transformasi khusus (join checkbox, mapping) lalu fetch...
}
</script>

<template>
    <form ref="formRef" method="POST" :action="cfg.formAction">
        <input type="hidden" name="_token" :value="cfg.csrf" />
        <SectionA v-model:form="form" :options="cfg.options?.x || []" @sesuatu="handlerDiRoot" />
        <button>Simpan</button>
    </form>
</template>
```

Konvensi yang terbukti rapi:

- `v-model:form="form"` (satu objek) ke section; `defineModel('form')` di anak.
  Untuk nilai tunggal: `v-model:query="query"` + `defineModel('query')` di anak
  (contoh di repo: `defineModel('fotos')` pada `FotoUpload.vue`,
  `defineModel('lat')` pada `GeoPicker.vue`).
- Event ke atas via `defineEmits` (`@kecamatan-change="onKecamatanChange"`);
  fetch cascade (`window.$.getJSON`) dan state option-list milik root.
- Opsi select selalu bentuk `[{ id: string, text: string }]`; normalisasi di Blade
  (`(string)` cast) agar komparasi `v-model` konsisten.
- Nilai awal radio/checkbox/select **wajib** diinisialisasi dari JSON —
  `v-model` menimpa elemen saat mount, bukan membaca dari DOM.

### 5.4 Submit & transformasi payload

Karena payload dibaca dari DOM, transformasi dilakukan tepat sebelum kirim:

```js
// Contoh: gabung grup checkbox menjadi string koma (backend expects string).
data.delete('pola_asuh[]');
if (vals.length) data.set('pola_asuh', vals.join(','));
```

Untuk submit native (tanpa AJAX), lakukan transformasi yang sama di DOM
(hidden input + cabut `name` checkbox) sebelum `form.submit()`.
**Plus jaring pengaman backend**: normalisasi array → string di controller
sebelum validasi, agar klien apapun tidak bisa mengirim array.

## 6. Pola kedua: `window.mountVue` (template in-DOM)

Tersedia di `vue-globals.js` untuk增强 form Blade yang sudah ada tanpa
menulis ulang markup ke SFC (butuh alias build penuh, lihat §2):

```js
window.mountVue('#form-lama', { data() { ... }, methods: { ... } });
```

Aturan main pola ini:

- Delimiter `[[ ]]` (di-set di `mountVue`) agar tidak tabrakan dengan `{{ }}`.
- Komponen kustom **dilarang self-closing** (`<x-comp></x-comp>`, bukan `<x-comp/>`)
  karena parser HTML akan menelannya.
- Nilai awal `v-model` tetap harus dari data JS, bukan dari DOM.
- Elemen yang di-init script pihak ketiga (peta, editor) harus **statis**
  (tanpa binding) agar tidak di-render ulang Vue.

Pola SFC (§5) adalah default untuk halaman baru; `mountVue` hanya untuk
retrofit cepat.

## 7. Aturan Tailwind + Vite

- **Jangan kirim string class Tailwind via JSON.** Compiler tidak bisa
  mendeteksinya. Semua varian kondisional ditulis literal di source SFC
  (`v-if`/`v-else`, map konstanta di file) — contoh: peta status → class
  di `EpisodeTimeline.vue`.
- Chunk sharing otomatis: komponen bersama (`Select2`, `FotoUpload`,
  `GeoPicker`) ter-split sendiri oleh Rollup dan dipakai ulang antar entry.
- Setelah ubah `vite.config.js` (tambah entry), jalankan `pnpm build`
  sekali agar `manifest.json` terbit; `pnpm dev` untuk HMR harian.

## 8. Checklist adaptasi ke proyek lain

1. `pnpm add vue` + `pnpm add -D @vitejs/plugin-vue`; sesuaikan `vite.config.js` (§2).
2. Pastikan layout memuat `@vite(['resources/css/app.css', 'resources/js/app.js'])`
   + CDN yang dibutuhkan + `<meta name="csrf-token">`.
3. Salin `app.js` (sesuaikan isi), `vue-globals.js`, dan komponen bersama
   yang relevan (`Select2`, `FotoUpload`, `RadioChips`).
4. Buat entry `pages/xxx.js` + root SFC + daftarkan di `vite.config.js`.
5. Tulis shell Blade (§5.2): siapkan `values` (old() dulu), `options`,
   `urls` (pakai `route()`/`url()`), `csrf`, teks tampilan.
6. Pertahankan `name` input = kontrak API backend; baca payload via FormData.
7. Verifikasi: `pnpm build` sukses, tidak ada error console, submit
   create + edit + validasi-gagal (input tidak hilang) dicoba manual.

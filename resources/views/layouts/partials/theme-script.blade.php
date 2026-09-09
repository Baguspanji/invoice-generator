<script>
    (function () {
        try {
            var stored = localStorage.getItem('theme');
            if (stored === 'dark' || (!stored && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        } catch (e) {
            // localStorage tidak tersedia, gunakan tema terang.
        }
    })();

    function toggleTheme() {
        var isDark = document.documentElement.classList.toggle('dark');
        try {
            localStorage.setItem('theme', isDark ? 'dark' : 'light');
        } catch (e) {
            // Abaikan kegagalan penyimpanan.
        }
        document.querySelectorAll('[data-theme-icon="sun"]').forEach(function (el) {
            el.classList.toggle('hidden', !isDark);
        });
        document.querySelectorAll('[data-theme-icon="moon"]').forEach(function (el) {
            el.classList.toggle('hidden', isDark);
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        var isDark = document.documentElement.classList.contains('dark');
        document.querySelectorAll('[data-theme-icon="sun"]').forEach(function (el) {
            el.classList.toggle('hidden', !isDark);
        });
        document.querySelectorAll('[data-theme-icon="moon"]').forEach(function (el) {
            el.classList.toggle('hidden', isDark);
        });
    });
</script>

import Toastify from 'toastify-js';
import 'toastify-js/src/toastify.css';

function toastTheme() {
    return document.documentElement.classList.contains('dark');
}

function showToast(text, type = 'success') {
    const dark = toastTheme();

    const styles = {
        success: {
            background: dark ? '#064e3b' : '#ecfdf5',
            color: dark ? '#6ee7b7' : '#047857',
            border: dark ? '1px solid #065f46' : '1px solid #a7f3d0',
        },
        error: {
            background: dark ? '#7f1d1d' : '#fef2f2',
            color: dark ? '#fca5a5' : '#b91c1c',
            border: dark ? '1px solid #991b1b' : '1px solid #fecaca',
        },
    };

    const style = styles[type] ?? styles.success;

    Toastify({
        text,
        duration: 4000,
        close: true,
        gravity: 'top',
        position: 'right',
        stopOnFocus: true,
        style: {
            background: style.background,
            color: style.color,
            border: style.border,
            borderRadius: '0.5rem',
            fontSize: '0.875rem',
            fontWeight: '500',
            fontFamily: "'Inter', ui-sans-serif, system-ui, sans-serif",
            boxShadow: '0 10px 15px -3px rgb(0 0 0 / 0.1)',
            maxWidth: 'calc(100vw - 2rem)',
        },
    }).showToast();
}

function showFlashMessages() {
    const flash = window.__flash;
    if (!flash) {
        return;
    }

    if (flash.success) {
        showToast(flash.success, 'success');
    }

    (flash.errors ?? []).forEach((error) => showToast(error, 'error'));
}

window.showToast = showToast;

showFlashMessages();

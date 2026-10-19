<script setup>
import { nextTick, ref, watch } from 'vue';

const props = defineProps({
    open: {
        type: Boolean,
        default: false,
    },
    html: {
        type: String,
        default: '',
    },
    title: {
        type: String,
        default: '',
    },
    loading: {
        type: Boolean,
        default: false,
    },
});

defineEmits(['close']);

const container = ref(null);

let mermaidPromise = null;

/**
 * Muat Mermaid hanya saat pratinjau dibuka agar bundle awal tetap ringan.
 */
function loadMermaid() {
    if (!mermaidPromise) {
        mermaidPromise = import('mermaid').then((module) => {
            const mermaid = module.default;

            mermaid.initialize({
                startOnLoad: false,
                securityLevel: 'strict',
                theme: 'default',
            });

            return mermaid;
        });
    }

    return mermaidPromise;
}

watch(
    [() => props.open, () => props.html],
    async () => {
        if (!props.open) {
            return;
        }

        await nextTick();

        if (!container.value) {
            return;
        }

        const nodes = Array.from(container.value.querySelectorAll('.mermaid:not([data-processed])'));

        if (!nodes.length) {
            return;
        }

        try {
            const mermaid = await loadMermaid();
            await mermaid.run({ nodes });
        } catch (e) {
            console.error('Gagal merender diagram Mermaid:', e);
        }
    }
);
</script>

<template>
    <div v-if="open" class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-slate-900/60 p-4"
        @click.self="$emit('close')">
        <div class="my-6 w-full max-w-4xl rounded-xl bg-white dark:bg-slate-900">
            <div
                class="flex items-center justify-between gap-3 border-b border-slate-200 px-5 py-3 dark:border-slate-700">
                <h3 class="min-w-0 truncate text-base font-semibold text-dark dark:text-white">
                    Pratinjau &middot; {{ title }}
                </h3>
                <button type="button"
                    class="shrink-0 rounded-md border border-slate-200 px-2 py-1 text-xs font-semibold text-slate-500 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800"
                    @click="$emit('close')">
                    Tutup
                </button>
            </div>

            <div v-if="loading" class="p-8 text-center text-sm text-slate-400">Memuat pratinjau…</div>

            <div v-else ref="container" class="preview-content px-6 py-5" v-html="html"></div>
        </div>
    </div>
</template>

<style scoped>
.preview-content :deep(h1) {
    margin: 1.25rem 0 0.5rem;
    font-size: 1.75rem;
    font-weight: 700;
}

.preview-content :deep(h2) {
    margin: 1rem 0 0.5rem;
    font-size: 1.35rem;
    font-weight: 700;
}

.preview-content :deep(h3) {
    margin: 0.75rem 0 0.5rem;
    font-size: 1.1rem;
    font-weight: 600;
}

.preview-content :deep(p) {
    margin-bottom: 0.75rem;
    line-height: 1.7;
}

.preview-content :deep(ul) {
    margin: 0.5rem 0 0.75rem 1.25rem;
    list-style: disc;
}

.preview-content :deep(ol) {
    margin: 0.5rem 0 0.75rem 1.25rem;
    list-style: decimal;
}

.preview-content :deep(blockquote) {
    margin: 0.75rem 0;
    border-left: 3px solid rgb(148 163 184);
    padding-left: 0.75rem;
    color: rgb(100 116 139);
}

.preview-content :deep(pre) {
    margin: 0.75rem 0;
    overflow-x: auto;
    border-radius: 0.5rem;
    background: rgb(15 23 42);
    padding: 0.75rem 1rem;
    color: rgb(226 232 240);
    font-size: 0.875rem;
}

.preview-content :deep(table) {
    width: 100%;
    border-collapse: collapse;
    margin: 0.75rem 0;
}

.preview-content :deep(th),
.preview-content :deep(td) {
    border: 1px solid rgb(203 213 225);
    padding: 0.4rem 0.6rem;
    text-align: left;
}

.preview-content :deep(th) {
    background: rgb(241 245 249);
}

.preview-content :deep(.mermaid) {
    margin: 0.75rem 0;
    text-align: center;
}

.preview-content :deep(.callout) {
    margin: 0.75rem 0;
    border-left: 3px solid rgb(37 99 235);
    background: rgb(248 250 252);
    padding: 0.5rem 0.75rem;
}

.preview-content :deep(img) {
    max-width: 100%;
    height: auto;
    border-radius: 0.5rem;
    margin: 0.5rem 0;
}
</style>

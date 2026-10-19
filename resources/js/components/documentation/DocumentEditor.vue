<script setup>
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { Editor, EditorContent } from '@tiptap/vue-3';
import StarterKit from '@tiptap/starter-kit';
import Placeholder from '@tiptap/extension-placeholder';

const props = defineProps({
    modelValue: {
        type: Object,
        default: null,
    },
});

const emit = defineEmits(['update:modelValue', 'change']);

const editor = ref(null);

function emptyDoc() {
    return { type: 'doc', content: [] };
}

function toolbarClass(active) {
    return active
        ? 'rounded-md bg-primary/10 px-2 py-1 text-xs font-semibold text-primary'
        : 'rounded-md px-2 py-1 text-xs font-semibold text-slate-500 hover:bg-slate-100 hover:text-slate-800 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-white';
}

onMounted(() => {
    editor.value = new Editor({
        content: props.modelValue || emptyDoc(),
        extensions: [
            StarterKit,
            Placeholder.configure({ placeholder: 'Tulis dokumentasi di sini…' }),
        ],
        editorProps: {
            attributes: {
                class: 'doc-content focus:outline-none',
            },
        },
        onUpdate: ({ editor: instance }) => {
            emit('update:modelValue', instance.getJSON());
            emit('change');
        },
    });
});

watch(
    () => props.modelValue,
    (value) => {
        if (!editor.value) {
            return;
        }

        const current = JSON.stringify(editor.value.getJSON());
        const next = JSON.stringify(value || emptyDoc());

        if (current === next) {
            return;
        }

        editor.value.commands.setContent(value || emptyDoc());
    }
);

onBeforeUnmount(() => {
    editor.value?.destroy();
    editor.value = null;
});
</script>

<template>
    <div class="doc-editor rounded-xl border border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-900">
        <div v-if="editor" class="flex flex-wrap items-center gap-1 border-b border-slate-200 p-2 dark:border-slate-700">
            <button type="button" :class="toolbarClass(editor.isActive('bold'))"
                @click="editor.chain().focus().toggleBold().run()">B</button>
            <button type="button" :class="toolbarClass(editor.isActive('italic'))"
                @click="editor.chain().focus().toggleItalic().run()"><em>I</em></button>
            <button type="button" :class="toolbarClass(editor.isActive('strike'))"
                @click="editor.chain().focus().toggleStrike().run()"><s>S</s></button>
            <span class="mx-1 h-4 w-px bg-slate-200 dark:bg-slate-700"></span>
            <button type="button" :class="toolbarClass(editor.isActive('heading', { level: 1 }))"
                @click="editor.chain().focus().toggleHeading({ level: 1 }).run()">H1</button>
            <button type="button" :class="toolbarClass(editor.isActive('heading', { level: 2 }))"
                @click="editor.chain().focus().toggleHeading({ level: 2 }).run()">H2</button>
            <button type="button" :class="toolbarClass(editor.isActive('heading', { level: 3 }))"
                @click="editor.chain().focus().toggleHeading({ level: 3 }).run()">H3</button>
            <span class="mx-1 h-4 w-px bg-slate-200 dark:bg-slate-700"></span>
            <button type="button" :class="toolbarClass(editor.isActive('bulletList'))"
                @click="editor.chain().focus().toggleBulletList().run()">• List</button>
            <button type="button" :class="toolbarClass(editor.isActive('orderedList'))"
                @click="editor.chain().focus().toggleOrderedList().run()">1. List</button>
            <button type="button" :class="toolbarClass(editor.isActive('blockquote'))"
                @click="editor.chain().focus().toggleBlockquote().run()">&ldquo;&rdquo;</button>
            <button type="button" :class="toolbarClass(editor.isActive('codeBlock'))"
                @click="editor.chain().focus().toggleCodeBlock().run()">{ }</button>
            <span class="mx-1 h-4 w-px bg-slate-200 dark:bg-slate-700"></span>
            <button type="button" :class="toolbarClass(false)" @click="editor.chain().focus().undo().run()">Undo</button>
            <button type="button" :class="toolbarClass(false)" @click="editor.chain().focus().redo().run()">Redo</button>
        </div>

        <EditorContent :editor="editor" class="max-w-none px-4 py-3" />
    </div>
</template>

<style scoped>
.doc-editor :deep(.doc-content) {
    min-height: 20rem;
    outline: none;
}

.doc-editor :deep(.doc-content p) {
    margin-bottom: 0.75rem;
    line-height: 1.7;
}

.doc-editor :deep(.doc-content h1) {
    margin: 1.25rem 0 0.5rem;
    font-size: 1.75rem;
    font-weight: 700;
}

.doc-editor :deep(.doc-content h2) {
    margin: 1rem 0 0.5rem;
    font-size: 1.35rem;
    font-weight: 700;
}

.doc-editor :deep(.doc-content h3) {
    margin: 0.75rem 0 0.5rem;
    font-size: 1.1rem;
    font-weight: 600;
}

.doc-editor :deep(.doc-content ul) {
    margin: 0.5rem 0 0.75rem 1.25rem;
    list-style: disc;
}

.doc-editor :deep(.doc-content ol) {
    margin: 0.5rem 0 0.75rem 1.25rem;
    list-style: decimal;
}

.doc-editor :deep(.doc-content blockquote) {
    margin: 0.75rem 0;
    border-left: 3px solid rgb(148 163 184);
    padding-left: 0.75rem;
    color: rgb(100 116 139);
}

.doc-editor :deep(.doc-content pre) {
    margin: 0.75rem 0;
    overflow-x: auto;
    border-radius: 0.5rem;
    background: rgb(15 23 42);
    padding: 0.75rem 1rem;
    color: rgb(226 232 240);
    font-size: 0.875rem;
}

.doc-editor :deep(.doc-content a) {
    color: rgb(37 99 235);
    text-decoration: underline;
}

.doc-editor :deep(.doc-content p.is-editor-empty:first-child::before) {
    content: attr(data-placeholder);
    float: left;
    height: 0;
    pointer-events: none;
    color: rgb(148 163 184);
}
</style>

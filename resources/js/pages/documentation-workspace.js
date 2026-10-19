import { createApp } from 'vue';
import DocumentationWorkspaceApp from '../components/documentation/DocumentationWorkspaceApp.vue';

const el = document.getElementById('documentation-app');

if (el) {
    let config = {};
    try {
        config = JSON.parse(el.dataset.config || '{}');
    } catch (e) {
        console.error('Failed to parse documentation config:', e);
        config = {};
    }

    createApp(DocumentationWorkspaceApp, {
        config,
        title: el.dataset.title || '',
    }).mount(el);
}

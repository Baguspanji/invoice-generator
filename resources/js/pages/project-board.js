import { createApp } from 'vue';
import ProjectBoardApp from '../components/projects/ProjectBoardApp.vue';

const el = document.getElementById('project-board-app');

if (el) {
    let config = {};
    try {
        config = JSON.parse(el.dataset.config || '{}');
    } catch (e) {
        console.error('Failed to parse project board config:', e);
        config = {};
    }

    createApp(ProjectBoardApp, {
        config,
        title: el.dataset.title || '',
    }).mount(el);
}

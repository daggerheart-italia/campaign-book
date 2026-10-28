import Alpine from 'alpinejs';

window.Alpine = Alpine;

if (document.querySelector('[x-data*="markdownEditor("]')) {
    const { registerMarkdownEditor } = await import('./markdown-editor');
    registerMarkdownEditor(Alpine);
}

Alpine.start();

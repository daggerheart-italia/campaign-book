import Alpine from 'alpinejs';
import { createCampaignSyncManager } from './campaign-sync-manager';

window.Alpine = Alpine;
window.createCampaignSyncManager = createCampaignSyncManager;

if (document.querySelector('[x-data*="markdownEditor("]')) {
    const { registerMarkdownEditor } = await import('./markdown-editor');
    registerMarkdownEditor(Alpine);
}

if (document.querySelector('[x-data*="parentPageSelect("]')) {
    const { registerParentPageSelect } = await import('./parent-page-select');
    registerParentPageSelect(Alpine);
}

Alpine.start();

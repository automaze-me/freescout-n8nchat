import { createChat } from './chat.bundle.es.js';

(function () {
    try {
        var cfg = window.N8nChatConfig;
        if (!cfg || !cfg.webhookUrl) {
            return;
        }
        // Force our per-agent+ticket session id (the widget reads this key on init).
        localStorage.setItem('n8n-chat/sessionId', cfg.sessionId);

        createChat(Object.assign({
            webhookUrl: cfg.webhookUrl,
            webhookConfig: { method: 'POST', headers: cfg.headers || {} },
            chatSessionKey: 'sessionId',
            metadata: cfg.metadata || {},
        }, cfg.options || {}));
    } catch (e) {
        console.error('[n8nchat] init failed', e);
    }
})();

# FreeScout ↔ n8n Chat module — design spec

**Date:** 2026-07-05
**Module:** `N8nChat` (alias `n8nchat`), repo `RhinoBag/freescout-n8nchat`
**Status:** Approved design → ready for implementation plan

## 1. Goal

Embed the [`@n8n/chat`](https://www.npmjs.com/package/@n8n/chat) widget into the FreeScout
agent UI so support agents can chat with an n8n-hosted AI assistant. The n8n webhook is
configurable in the FreeScout UI. Each message automatically carries FreeScout context —
especially **which ticket is currently open** — and conversation memory is scoped **per
agent per ticket**.

## 2. Decisions (locked)

| Topic | Decision |
|---|---|
| Session scope | **Per agent + ticket.** `sessionId = fs-user-{uid}-conv-{cid}` on a ticket; `fs-user-{uid}-general` elsewhere. |
| Context payload | **Compact summary** in `metadata` (identifiers + human-readable fields; n8n fetches deep detail via FreeScout API if needed). |
| Widget placement | **Global floating bubble** (`mode: 'window'`) on all pages. |
| Audience | **All logged-in users** (agents + admins). |
| Webhook auth | **Optional shared-secret header** (stored encrypted, sent as HTTP header). |
| Asset delivery | **Vendor the prebuilt `@n8n/chat` dist locally** in the module's `Public/`. |
| Naming | `N8nChat` / `n8nchat`. |

## 3. How `@n8n/chat` works (verified against source)

- Embed: `import { createChat } from '<bundle>.es.js'; createChat({ webhookUrl, ... })`.
- Session id persisted in `localStorage` under key **`n8n-chat/sessionId`**. If we set that
  key before `createChat()`, the widget uses **our** id → this is how we force per-agent+ticket.
- `metadata: {}` is attached to every webhook POST → our channel for FreeScout context.
- `loadPreviousSession: true` restores a session's history when the widget opens.
- Webhook payload: `{ action:"sendMessage", sessionId, chatInput, metadata }` (and
  `{ action:"loadPreviousSession", sessionId }` on open). `webhookConfig.headers` lets us
  add the shared-secret header.
- The n8n **Chat Trigger** node must allowlist the FreeScout origin for CORS.

## 4. FreeScout integration points (verified in core source)

- `layout.body_bottom` action (`resources/views/layouts/app.blade.php:278`) — global, end of
  `<body>`; where we render the widget config + loader.
- `<body data-auth_user_id>` is already emitted (`app.blade.php:43`).
- Open ticket detection: route `conversations.view` = `/conversation/{id}`
  (`routes/web.php:63`). In the hook we read `Route::currentRouteName()` and
  `request()->route('id')`.
- Settings page mechanism (`app/Http/Controllers/SettingsController.php`):
  - `settings.sections` filter — register the section.
  - `settings.view` filter (`'settings/'.$section`) — render our section view.
  - `settings.section_settings` filter — option keys + current values.
  - `settings.section_params` filter — `validator_rules`, `encrypt`, `safe_password`, `template_vars`.
  - Save flow supports encrypting a value (`encrypt`) and masking passwords (`safe_password`).

## 5. Architecture

```
Modules/N8nChat/
├── module.json                     alias n8nchat, provider registration
├── Config/config.php               option defaults (n8nchat.*)
├── Providers/N8nChatServiceProvider.php   registers all hooks + views
├── Resources/views/
│   ├── settings.blade.php          settings form (webhook URL, secret, branding)
│   └── widget.blade.php            server-rendered config + asset tags (body_bottom)
├── Public/
│   ├── js/loader.js                reads window.N8nChatConfig, seeds session, createChat()
│   ├── js/chat.bundle.es.js        vendored @n8n/chat (prebuilt)
│   └── css/style.css               vendored @n8n/chat styles
├── README.md                       admin setup + reference n8n workflow outline
└── Tests/                          feature test(s)
```

### 5.1 Options (`n8nchat.*`, defaults in Config/config.php)
- `enabled` (bool, default false)
- `webhook_url` (string, required when enabled, `url`)
- `shared_secret` (string, optional, **encrypted**, `safe_password`)
- `secret_header` (string, default `X-Freescout-Secret`)
- `title` (string, optional branding)
- `greeting` (string, optional initial bot message)
- `input_placeholder` (string, optional)

### 5.2 Settings section (ServiceProvider `registerSettings()`)
Adds section `n8nchat` (title "n8n Chat", icon `comment`) via the four settings filters
above. `webhook_url` → `required|url` when enabled; `shared_secret` encrypted + masked.
View `n8nchat::settings` renders the form using FreeScout's `settings[key]` field
convention and `partials/field_error`.

### 5.3 Widget injection (ServiceProvider `registerWidget()`)
`Eventy::addAction('layout.body_bottom', ...)` renders `n8nchat::widget` when
`enabled && webhook_url && Auth::check()`. The view computes, server-side:

- **agent** = `{ id, name, email, role }` from `Auth::user()`.
- **conversation** (only if `Route::currentRouteName() === 'conversations.view'`):
  load `Conversation::find(id)`, **verify access** (agent can view its mailbox); if OK,
  `{ id, number, subject, status, mailbox:{id,name}, customer:{name,email}, assignee }`.
  If no access or not on a ticket → omit.
- **sessionId** = `fs-user-{uid}-conv-{cid}` when a conversation is in context, else
  `fs-user-{uid}-general`.

It outputs:
```html
<link rel="stylesheet" href="{{ Module::getPublicPath(N8NCHAT_MODULE) }}/css/style.css">
<script>window.N8nChatConfig = @json($config);</script>
<script type="module" src="{{ Module::getPublicPath(N8NCHAT_MODULE) }}/js/loader.js"></script>
```
`$config = { webhookUrl, headers:{[secret_header]: shared_secret}?, sessionId, metadata:{agent, conversation}, options:{ mode:'window', loadPreviousSession:true, initialMessages, i18n } }`.

### 5.4 Loader (`Public/js/loader.js`)
```js
import { createChat } from './chat.bundle.es.js';
(function () {
  try {
    const cfg = window.N8nChatConfig; if (!cfg || !cfg.webhookUrl) return;
    localStorage.setItem('n8n-chat/sessionId', cfg.sessionId);
    createChat({
      webhookUrl: cfg.webhookUrl,
      webhookConfig: { method: 'POST', headers: cfg.headers || {} },
      mode: 'window',
      chatSessionKey: 'sessionId',
      loadPreviousSession: true,
      metadata: cfg.metadata,
      ...(cfg.options || {}),
    });
  } catch (e) { console.error('[n8nchat] init failed', e); }
})();
```
Note: `loader.js` uses a relative `import` of the vendored bundle; both are served from the
module's public path. The `import` requires the `<script type="module">` above.

## 6. Data flow (per message)

`POST {webhook_url}` →
```json
{ "action": "sendMessage",
  "sessionId": "fs-user-7-conv-123",
  "chatInput": "summarise this ticket",
  "metadata": {
    "agent": { "id": 7, "name": "Jane Doe", "email": "jane@x.com", "role": "user" },
    "conversation": { "id": 123, "number": 456, "subject": "Refund request",
                      "status": "active", "mailbox": {"id":2,"name":"Support"},
                      "customer": {"name":"Bob","email":"bob@x.com"}, "assignee": "Jane Doe" }
  } }
```
plus header `X-Freescout-Secret: <secret>` when configured. n8n keys memory on `sessionId`.

## 7. Out of scope (documented, not built)

The n8n workflow: Chat Trigger (CORS allowlist for FreeScout origin) → AI Agent + memory
node keyed on `sessionId` → optional FreeScout API calls (using `metadata.conversation.id`)
→ optional shared-secret header verification. README ships a reference outline only.

## 8. Security

- Conversation context gated by the agent's existing mailbox access — never leak a hidden ticket.
- `shared_secret` encrypted at rest; sent as a header. It is rendered client-side (all agents
  are trusted users), so it is a gate against **anonymous** internet abuse of the webhook, not
  a secret from agents. HTTPS assumed. Stated plainly in README.

## 9. Error handling

- Module disabled / no `webhook_url` → widget not rendered at all.
- `loader.js` no-ops if `window.N8nChatConfig` missing; `createChat` wrapped in try/catch so
  chat failures never break the FreeScout page.
- Settings: `webhook_url` validated as URL before save.

## 10. Testing

- **Automated (light, feature test in `Tests/`):**
  - Settings section `n8nchat` is registered (appears in `getSections()`).
  - With `enabled=true` + `webhook_url` set, `layout.body_bottom` render contains the webhook
    URL and `sessionId` `fs-user-{uid}-general` for a non-conversation page.
  - With `enabled=false`, render is empty.
- **Manual:** point `webhook_url` at a request-bin/n8n test; open a ticket → bubble appears →
  POST payload has `fs-user-X-conv-Y` + ticket metadata; dashboard → session `-general`, no
  ticket context; toggle disabled → widget gone.

## 11. Assumptions / to confirm during planning

- Exact conversation access-check API (e.g. `$user->can('view', $conversation)` /
  policy / `Conversation` + mailbox permission helper) — confirm in `app/Policies` / `User`.
- Exact status-to-text mapping (`Conversation::$statuses` / helper).
- Which `@n8n/chat` dist filenames to vendor (`chat.bundle.es.js`, `style.css`) and pinned version.
- Module scaffolding: create via `make new-module name=N8nChat` (adds repo + git) at the start
  of implementation. The spec then moves into the module repo's `docs/`.

# N8nChat — n8n AI chat for FreeScout

Embeds the [@n8n/chat](https://www.npmjs.com/package/@n8n/chat) widget into the FreeScout
agent UI. Every message carries FreeScout context (which ticket is open, the agent, the
customer). Conversation memory is scoped per agent per ticket.

## Setup (FreeScout side)

1. Modules → activate **N8nChat**.
2. Manage → Settings → **n8n Chat**:
   - **Enabled**: on
   - **Webhook URL**: your n8n Chat Trigger *production* URL (ends in `/chat`)
   - **Shared Secret** (optional): a token your workflow checks
   - Optional: window title, greeting, input placeholder

## Setup (n8n side)

1. Add a **Chat Trigger** node. In its options, set **Allowed Origins (CORS)** to your
   FreeScout domain.
2. If using a shared secret, read the configured header (default `X-Freescout-Secret`)
   in the workflow and reject mismatches.
3. Wire the trigger into an **AI Agent** node with a **memory** node keyed on the
   incoming `sessionId` (format `fs-user-<uid>-conv-<cid>` or `fs-user-<uid>-general`).
4. To use ticket context, read `metadata.conversation` from the trigger payload. For the
   full thread, call the FreeScout API using `metadata.conversation.id`.

### Payload shape

```json
{
  "action": "sendMessage",
  "sessionId": "fs-user-7-conv-123",
  "chatInput": "summarise this ticket",
  "metadata": {
    "agent": { "id": 7, "name": "Jane Doe", "email": "jane@x.com", "role": "user" },
    "conversation": {
      "id": 123, "number": 456, "subject": "Refund request", "status": "active",
      "mailbox": { "id": 2, "name": "Support" },
      "customer": { "name": "Bob", "email": "bob@x.com" }, "assignee": "Jane Doe"
    }
  }
}
```

## Security note

The shared secret is rendered client-side (agents are trusted users). It is a gate against
anonymous internet abuse of the webhook, **not** a secret from agents. Always use HTTPS.

## Updating the widget bundle

The `@n8n/chat` build is vendored in `Public/js/chat.bundle.es.js` + `Public/css/style.css`
(pinned in `Public/VERSION`). To update, re-download those two files from
`https://cdn.jsdelivr.net/npm/@n8n/chat@<version>/dist/` and bump `VERSION`.

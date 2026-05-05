# Real-World Examples and How-Tos

This guide is for taking the local Mautic MCP bundle from "it boots" to "an agent can use it for real work."

The current bundle is intentionally read-only. It exposes six tools:

- `search_contacts`
- `fetch_contact`
- `get_contact_timeline`
- `search_campaigns`
- `fetch_campaign`

## Choose the right transport

Use `stdio` when:

- your AI client runs on the same machine as this checkout
- you want the simplest local setup
- you do not need a shared remote endpoint

Use HTTP at `/mcp` when:

- you want a shared MCP endpoint for multiple clients
- your Mautic instance already runs behind DDEV, nginx, or Apache
- you want to authenticate the same way as the Mautic API

HTTP is opt-in. Enable it explicitly in local config before using the examples below.

## Local client setup with `stdio`

Most desktop MCP clients accept a server block shaped roughly like this:

```json
{
  "mcpServers": {
    "mautic-local": {
      "command": "php",
      "args": [
        "-d",
        "memory_limit=1024M",
        "/absolute/path/to/mautic/bin/console",
        "--env=dev",
        "mcp:server"
      ],
      "cwd": "/absolute/path/to/mautic"
    }
  }
}
```

Notes:

- If your client does not support `cwd`, keep the absolute path to `bin/console`.
- If your local instance depends on DDEV services, start them first with `ddev start`.
- `stdio` is the easiest way to connect a local coding agent to this repo.

### Codex

Codex supports project-scoped MCP config in `.codex/config.toml`, and this repo already includes one:

```toml
[mcp_servers.mautic]
command = "./bin/mcp-server"
startup_timeout_sec = 20
tool_timeout_sec = 120
required = true
```

The launcher script wraps `ddev exec php -d memory_limit=1024M bin/console --env=dev mcp:server`, which avoids the database host resolution failure you hit if you start `bin/console` directly from the host shell.

If you want to add it to your global Codex config instead of using the checked-in project config:

```bash
codex mcp add mautic -- ./bin/mcp-server
codex mcp list
```

## Remote or shared setup over HTTP

The HTTP endpoint is:

```text
https://<your-host>/mcp
```

Authentication uses the same security stack as Mautic's API firewall.

### Option 1: Basic Auth

Use this when Mautic API Basic Auth is enabled.

```bash
curl -i https://<your-host>/mcp \
  -u admin:password \
  -H 'Content-Type: application/json' \
  -d '{
    "jsonrpc": "2.0",
    "id": 1,
    "method": "initialize",
    "params": {
      "protocolVersion": "2025-06-18",
      "capabilities": {},
      "clientInfo": {
        "name": "manual-test",
        "version": "1.0.0"
      }
    }
  }'
```

### Option 2: OAuth2 bearer token

Use this when you want a dedicated machine credential instead of a username and password.

1. In Mautic, create an OAuth2 client under `Settings > API Credentials` or the `/s/credentials/new` UI.
2. Exchange the client id and secret for an access token:

```bash
curl -s https://<your-host>/oauth/v2/token \
  -d grant_type=client_credentials \
  -d client_id=<client-public-id> \
  -d client_secret=<client-secret>
```

3. Use the returned `access_token` on every MCP request:

```bash
curl -i https://<your-host>/mcp \
  -H 'Authorization: Bearer <access-token>' \
  -H 'Content-Type: application/json' \
  -d '{
    "jsonrpc": "2.0",
    "id": 1,
    "method": "initialize",
    "params": {
      "protocolVersion": "2025-06-18",
      "capabilities": {},
      "clientInfo": {
        "name": "manual-test",
        "version": "1.0.0"
      }
    }
  }'
```

## Manual MCP flow with `curl`

For HTTP clients, there are two things to keep straight:

- authentication proves who you are
- `Mcp-Session-Id` keeps the MCP conversation state

### 1. Initialize

Save the `Mcp-Session-Id` response header.

### 2. Send the initialized notification

```bash
curl -i https://<your-host>/mcp \
  -H 'Authorization: Bearer <access-token>' \
  -H 'Content-Type: application/json' \
  -H 'Mcp-Session-Id: <session-id>' \
  -d '{
    "jsonrpc": "2.0",
    "method": "notifications/initialized"
  }'
```

### 3. List tools

```bash
curl -i https://<your-host>/mcp \
  -H 'Authorization: Bearer <access-token>' \
  -H 'Content-Type: application/json' \
  -H 'Mcp-Session-Id: <session-id>' \
  -d '{
    "jsonrpc": "2.0",
    "id": 2,
    "method": "tools/list",
    "params": {}
  }'
```

### 4. Call a tool

```bash
curl -i https://<your-host>/mcp \
  -H 'Authorization: Bearer <access-token>' \
  -H 'Content-Type: application/json' \
  -H 'Mcp-Session-Id: <session-id>' \
  -d '{
    "jsonrpc": "2.0",
    "id": 3,
    "method": "tools/call",
    "params": {
      "name": "search_contacts",
      "arguments": {
        "query": "alice@example.com",
        "limit": 5,
        "page": 1
      }
    }
  }'
```

### 5. End the session when you are done

```bash
curl -i -X DELETE https://<your-host>/mcp \
  -H 'Authorization: Bearer <access-token>' \
  -H 'Mcp-Session-Id: <session-id>'
```

## Tool reference with copy-paste examples

### `search_contacts`

Find contacts by email, name, or another indexed field.

Arguments:

- `query` string, optional, default `''`
- `limit` int, optional, default `10`
- `page` int, optional, default `1`

Example:

```json
{
  "name": "search_contacts",
  "arguments": {
    "query": "alice@example.com",
    "limit": 5,
    "page": 1
  }
}
```

The response shape includes:

- `query`
- `page`
- `limit`
- `total`
- `items[]` with `id`, `primary_identifier`, `name`, `email`, `company`, `points`, `stage`, `date_identified`, `last_active`

### `fetch_contact`

Fetch the full normalized contact payload.

Arguments:

- `contactId` int, required

Example:

```json
{
  "name": "fetch_contact",
  "arguments": {
    "contactId": 123
  }
}
```

The response shape includes:

- summary fields from `search_contacts`
- `firstname`
- `lastname`
- `owner`
- `created_by`
- `date_added`
- `date_modified`
- `tags[]`
- `fields{}`

### `get_contact_timeline`

Inspect a contact's activity history.

Arguments:

- `contactId` int, required
- `search` string, optional, default `''`
- `limit` int, optional, default `25`, max `100`
- `page` int, optional, default `1`

Example:

```json
{
  "name": "get_contact_timeline",
  "arguments": {
    "contactId": 123,
    "search": "email",
    "limit": 10,
    "page": 1
  }
}
```

The response shape includes:

- `contact_id`
- `page`
- `limit`
- `total`
- `max_pages`
- `types`
- `events[]` with `id`, `event`, `type`, `label`, `occurred_at`, `contact_id`, `icon`, `details`

### `search_campaigns`

Find campaigns by name or keyword.

Arguments:

- `query` string, optional, default `''`
- `limit` int, optional, default `10`
- `page` int, optional, default `1`

Example:

```json
{
  "name": "search_campaigns",
  "arguments": {
    "query": "welcome",
    "limit": 5,
    "page": 1
  }
}
```

The response shape includes:

- `query`
- `page`
- `limit`
- `total`
- `items[]` with `id`, `name`, `description`, `category`, `publish_up`, `publish_down`, `date_added`, `date_modified`

### `fetch_campaign`

Fetch a campaign and optionally include its events.

Arguments:

- `campaignId` int, required
- `includeEvents` bool, optional, default `true`

Example:

```json
{
  "name": "fetch_campaign",
  "arguments": {
    "campaignId": 42,
    "includeEvents": true
  }
}
```

The response shape includes:

- top-level campaign details
- `segments[]`
- `forms[]`
- `events[]` when `includeEvents` is true

## Practical workflows

### Contact support triage

Use this when someone says "why did this person get this campaign?"

Suggested tool sequence:

1. `search_contacts` with the email address.
2. `fetch_contact` to confirm ownership, tags, stage, and field values.
3. `get_contact_timeline` with `search: "campaign"` or `search: "email"` to inspect recent automation activity.

Good agent prompt:

```text
Find the contact for alice@example.com, summarize their current profile, then inspect the last 10 timeline events related to email or campaign activity.
```

### Campaign review before launch

Use this when you want a quick AI-readable campaign summary.

Suggested tool sequence:

1. `search_campaigns` with part of the campaign name.
2. `fetch_campaign` with `includeEvents: true`.

Good agent prompt:

```text
Find the campaign named Welcome Series, summarize its segments, forms, publish window, and event flow, and call out anything that looks surprising.
```

### Sanity-check access permissions

This bundle respects Mautic entity permissions.

If the authenticated user can only view their own contacts or campaigns, searches are automatically scoped to items they created. That makes it a good fit for low-privilege service accounts.

### Fast manual smoke test after deploy

Use this exact sequence:

1. `initialize`
2. `notifications/initialized`
3. `tools/list`
4. `tools/call` for `search_contacts`
5. `tools/call` for `search_campaigns`

If those pass, both discovery and authentication are usually healthy.

## Troubleshooting

### `401` or `403`

Check:

- Mautic API is enabled
- Basic Auth or OAuth2 is enabled for the API
- the `/mcp` firewall entry exists in `app/config/security.php`
- the authenticated user has contact or campaign view permissions

### `tools/list` is empty or missing the Mautic tools

Check:

- `config/bundles_local.php` loads `Symfony\AI\McpBundle\McpBundle`
- the plugin is enabled and reloaded
- `config/config_local.php` scans `plugins/MauticMcpBundle`
- cache was cleared after changes

### The MCP endpoint works, but searches return nothing

Possible causes:

- you are authenticated as a user without `viewother` permissions, so results are scoped to items that user created
- the search string does not match Mautic's indexed fields the way you expect
- the dataset exists, but on a different Mautic environment than the one your client is hitting

### Browser testing is confusing

That is normal. `/mcp` is a JSON-RPC endpoint, not an HTML page. Use an MCP client, `curl`, or the MCP Inspector.

## Recommended first integrations

The easiest real-world rollout is:

1. connect a local coding or desktop agent over `stdio`
2. validate `search_contacts` and `fetch_campaign`
3. move to HTTP only when you need a shared endpoint or remote access

That keeps the operational surface small while the bundle is still read-only.

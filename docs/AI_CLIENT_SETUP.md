# AI Client Setup

This guide shows how to connect the local Mautic MCP server in this checkout to Codex, Claude Code, and Gemini CLI.

Use your own checkout root consistently in every config example below. In the snippets, replace `<project-root>` with the absolute path to your local Mautic checkout.

```text
<project-root>
```

## Prerequisites

1. Start the local stack:

```bash
ddev start
```

2. Create `bin/mcp-server` if it does not already exist:

```text
<project-root>/bin/mcp-server
```

It should contain:

```sh
#!/bin/sh

set -eu

SCRIPT_DIR=$(CDPATH= cd -- "$(dirname -- "$0")" && pwd)
PROJECT_ROOT=$(dirname "$SCRIPT_DIR")

cd "$PROJECT_ROOT"

exec ddev exec php -d memory_limit=1024M bin/console --env=dev mcp:server
```

This launcher is the recommended entry point for local MCP clients because it runs through `ddev exec`, which gives `bin/console` the DDEV-backed environment it needs.

3. Mark the launcher executable:

```bash
chmod +x bin/mcp-server
```

4. Create or update `config/bundles_local.php` so the vendor MCP bundle is registered:

```php
<?php

$bundles[] = new Symfony\AI\McpBundle\McpBundle();
```

5. Create or update `config/config_local.php` if you want to override local MCP settings or enable HTTP transport:

```php
<?php

$container->loadFromExtension('mcp', [
    'app'               => 'mautic',
    'version'           => '0.1.0',
    'description'       => 'Local Mautic MCP server',
    'instructions'      => 'Read-only access to Mautic contacts and campaigns.',
    'client_transports' => [
        'stdio' => true,
        'http'  => true,
    ],
    'http' => [
        'path'    => '/mcp',
        'session' => [
            'store'      => 'cache',
            'cache_pool' => 'cache.mcp.sessions',
            'prefix'     => 'mcp_',
            'ttl'        => 3600,
        ],
    ],
]);

$container->setParameter('mautic_mcp.allow_stdio_admin_fallback', true);
```

## Codex

Create or update `.codex/config.toml` in your checkout root.

It should look like this:

```toml
[mcp_servers.mautic]
command = "<project-root>/bin/mcp-server"
cwd = "<project-root>"
startup_timeout_sec = 20
tool_timeout_sec = 120
required = true
```

To use it:

1. Open `<project-root>` in Codex.
2. Trust the project if Codex prompts you.
3. Open a thread in this checkout and run `/mcp`.
4. Confirm you see a server named `mautic`.

Expected tools:

- `search_contacts`
- `fetch_contact`
- `get_contact_timeline`
- `search_campaigns`
- `fetch_campaign`

If Codex does not pick up the project config automatically, restart the Codex app after opening the project.

### Codex global config

If you prefer a global Codex config instead of the checked-in project config:

```bash
codex mcp add mautic -- <project-root>/bin/mcp-server
codex mcp list
```

## Claude Code

This section targets Claude Code. Claude Desktop currently centers its local MCP flow around desktop extensions rather than a repo-local MCP config, so the checkout-friendly setup here is for the coding client.

Claude Code supports local project MCP servers through `claude mcp add`.

For a project-local setup tied to this checkout:

```bash
claude mcp add --transport stdio mautic -- <project-root>/bin/mcp-server
```

That creates a checkout-local entry for Claude Code without needing to call `php` directly.

Useful follow-up commands:

```bash
claude mcp list
claude mcp get mautic
```

Inside Claude Code, run:

```text
/mcp
```

You should see the same tool set as Codex:

- `search_contacts`
- `fetch_contact`
- `get_contact_timeline`
- `search_campaigns`
- `fetch_campaign`

### Claude Code shared project config

If you want a versioned Claude Code config at the project root instead, create `.mcp.json` with:

```json
{
  "mcpServers": {
    "mautic": {
      "command": "<project-root>/bin/mcp-server",
      "args": [],
      "env": {}
    }
  }
}
```

Claude Code documents project-scoped MCP config in `.mcp.json`. Because this format is usually shared through version control, be careful about committing machine-specific absolute paths if your teammates use different checkout locations.

## Gemini CLI

Gemini CLI supports MCP servers through `settings.json` or the `gemini mcp` command.

For a project-local setup in this checkout:

```bash
gemini mcp add -s project mautic <project-root>/bin/mcp-server
```

That writes the MCP entry to `.gemini/settings.json` in the project root.

You can inspect it with:

```bash
gemini mcp list
```

The equivalent project config looks like this:

```json
{
  "mcpServers": {
    "mautic": {
      "command": "<project-root>/bin/mcp-server",
      "cwd": "<project-root>",
      "timeout": 30000,
      "trust": false
    }
  }
}
```

Inside Gemini CLI, run:

```text
/mcp
```

You should see the same tool set:

- `search_contacts`
- `fetch_contact`
- `get_contact_timeline`
- `search_campaigns`
- `fetch_campaign`

### Gemini global config

If you want the server available across all projects instead:

```bash
gemini mcp add -s user mautic <project-root>/bin/mcp-server
```

Gemini CLI stores user-scoped settings in `~/.gemini/settings.json`.

## Smoke Test

After the client is connected, use a prompt like:

```text
Use the `mautic` MCP server and list available tools.
```

Expected result:

- `search_contacts`
- `fetch_contact`
- `get_contact_timeline`
- `search_campaigns`
- `fetch_campaign`

## Troubleshooting

If discovery fails, check these in order:

1. Validate the launcher manually:

```bash
<project-root>/bin/mcp-server
```

2. Confirm the Mautic console command is available through DDEV:

```bash
ddev exec php -d memory_limit=1024M bin/console list mcp
```

3. Confirm the local HTTP route stays aligned at `/mcp` in `config/config_local.php` if you enable HTTP transport locally.

4. Confirm the `/mcp` firewall and access-control entries exist in `app/config/security.php`.

For manual HTTP validation and JSON-RPC examples, see [REAL_WORLD_EXAMPLES.md](REAL_WORLD_EXAMPLES.md).

# MauticMcpBundle

## INTRODUCTION

`MauticMcpBundle` is a read-only Model Context Protocol (MCP) plugin for Mautic. It exposes a small, intentional tool surface for AI-assisted development, testing, support, and campaign debugging workflows without turning the Mautic application into a broad data dump.

The bundle builds on `symfony/mcp-bundle` for MCP transport and tool discovery, while this plugin owns the Mautic-specific permission checks, read services, and response normalization.

Current tools:

- `search_contacts`
- `fetch_contact`
- `get_contact_timeline`
- `search_campaigns`
- `fetch_campaign`

This phase is intentionally read-only.

## INSTALLATION

### Requirements

- PHP `^8.2`
- `mautic/core-lib ^7.0`
- `symfony/mcp-bundle ^0.6`
- A local Mautic checkout with DDEV available for the recommended `stdio` launcher flow

### Package metadata

- Package: `shinde-rahul/plugin-mautic-mcp`
- Install directory: `MauticMcpBundle`

### Local checkout setup

1. Ensure the vendor MCP bundle is available in this checkout.
2. Create or update `config/bundles_local.php` so it registers the vendor MCP bundle:

```php
<?php

$bundles[] = new Symfony\AI\McpBundle\McpBundle();
```

3. Create or update `bin/mcp-server` with the local `stdio` launcher used by AI clients:

```sh
#!/bin/sh

set -eu

SCRIPT_DIR=$(CDPATH= cd -- "$(dirname -- "$0")" && pwd)
PROJECT_ROOT=$(dirname "$SCRIPT_DIR")

cd "$PROJECT_ROOT"

exec ddev exec php -d memory_limit=1024M bin/console --env=dev mcp:server
```

4. Mark the launcher executable:

```bash
chmod +x bin/mcp-server
```

5. Reload plugins and clear cache:

```bash
php -d memory_limit=1024M bin/console --env=dev mautic:plugins:reload -n
php -d memory_limit=1024M bin/console --env=dev cache:clear
```

## CONFIGURATION

### Default behavior

The plugin ships with a public `/mcp` route and defaults to read-only MCP access for Mautic contacts and campaigns. `stdio` is the primary local-client transport. HTTP can be enabled explicitly for shared or remote MCP access.

### Local MCP settings

Create or update `config/config_local.php` if you want to override the default MCP settings or enable HTTP transport locally:

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

### Security

Add the MCP firewall in `app/config/security.php`:

```php
'mcp' => [
    'pattern'            => '^/mcp(?:/|$)',
    'fos_oauth'          => true,
    'stateless'          => true,
    'http_basic'         => true,
    'provider'           => 'user_provider',
    'entry_point'        => 'fos_oauth_server.security.entry_point',
],
```

Add the MCP access-control rule before the existing `^/api` rule:

```php
['path' => '^/mcp(?:/|$)', 'roles' => AuthenticatedVoter::IS_AUTHENTICATED_FULLY],
```

### Client setup

For client-specific setup instructions, see:

- [docs/AI_CLIENT_SETUP.md](docs/AI_CLIENT_SETUP.md) for Codex, Claude Code, and Gemini CLI
- [docs/REAL_WORLD_EXAMPLES.md](docs/REAL_WORLD_EXAMPLES.md) for manual JSON-RPC, HTTP transport, and task-oriented recipes

## USAGE

### Local validation

Validate the `stdio` launcher:

```bash
./bin/mcp-server
```

Confirm the console command is available through DDEV:

```bash
ddev exec php -d memory_limit=1024M bin/console list mcp
```

### HTTP validation

If you enable HTTP transport, initialize the MCP endpoint at `/mcp` and then list tools using the returned `Mcp-Session-Id`. Full request examples are documented in [docs/REAL_WORLD_EXAMPLES.md](docs/REAL_WORLD_EXAMPLES.md).

Expected tool set:

- `search_contacts`
- `fetch_contact`
- `get_contact_timeline`
- `search_campaigns`
- `fetch_campaign`

## ARCHITECTURE

This bundle keeps the transport adapter thin and pushes real work into application, domain, presentation, and security layers.

For the design rationale and layer boundaries, see [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md).

## NOTES

- The `/mcp` route comes from the plugin bundle config.
- Mautic still requires manual firewall and access-control entries under `app/config`.
- HTTP transport is opt-in for local setups.
- Create `bin/mcp-server`, `config/bundles_local.php`, and `config/config_local.php` as needed for your checkout instead of assuming they already exist.
- Tool payloads should be shaped through the normalizers in `Presentation/Normalizer`.
- Timeline access is isolated behind `TimelineProviderInterface` because timeline behavior can vary across branches.

## AUTHOR

- Rahul Shinde

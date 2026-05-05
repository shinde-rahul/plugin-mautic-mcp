# MauticMcpBundle

This plugin is the first read-only MCP skeleton for exposing a small set of Mautic contact and campaign tools from inside the Mautic app.

## Current tools

- `search_contacts`
- `fetch_contact`
- `get_contact_timeline`
- `search_campaigns`
- `fetch_campaign`

## Setup

1. Install dependencies `composer require symfony/mcp-bundle`

2. Register the vendor bundle in `<path-to-projectroot>/config/bundles_local.php`

```php
<?php

$bundles[] = new Symfony\AI\McpBundle\McpBundle();
```

3. Optional: override the default MCP settings in `config/config_local.php`. The plugin already prepends sensible defaults for discovery, `stdio`, the `/mcp` path, and cache-backed sessions. HTTP is opt-in, so enable it here only when you need a shared endpoint:
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
            'prefix' => 'mcp_',
            'ttl'    => 3600,
        ],
    ],
]);
```

4. Add the MCP firewall in `app/config/security.php`. Add it next to the existing `api` firewall:

```php
'mcp' => [
    'pattern'     => '^/mcp(?:/|$)',
    'fos_oauth'   => true,
    'stateless'   => true,
    'http_basic'  => true,
    'provider'    => 'user_provider',
    'entry_point' => 'fos_oauth_server.security.entry_point',
],
```

5. Add the MCP access-control rule in the same file before the existing `^/api` rule:

```php
['path' => '^/mcp(?:/|$)', 'roles' => AuthenticatedVoter::IS_AUTHENTICATED_FULLY],
```
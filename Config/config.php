<?php

return [
    'name'        => 'Mautic MCP Bundle',
    'description' => 'Read-only MCP tools for Mautic data access.',
    'version'     => '0.1.0',
    'author'      => 'Rahul Shinde',
    'routes'      => [
        'public' => [
            'mautic_mcp_http_endpoint' => [
                'path'       => '/mcp',
                'controller' => 'mcp.server.controller::handle',
                'method'     => ['GET', 'POST', 'DELETE', 'OPTIONS'],
            ],
        ],
    ],
];

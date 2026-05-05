# Architecture

Mautic MCP Plugin provides an MCP server inside Mautic, exposing controlled tools that AI clients can use for development, testing, support, and campaign debugging workflows.

The initial version starts with a conservative set of read-only tools around contacts and campaigns. This allows the community to review the direction, tool design, permission handling, output shape, and transport choices before the plugin expands into additional Mautic domains.

## Goals

- Provide useful MCP tools for Mautic developers, testers, maintainers, and support workflows.
- Keep the first version read-only and safe by default.
- Reuse Mautic’s existing models, services, and permission system.
- Return normalized, predictable, AI-friendly data instead of raw entities.
- Keep MCP tool classes thin and easy to review.
- Prepare the structure for future tools without turning the plugin into a flat list of unrelated classes.

## Current Scope

The first version exposes read-only tools around contacts and campaigns.

Current tools:

- `search_contacts`
- `fetch_contact`
- `get_contact_timeline`
- `search_campaigns`
- `fetch_campaign`

These tools are intentionally conservative. They do not create, update, delete, trigger, publish, send, or mutate Mautic data.

## Design Principle

MCP tools should be thin.

A tool class should mainly:

1. Accept MCP input.
2. Validate or normalize simple arguments.
3. Call an application service.
4. Return a structured response.

Business logic, Mautic access, permission checks, and output shaping should live outside the tool class.

In short:

```text
MCP Tool
  -> Application Service
    -> Permission Check
    -> Mautic Model / Service
    -> Normalizer
    -> Structured MCP Response
```

## Request flow

The normal path through the bundle should look like this:

1. An MCP request reaches a tool class under `MCP/Tool`.
2. The tool performs any shared execution bootstrap.
3. The tool delegates to an `Application` read service.
4. The application service checks permissions through `Security`.
5. The application service fetches Mautic data directly or through `Domain` contracts.
6. The result is shaped through `Presentation` normalizers.
7. The tool returns the final array payload to the vendor MCP layer.

This keeps each layer small and makes reviews much easier.≈
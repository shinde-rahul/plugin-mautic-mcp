<?php

declare(strict_types=1);

namespace MauticPlugin\MauticMcpBundle\Mcp\Tool\Contact;

use Mcp\Capability\Attribute\McpTool;
use MauticPlugin\MauticMcpBundle\Application\Contact\ContactReadService;
use MauticPlugin\MauticMcpBundle\Mcp\Tool\AbstractMcpTool;

#[McpTool(name: 'mautic_fetch_contact')]
final class FetchContactTool extends AbstractMcpTool
{
    public function __construct(
        private ContactReadService $contactReadService,
    ) {
    }

    /**
     * Fetch normalized details for a single contact.
     */
    public function __invoke(int $contactId): array
    {
        $this->bootstrapExecution();

        return $this->contactReadService->fetch($contactId);
    }
}

<?php

declare(strict_types=1);

namespace MauticPlugin\MauticMcpBundle\Mcp\Tool\Contact;

use Mcp\Capability\Attribute\McpTool;
use MauticPlugin\MauticMcpBundle\Application\Contact\TimelineReadService;
use MauticPlugin\MauticMcpBundle\Mcp\Tool\AbstractMcpTool;

#[McpTool(name: 'mautic_get_contact_timeline')]
final class GetContactTimelineTool extends AbstractMcpTool
{
    public function __construct(
        private TimelineReadService $timelineReadService,
    ) {
    }

    /**
     * Fetch normalized timeline activity for a single contact.
     */
    public function __invoke(int $contactId, string $search = '', int $limit = 25, int $page = 1): array
    {
        $this->bootstrapExecution();

        return $this->timelineReadService->getTimeline($contactId, $search, $limit, $page);
    }
}

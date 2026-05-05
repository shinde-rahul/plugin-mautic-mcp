<?php

declare(strict_types=1);

namespace MauticPlugin\MauticMcpBundle\Mcp\Tool\Campaign;

use Mcp\Capability\Attribute\McpTool;
use MauticPlugin\MauticMcpBundle\Application\Campaign\CampaignReadService;
use MauticPlugin\MauticMcpBundle\Mcp\Tool\AbstractMcpTool;

#[McpTool(name: 'mautic_fetch_campaign')]
final class FetchCampaignTool extends AbstractMcpTool
{
    public function __construct(
        private CampaignReadService $campaignReadService,
    ) {
    }

    /**
     * Fetch normalized details for a single campaign.
     */
    public function __invoke(int $campaignId, bool $includeEvents = true): array
    {
        $this->bootstrapExecution();

        return $this->campaignReadService->fetch($campaignId, $includeEvents);
    }
}

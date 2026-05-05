<?php

declare(strict_types=1);

namespace MauticPlugin\MauticMcpBundle\Mcp\Tool\Campaign;

use Mcp\Capability\Attribute\McpTool;
use MauticPlugin\MauticMcpBundle\Application\Campaign\CampaignReadService;
use MauticPlugin\MauticMcpBundle\Application\Campaign\CampaignSearchQuery;
use MauticPlugin\MauticMcpBundle\Mcp\Tool\AbstractMcpTool;

#[McpTool(name: 'mautic_search_campaigns')]
final class SearchCampaignsTool extends AbstractMcpTool
{
    public function __construct(
        private CampaignReadService $campaignReadService,
    ) {
    }

    /**
     * Search campaigns by name or keyword.
     */
    public function __invoke(string $query = '', int $limit = 10, int $page = 1): array
    {
        $this->bootstrapExecution();

        return $this->campaignReadService->search(new CampaignSearchQuery($query, $limit, $page));
    }
}

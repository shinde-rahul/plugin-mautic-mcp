<?php

declare(strict_types=1);

namespace MauticPlugin\MauticMcpBundle\Tests\Unit\Mcp\Tool\Campaign;

use Mautic\UserBundle\Model\UserModel;
use Mautic\UserBundle\Security\UserTokenSetter;
use MauticPlugin\MauticMcpBundle\Application\Campaign\CampaignReadService;
use MauticPlugin\MauticMcpBundle\Mcp\Tool\Campaign\FetchCampaignTool;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

final class FetchCampaignToolTest extends TestCase
{
    public function testInvokeDelegatesToReadService(): void
    {
        $expected = [
            'id'   => 7,
            'name' => 'Nurture Flow',
        ];

        $service = $this->createMock(CampaignReadService::class);
        $service->expects($this->once())
            ->method('fetch')
            ->with(7, false)
            ->willReturn($expected);

        $tool = new FetchCampaignTool($service);
        $requestStack = new RequestStack();
        $requestStack->push(new Request());
        $tool->setExecutionContextDependencies(
            $requestStack,
            $this->createMock(TokenStorageInterface::class),
            new UserTokenSetter(
                $this->createMock(UserModel::class),
                $this->createMock(TokenStorageInterface::class),
            ),
            $this->createMock(UserModel::class),
            $this->createMock(KernelInterface::class),
            false,
        );

        $this->assertSame($expected, $tool(7, false));
    }
}

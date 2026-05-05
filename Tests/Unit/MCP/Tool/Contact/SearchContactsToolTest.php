<?php

declare(strict_types=1);

namespace MauticPlugin\MauticMcpBundle\Tests\Unit\Mcp\Tool\Contact;

use Mautic\UserBundle\Model\UserModel;
use Mautic\UserBundle\Security\UserTokenSetter;
use MauticPlugin\MauticMcpBundle\Application\Contact\ContactReadService;
use MauticPlugin\MauticMcpBundle\Application\Contact\ContactSearchQuery;
use MauticPlugin\MauticMcpBundle\Mcp\Tool\Contact\SearchContactsTool;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

final class SearchContactsToolTest extends TestCase
{
    public function testInvokeDelegatesToReadService(): void
    {
        $expected = [
            'items' => [
                ['id' => 42, 'email' => 'ada@example.com'],
            ],
        ];

        $service = $this->createMock(ContactReadService::class);
        $service->expects($this->once())
            ->method('search')
            ->with($this->callback(function (ContactSearchQuery $query): bool {
                return 'ada' === $query->getSearch()
                    && 15 === $query->getLimit()
                    && 2 === $query->getPage();
            }))
            ->willReturn($expected);

        $tool = new SearchContactsTool($service);
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

        $this->assertSame($expected, $tool('ada', 15, 2));
    }
}

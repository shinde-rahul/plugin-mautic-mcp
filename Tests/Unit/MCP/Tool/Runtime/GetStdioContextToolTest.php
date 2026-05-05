<?php

declare(strict_types=1);

namespace MauticPlugin\MauticMcpBundle\Tests\Unit\Mcp\Tool\Runtime;

use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Model\UserModel;
use Mautic\UserBundle\Security\UserTokenSetter;
use MauticPlugin\MauticMcpBundle\Mcp\Tool\Runtime\GetStdioContextTool;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

final class GetStdioContextToolTest extends TestCase
{
    public function testInvokeRejectsHttpExecution(): void
    {
        $requestStack = new RequestStack();
        $requestStack->push(new Request());

        $tool = new GetStdioContextTool(
            $requestStack,
            $this->createMock(TokenStorageInterface::class),
            $this->createMock(KernelInterface::class),
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('available only during stdio execution');

        $tool();
    }

    public function testInvokeReturnsNormalizedStdioContext(): void
    {
        $requestStack = new RequestStack();

        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn(7);
        $user->method('getUsername')->willReturn('admin');
        $user->method('getEmail')->willReturn('admin@example.com');
        $user->method('getFirstName')->willReturn('System');
        $user->method('getLastName')->willReturn('Admin');

        $token = $this->createMock(TokenInterface::class);
        $token->expects($this->exactly(2))
            ->method('getUser')
            ->willReturn($user);

        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage->expects($this->exactly(2))
            ->method('getToken')
            ->willReturn($token);

        $kernel = $this->createMock(KernelInterface::class);
        $kernel->expects($this->once())->method('getEnvironment')->willReturn('dev');
        $kernel->expects($this->once())->method('isDebug')->willReturn(true);

        $tool = new GetStdioContextTool($requestStack, $tokenStorage, $kernel);
        $tool->setExecutionContextDependencies(
            $requestStack,
            $tokenStorage,
            new UserTokenSetter(
                $this->createMock(UserModel::class),
                $this->createMock(TokenStorageInterface::class),
            ),
            $this->createMock(UserModel::class),
            $kernel,
            false,
        );

        $this->assertSame(
            [
                'transport'   => 'stdio',
                'environment' => 'dev',
                'debug'       => true,
                'user'        => [
                    'id'         => 7,
                    'username'   => 'admin',
                    'email'      => 'admin@example.com',
                    'first_name' => 'System',
                    'last_name'  => 'Admin',
                ],
            ],
            $tool()
        );
    }
}

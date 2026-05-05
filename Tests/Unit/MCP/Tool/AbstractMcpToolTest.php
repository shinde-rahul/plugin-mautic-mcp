<?php

declare(strict_types=1);

namespace MauticPlugin\MauticMcpBundle\Tests\Unit\Mcp\Tool;

use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Model\UserModel;
use Mautic\UserBundle\Security\UserTokenSetter;
use MauticPlugin\MauticMcpBundle\Mcp\Tool\AbstractMcpTool;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

final class AbstractMcpToolTest extends TestCase
{
    public function testBootstrapKeepsAuthenticatedHttpRequestsUntouched(): void
    {
        $requestStack = new RequestStack();
        $requestStack->push(new Request());

        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage->expects($this->never())->method('getToken');

        $userTokenSetter = new UserTokenSetter(
            $this->createMock(UserModel::class),
            $this->createMock(TokenStorageInterface::class),
        );

        $userModel = $this->createMock(UserModel::class);
        $userModel->expects($this->never())->method('getSystemAdministrator');

        $tool = new class extends AbstractMcpTool {
            public function runBootstrap(): void
            {
                $this->bootstrapExecution();
            }
        };
        $kernel = $this->createMock(KernelInterface::class);
        $tool->setExecutionContextDependencies($requestStack, $tokenStorage, $userTokenSetter, $userModel, $kernel, false);

        $tool->runBootstrap();
    }

    public function testBootstrapRejectsStdioRequestsWithoutAuthenticatedUserWhenFallbackIsDisabled(): void
    {
        $requestStack = new RequestStack();

        $token = $this->createMock(TokenInterface::class);
        $token->expects($this->once())
            ->method('getUser')
            ->willReturn(null);

        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn($token);

        $userTokenSetter = new UserTokenSetter(
            $this->createMock(UserModel::class),
            $this->createMock(TokenStorageInterface::class),
        );

        $userModel = $this->createMock(UserModel::class);
        $userModel->expects($this->never())->method('getSystemAdministrator');

        $kernel = $this->createMock(KernelInterface::class);
        $kernel->expects($this->never())->method('isDebug');

        $tool = new class extends AbstractMcpTool {
            public function runBootstrap(): void
            {
                $this->bootstrapExecution();
            }
        };
        $tool->setExecutionContextDependencies($requestStack, $tokenStorage, $userTokenSetter, $userModel, $kernel, false);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('System administrator fallback is disabled by default');

        $tool->runBootstrap();
    }

    public function testBootstrapRejectsFallbackOutsideDebugUsage(): void
    {
        $requestStack = new RequestStack();

        $token = $this->createMock(TokenInterface::class);
        $token->expects($this->once())
            ->method('getUser')
            ->willReturn(null);

        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn($token);

        $userTokenSetter = new UserTokenSetter(
            $this->createMock(UserModel::class),
            $this->createMock(TokenStorageInterface::class),
        );

        $userModel = $this->createMock(UserModel::class);
        $userModel->expects($this->never())->method('getSystemAdministrator');

        $kernel = $this->createMock(KernelInterface::class);
        $kernel->expects($this->once())->method('isDebug')->willReturn(false);
        $kernel->expects($this->once())->method('getEnvironment')->willReturn('prod');

        $tool = new class extends AbstractMcpTool {
            public function runBootstrap(): void
            {
                $this->bootstrapExecution();
            }
        };
        $tool->setExecutionContextDependencies($requestStack, $tokenStorage, $userTokenSetter, $userModel, $kernel, true);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('only allowed in local/dev usage');

        $tool->runBootstrap();
    }

    public function testBootstrapSeedsSystemAdministratorForExplicitDebugFallback(): void
    {
        $requestStack = new RequestStack();

        $token = $this->createMock(TokenInterface::class);
        $token->expects($this->once())
            ->method('getUser')
            ->willReturn(null);

        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn($token);

        $systemAdministrator = $this->createMock(User::class);
        $systemAdministrator->expects($this->once())
            ->method('getId')
            ->willReturn(7);

        $bootstrapUserModel = $this->createMock(UserModel::class);
        $bootstrapUserModel->expects($this->once())
            ->method('getSystemAdministrator')
            ->willReturn($systemAdministrator);

        $resolvedUser = $this->createMock(User::class);

        $setterUserModel = $this->createMock(UserModel::class);
        $setterUserModel->expects($this->once())
            ->method('getEntity')
            ->with(7)
            ->willReturn($resolvedUser);

        $setterTokenStorage = $this->createMock(TokenStorageInterface::class);
        $setterTokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn(null);
        $setterTokenStorage->expects($this->once())
            ->method('setToken')
            ->with($this->callback(
                fn (TokenInterface $token): bool => $resolvedUser === $token->getUser()
            ));

        $userTokenSetter = new UserTokenSetter($setterUserModel, $setterTokenStorage);

        $kernel = $this->createMock(KernelInterface::class);
        $kernel->expects($this->once())->method('isDebug')->willReturn(true);

        $tool = new class extends AbstractMcpTool {
            public function runBootstrap(): void
            {
                $this->bootstrapExecution();
            }
        };
        $tool->setExecutionContextDependencies(
            $requestStack,
            $tokenStorage,
            $userTokenSetter,
            $bootstrapUserModel,
            $kernel,
            true,
        );

        $tool->runBootstrap();
    }

    public function testBootstrapDoesNothingWhenTokenAlreadyHasUser(): void
    {
        $requestStack = new RequestStack();

        $token = $this->createMock(TokenInterface::class);
        $token->expects($this->once())
            ->method('getUser')
            ->willReturn(new User());

        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn($token);

        $userTokenSetter = new UserTokenSetter(
            $this->createMock(UserModel::class),
            $this->createMock(TokenStorageInterface::class),
        );

        $userModel = $this->createMock(UserModel::class);
        $userModel->expects($this->never())->method('getSystemAdministrator');

        $tool = new class extends AbstractMcpTool {
            public function runBootstrap(): void
            {
                $this->bootstrapExecution();
            }
        };
        $kernel = $this->createMock(KernelInterface::class);
        $tool->setExecutionContextDependencies($requestStack, $tokenStorage, $userTokenSetter, $userModel, $kernel, false);

        $tool->runBootstrap();
    }
}

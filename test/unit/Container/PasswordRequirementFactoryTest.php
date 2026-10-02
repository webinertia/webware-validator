<?php

declare(strict_types=1);

/**
 * This file is part of the Webware Validator package.
 *
 * Copyright (c) 2026 Joey Smith <jsmith@webinertia.net>
 * and contributors.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WebwareTest\Validator\Container;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Webware\Validator\Container\PasswordRequirementFactory;
use Webware\Validator\PasswordRequirement;

#[CoversClass(PasswordRequirementFactory::class)]
#[CoversMethod(PasswordRequirementFactory::class, '__invoke')]
final class PasswordRequirementFactoryTest extends TestCase
{
    #[Test]
    public function buildsADefaultValidatorWhenNothingIsConfigured(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $container->method('has')
            ->willReturn(false);

        $validator = (new PasswordRequirementFactory())(
            container    : $container,
            requestedName: PasswordRequirement::class,
        );

        self::assertTrue($validator->isValid(''));
    }

    #[Test]
    public function buildsAValidatorFromTheConfiguredDefaults(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $container->method('has')
            ->willReturn(true);
        $container->method('get')
            ->willReturn([PasswordRequirement::class => ['length' => 4]]);

        $validator = (new PasswordRequirementFactory())(
            container    : $container,
            requestedName: PasswordRequirement::class,
        );

        self::assertTrue($validator->isValid('abcd'));
        self::assertFalse($validator->isValid('abc'));
    }

    #[Test]
    public function letsCallSiteOptionsOverrideTheConfiguredDefaults(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $container->method('has')
            ->willReturn(true);
        $container->method('get')
            ->willReturn([PasswordRequirement::class => ['length' => 8]]);

        $validator = (new PasswordRequirementFactory())(
            container    : $container,
            requestedName: PasswordRequirement::class,
            options      : ['length' => 4],
        );

        self::assertTrue($validator->isValid('abcd'));
        self::assertFalse($validator->isValid('abc'));
    }

    #[Test]
    public function usesCallSiteOptionsWhenNoConfigServiceIsRegistered(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $container->method('has')
            ->willReturn(false);

        $validator = (new PasswordRequirementFactory())(
            container    : $container,
            requestedName: PasswordRequirement::class,
            options      : ['length' => 4],
        );

        self::assertTrue($validator->isValid('abcd'));
        self::assertFalse($validator->isValid('abc'));
    }
}

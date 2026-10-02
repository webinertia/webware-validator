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

namespace WebwareTest\Validator;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Webware\Validator\ConfigProvider;
use Webware\Validator\Container\PasswordRequirementFactory;
use Webware\Validator\PasswordRequirement;

#[CoversClass(ConfigProvider::class)]
#[CoversMethod(ConfigProvider::class, '__invoke')]
#[CoversMethod(ConfigProvider::class, 'getDependencies')]
final class ConfigProviderTest extends TestCase
{
    #[Test]
    public function exposesItsDependencyFactoryMapPublicly(): void
    {
        $expected = [
            'factories' => [],
        ];

        self::assertSame($expected, new ConfigProvider()->getDependencies());
    }

    #[Test]
    public function providesThePasswordRequirementWiringAndThePackageDefaults(): void
    {
        $expected = [
            'dependencies'             => [
                'factories' => [],
            ],
            'validators'               => [
                'factories' => [
                    PasswordRequirement::class => PasswordRequirementFactory::class,
                ],
            ],
            PasswordRequirement::class => [
                'length'  => 8,
                'upper'   => 1,
                'lower'   => 1,
                'digit'   => 1,
                'special' => 1,
            ],
        ];

        self::assertSame($expected, new ConfigProvider()->__invoke());
    }
}

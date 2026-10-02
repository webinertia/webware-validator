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

namespace Webware\Validator\Container;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Webware\Validator\PasswordRequirement;

/**
 * Builds a {@see PasswordRequirement} from the package's configured defaults, overridden by
 * the call-site options.
 *
 * @internal
 */
final class PasswordRequirementFactory
{
    /**
     * @param array<string, mixed>|null $options
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(
        ContainerInterface $container,
        string $requestedName,
        ?array $options = null,
    ): PasswordRequirement {
        /** @var array<string, mixed> $config */
        $config = $container->has(id: 'config') ? $container->get(id: 'config') : [];

        /** @var array<string, mixed> $configured */
        $configured = $config[$requestedName] ?? [];

        return new PasswordRequirement(options: [
            ...$configured,
            ...($options ?? []),
        ]);
    }
}

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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Webware\Validator\PasswordRequirement;

#[CoversClass(PasswordRequirement::class)]
#[CoversMethod(PasswordRequirement::class, '__construct')]
#[CoversMethod(PasswordRequirement::class, 'isValid')]
final class PasswordRequirementTest extends TestCase
{
    /**
     * @return array<string, array{options: array<string, int>, rejected: string, accepted: string}>
     */
    public static function configuredRuleProvider(): array
    {
        return [
            'length'  => [
                'options'  => ['length' => 4],
                'rejected' => 'abc',
                'accepted' => 'abcd',
            ],
            'upper'   => [
                'options'  => ['upper' => 1],
                'rejected' => 'abc1',
                'accepted' => 'Abc1',
            ],
            'lower'   => [
                'options'  => ['lower' => 1],
                'rejected' => 'ABC1',
                'accepted' => 'ABc1',
            ],
            'digit'   => [
                'options'  => ['digit' => 1],
                'rejected' => 'Abcd',
                'accepted' => 'Abcd1',
            ],
            'special' => [
                'options'  => ['special' => 1],
                'rejected' => 'Abcd1',
                'accepted' => 'Abcd1!',
            ],
        ];
    }

    #[Test]
    public function acceptsAPasswordThatMeetsEveryRule(): void
    {
        $validator = new PasswordRequirement(options: [
            'length'  => 8,
            'upper'   => 1,
            'lower'   => 1,
            'digit'   => 1,
            'special' => 1,
        ]);

        self::assertTrue($validator->isValid('Passw0rd!'));
        self::assertSame([], $validator->getMessages());
    }

    #[Test]
    public function castsANonStringValueToAString(): void
    {
        $validator = new PasswordRequirement(options: ['length' => 3, 'digit' => 3]);

        self::assertTrue($validator->isValid(123));
        self::assertFalse($validator->isValid(12));
        self::assertFalse($validator->isValid(null));
    }

    #[Test]
    public function castsStringOptionValuesToIntegers(): void
    {
        $validator = new PasswordRequirement(options: ['length' => '4']);

        self::assertTrue($validator->isValid('abcd'));
        self::assertFalse($validator->isValid('abc'));
    }

    #[Test]
    public function countsMultibyteCharactersAsCharacters(): void
    {
        $validator = new PasswordRequirement(options: ['length' => 4]);

        self::assertTrue($validator->isValid('éàçü'));
        self::assertSame([], $validator->getMessages());

        self::assertFalse($validator->isValid('éàç'));
        self::assertArrayHasKey(PasswordRequirement::INVALID_LENGTH_COUNT, $validator->getMessages());
    }

    /**
     * @param array<string, int> $options
     */
    #[Test]
    #[DataProvider('configuredRuleProvider')]
    public function enforcesOnlyTheConfiguredRule(array $options, string $rejected, string $accepted): void
    {
        $validator = new PasswordRequirement(options: $options);

        self::assertFalse($validator->isValid($rejected));
        self::assertCount(1, $validator->getMessages());

        self::assertTrue($validator->isValid($accepted));
        self::assertSame([], $validator->getMessages());
    }

    #[Test]
    public function imposesNoRequirementWhenNoCountIsConfigured(): void
    {
        $validator = new PasswordRequirement();

        self::assertTrue($validator->isValid(''));
        self::assertSame([], $validator->getMessages());
    }

    #[Test]
    public function passesMessageOverridesOnToTheBaseValidator(): void
    {
        $validator = new PasswordRequirement(options: [
            'length'   => 8,
            'messages' => [
                PasswordRequirement::INVALID_LENGTH_COUNT => 'That password is too short.',
            ],
        ]);

        self::assertFalse($validator->isValid('abc'));
        self::assertSame(
            [PasswordRequirement::INVALID_LENGTH_COUNT => 'That password is too short.'],
            $validator->getMessages(),
        );
    }

    #[Test]
    public function reportsEveryFailedRuleWithItsOwnPlaceholderValue(): void
    {
        $validator = new PasswordRequirement(options: [
            'length'  => 8,
            'upper'   => 2,
            'lower'   => 3,
            'digit'   => 4,
            'special' => 5,
        ]);

        self::assertFalse($validator->isValid(''));

        self::assertSame(
            [
                PasswordRequirement::INVALID_LENGTH_COUNT  => 'Password must be at least 8 characters in length.',
                PasswordRequirement::INVALID_UPPER_COUNT   => 'Password must contain at least 2 uppercase letter(s).',
                PasswordRequirement::INVALID_LOWER_COUNT   => 'Password must contain at least 3 lowercase letter(s).',
                PasswordRequirement::INVALID_DIGIT_COUNT   => 'Password must contain at least 4 numeric character(s).',
                PasswordRequirement::INVALID_SPECIAL_COUNT => 'Password must contain at least 5 special character(s).',
            ],
            $validator->getMessages(),
        );
    }

    #[Test]
    public function requiresExactlyTheConfiguredNumberOfMatches(): void
    {
        $validator = new PasswordRequirement(options: ['upper' => 3]);

        self::assertTrue($validator->isValid('ABC'));
        self::assertFalse($validator->isValid('AB'));
        self::assertArrayHasKey(PasswordRequirement::INVALID_UPPER_COUNT, $validator->getMessages());
    }
}

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

namespace WebwareTest\Validator\Property;

use Eris\TestTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use SensitiveParameter;
use Webware\Validator\PasswordRequirement;

use function Eris\Generator\char;
use function Eris\Generator\choose;
use function Eris\Generator\string;

/**
 * Properties of PasswordRequirement that hold for every input.
 *
 * The example-based suite next door fixes (options, password) pairs. These
 * assert relationships instead, so a change to the counting or the threshold
 * comparison cannot satisfy the examples while breaking the invariant.
 *
 * Deliberately narrow: nothing here re-derives the rule set. Each property
 * states something that must hold whatever the configuration is, so a passing
 * test cannot be produced by copying the implementation into the assertion.
 */
#[CoversClass(PasswordRequirement::class)]
#[CoversMethod(PasswordRequirement::class, '__construct')]
#[CoversMethod(PasswordRequirement::class, 'isValid')]
final class PasswordRequirementPropertyTest extends TestCase
{
    use TestTrait;

    /**
     * Appending a character can only add matches and only increase length, so a
     * password that already satisfies every threshold keeps satisfying it.
     *
     * This is what makes the validator safe to use as a live gate: typing
     * cannot turn an accepted password into a rejected one. The failure it
     * rules out is a threshold compared against the wrong operand, or a count
     * recomputed in a way that can decrease.
     */
    #[Test]
    public function appendingCharactersNeverInvalidatesAPassword(): void
    {
        $this
            ->forAll(
                choose(
                    lowerLimit: 0,
                    upperLimit: 2,
                ),
                string(),
                char(),
            )
            ->withMaxSize(16)
            ->then(static function (
                int $upperThreshold,
                #[SensitiveParameter]
                string $password,
                string $character,
            ): void {
                $requirement = new PasswordRequirement(
                    options: ['length' => 3, 'upper' => $upperThreshold],
                );

                if (! $requirement->isValid($password)) {
                    return;
                }

                self::assertTrue(
                    $requirement->isValid($password . $character),
                    'Appending a character invalidated an accepted password.',
                );
            });
    }

    /**
     * Counting is stateless across calls: the verdict and the reported messages
     * are the same on the second evaluation as on the first.
     *
     * A validator instance is reused by Laminas input filters, so state carried
     * between calls would leak one field's failures into the next.
     */
    #[Test]
    public function repeatedEvaluationIsStable(): void
    {
        $this
            ->forAll(
                choose(
                    lowerLimit: 0,
                    upperLimit: 6,
                ),
                string(),
            )
            ->withMaxSize(16)
            ->then(static function (int $length, #[SensitiveParameter] string $password): void {
                $requirement = new PasswordRequirement(
                    options: ['length' => $length, 'digit' => 1],
                );

                $firstVerdict  = $requirement->isValid($password);
                $firstMessages = $requirement->getMessages();

                $secondVerdict  = $requirement->isValid($password);
                $secondMessages = $requirement->getMessages();

                self::assertSame($firstVerdict, $secondVerdict);
                self::assertSame($firstMessages, $secondMessages);
            });
    }

    /**
     * Every threshold defaults to `0`, which reads as "no requirement", so no
     * input can fail. Totality of the accept case: it is the one verdict the
     * rule set must never produce from an unconfigured validator.
     */
    #[Test]
    public function unconfiguredValidatorAcceptsEveryInput(): void
    {
        $this
            ->forAll(string())
            ->withMaxSize(24)
            ->then(static function (#[SensitiveParameter] string $password): void {
                self::assertTrue(
                    new PasswordRequirement()->isValid($password),
                    'An unconfigured validator rejected a password.',
                );
            });
    }
}

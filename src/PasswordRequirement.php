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

namespace Webware\Validator;

use Laminas\Translator\TranslatorInterface;
use Laminas\Validator\AbstractValidator;
use Override;
use SensitiveParameter;

use function mb_strlen;
use function preg_match_all;

/**
 * Requires a password to meet a minimum length and minimum per-character-class counts.
 *
 * Every configured rule is evaluated on each call, so a rejected password reports all of
 * the reasons it was rejected rather than only the first. A count of `0` (or less) imposes
 * no requirement, because no password can hold fewer than zero matches.
 *
 * @api
 */
final class PasswordRequirement extends AbstractValidator
{
    public const string INVALID_LENGTH_COUNT = 'invalidLengthCount';

    public const string INVALID_UPPER_COUNT = 'invalidUpperCount';

    public const string INVALID_LOWER_COUNT = 'invalidLowerCount';

    public const string INVALID_DIGIT_COUNT = 'invalidDigitCount';

    public const string INVALID_SPECIAL_COUNT = 'invalidSpecialCount';

    /** Supported special characters. */
    public const string POSSIBLE_SPECIAL_CHARS = '/[].\'"+=\[\\\\@_!\#$%^&*()<>?|}{~:-]/';

    /**
     * @var array<string, string>
     */
    protected array $messageTemplates = [
        self::INVALID_LENGTH_COUNT  => 'Password must be at least %length% characters in length.',
        self::INVALID_UPPER_COUNT   => 'Password must contain at least %upper% uppercase letter(s).',
        self::INVALID_LOWER_COUNT   => 'Password must contain at least %lower% lowercase letter(s).',
        self::INVALID_DIGIT_COUNT   => 'Password must contain at least %digit% numeric character(s).',
        self::INVALID_SPECIAL_COUNT => 'Password must contain at least %special% special character(s).',
    ];

    /**
     * @var array<string, string|array<string, string>>
     */
    protected array $messageVariables = [
        'length'  => 'length',
        'upper'   => 'upper',
        'lower'   => 'lower',
        'digit'   => 'digit',
        'special' => 'special',
    ];

    protected readonly int $length;

    protected readonly int $upper;

    protected readonly int $lower;

    protected readonly int $digit;

    protected readonly int $special;

    /**
     * @param array<string, mixed> $options
     */
    public function __construct(array $options = [])
    {
        $this->length = self::countOption(
            options: $options,
            key    : 'length',
        );
        $this->upper = self::countOption(
            options: $options,
            key    : 'upper',
        );
        $this->lower = self::countOption(
            options: $options,
            key    : 'lower',
        );
        $this->digit = self::countOption(
            options: $options,
            key    : 'digit',
        );
        $this->special = self::countOption(
            options: $options,
            key    : 'special',
        );

        /** @var array{
         *     messages?: array<string, string>,
         *     translator?: TranslatorInterface|null,
         *     translatorEnabled?: bool,
         *     translatorTextDomain?: string|null,
         *     valueObscured?: bool,
         *     ...<string, mixed>
         * } $abstractOptions
         */
        $abstractOptions = $options;

        parent::__construct(options: $abstractOptions);
    }

    /**
     * Reads one of the five count options, defaulting to `0` when it is not configured.
     *
     * @param array<string, mixed> $options
     */
    private static function countOption(array $options, string $key): int
    {
        return (int) ($options[$key] ?? 0);
    }

    #[Override]
    public function isValid(mixed $value): bool
    {
        $password = (string) $value;

        $this->setValue($password);

        $failedRules = $this->failedRules(password: $password);

        foreach ($failedRules as $messageKey) {
            $this->error(messageKey: $messageKey);
        }

        return [] === $failedRules;
    }

    /**
     * Counts the characters of `$subject` that match `$pattern`.
     *
     * `preg_match_all()` reports `false` for a pattern it cannot compile. Every pattern used
     * here is a literal, so that cannot happen at runtime; counting a failure as `0` keeps
     * the rule failing closed rather than passing an unbreakable password.
     */
    private function countMatches(string $pattern, string $subject): int
    {
        return (int) preg_match_all(
            pattern: $pattern,
            subject: $subject,
        );
    }

    /**
     * Every rule the password fails, in template order.
     *
     * @return list<string>
     */
    private function failedRules(#[SensitiveParameter] string $password): array
    {
        $upperCount = $this->countMatches(
            pattern: '/[A-Z]/',
            subject: $password,
        );
        $lowerCount = $this->countMatches(
            pattern: '/[a-z]/',
            subject: $password,
        );
        $digitCount = $this->countMatches(
            pattern: '/[0-9]/',
            subject: $password,
        );
        $specialCount = $this->countMatches(
            pattern: self::POSSIBLE_SPECIAL_CHARS,
            subject: $password,
        );

        $failedRules = [];

        if (mb_strlen(string: $password) < $this->length) {
            $failedRules[] = self::INVALID_LENGTH_COUNT;
        }

        if ($upperCount < $this->upper) {
            $failedRules[] = self::INVALID_UPPER_COUNT;
        }

        if ($lowerCount < $this->lower) {
            $failedRules[] = self::INVALID_LOWER_COUNT;
        }

        if ($digitCount < $this->digit) {
            $failedRules[] = self::INVALID_DIGIT_COUNT;
        }

        if ($specialCount < $this->special) {
            $failedRules[] = self::INVALID_SPECIAL_COUNT;
        }

        return $failedRules;
    }
}

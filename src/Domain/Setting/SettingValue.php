<?php

declare(strict_types=1);

namespace Saso\Domain\Setting;

use InvalidArgumentException;

/**
 * Typed wrapper around a single `system_setting` value.
 *
 * The constructor validates that the raw payload matches the declared
 * {@see SettingType} — no `Bool` value carrying the string "yes", no
 * `Int` carrying alphabetic noise. Callers always go through
 * {@see asString()}, {@see asInt()}, {@see asBool()}, or
 * {@see asJson()} to read; the casting is centralised so each call
 * site does not reinvent its own type coercion.
 *
 * For `Secret` values, the constructor accepts the plaintext; the
 * service is responsible for encrypting at rest. The plaintext is the
 * caller-facing shape — admin UI never reads it back.
 */
final readonly class SettingValue
{
    public function __construct(
        public string $raw,
        public SettingType $type,
    ) {
        match ($type) {
            SettingType::Bool => self::ensureBoolFormat($raw),
            SettingType::Int  => self::ensureIntFormat($raw),
            SettingType::Json => self::ensureJsonFormat($raw),
            default           => null,
        };
    }

    public static function string(string $value): self
    {
        return new self($value, SettingType::String);
    }

    public static function int(int $value): self
    {
        return new self((string) $value, SettingType::Int);
    }

    public static function bool(bool $value): self
    {
        return new self($value ? '1' : '0', SettingType::Bool);
    }

    public static function secret(string $plaintext): self
    {
        return new self($plaintext, SettingType::Secret);
    }

    /**
     * @param array<mixed>|object $value any json_encode-able payload
     */
    public static function json(array|object $value): self
    {
        $encoded = json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($encoded === false) {
            throw new InvalidArgumentException('SettingValue::json failed to encode the supplied payload.');
        }

        return new self($encoded, SettingType::Json);
    }

    public function asString(): string
    {
        return $this->raw;
    }

    public function asInt(): int
    {
        return (int) $this->raw;
    }

    public function asBool(): bool
    {
        return $this->raw === '1' || strcasecmp($this->raw, 'true') === 0;
    }

    public function asJson(): mixed
    {
        return json_decode($this->raw, associative: true);
    }

    private static function ensureBoolFormat(string $raw): void
    {
        if ($raw !== '0' && $raw !== '1') {
            throw new InvalidArgumentException(sprintf(
                'SettingValue::Bool raw must be "0" or "1" (got %s).',
                $raw,
            ));
        }
    }

    private static function ensureIntFormat(string $raw): void
    {
        if (preg_match('/^-?\d+$/', $raw) !== 1) {
            throw new InvalidArgumentException(sprintf(
                'SettingValue::Int raw must be a base-10 integer string (got %s).',
                $raw,
            ));
        }
    }

    private static function ensureJsonFormat(string $raw): void
    {
        if ($raw === '') {
            throw new InvalidArgumentException('SettingValue::Json raw must not be empty.');
        }
        json_decode($raw);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new InvalidArgumentException(sprintf(
                'SettingValue::Json raw is not valid JSON: %s',
                json_last_error_msg(),
            ));
        }
    }
}

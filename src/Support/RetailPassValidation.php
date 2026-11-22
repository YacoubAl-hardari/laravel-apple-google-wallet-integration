<?php

namespace Yacoubalhaidari\AppleGoogleWallet\Support;

use DateTimeImmutable;
use DateTimeInterface;
use InvalidArgumentException;

final class RetailPassValidation
{
    public static function required(string $value, string $field): void
    {
        if (trim($value) === '') {
            throw new InvalidArgumentException("{$field} must not be empty.");
        }
    }

    public static function state(string $state): void
    {
        if (! in_array($state, ['ACTIVE', 'COMPLETED', 'EXPIRED', 'INACTIVE'], true)) {
            throw new InvalidArgumentException('Unsupported wallet pass state.');
        }
    }

    public static function countryCode(?string $countryCode): void
    {
        if ($countryCode !== null && ! preg_match('/^[A-Z]{2}$/D', $countryCode)) {
            throw new InvalidArgumentException('A wallet country code must contain two uppercase letters.');
        }
    }

    /**
     * @param  list<array{uri: string, id?: string, description?: string, localizedDescription?: array{defaultValue: array{language: string, value: string}, translatedValues?: list<array{language: string, value: string}>}}>  $links
     */
    public static function links(array $links): void
    {
        if (! array_is_list($links)) {
            throw new InvalidArgumentException('Merchant links must be a list of URI objects.');
        }

        foreach ($links as $link) {
            $uri = is_array($link) ? ($link['uri'] ?? null) : null;

            if (! is_string($uri) || ! filter_var($uri, FILTER_VALIDATE_URL)
                || strtolower((string) parse_url($uri, PHP_URL_SCHEME)) !== 'https'
                || parse_url($uri, PHP_URL_USER) !== null || parse_url($uri, PHP_URL_PASS) !== null) {
                throw new InvalidArgumentException('Merchant links require valid HTTPS URLs without credentials.');
            }

            if (array_diff(array_keys($link), ['uri', 'id', 'description', 'localizedDescription']) !== []) {
                throw new InvalidArgumentException('Merchant links contain unsupported URI fields.');
            }

            foreach (['id', 'description'] as $field) {
                if (array_key_exists($field, $link) && ! is_string($link[$field])) {
                    throw new InvalidArgumentException("Merchant link {$field} must be a string.");
                }
            }

            if (array_key_exists('localizedDescription', $link)) {
                self::localizedString($link['localizedDescription']);
            }
        }
    }

    private static function localizedString(mixed $value): void
    {
        if (! is_array($value) || ! isset($value['defaultValue'])
            || array_diff(array_keys($value), ['defaultValue', 'translatedValues']) !== []) {
            throw new InvalidArgumentException('Localized merchant link descriptions require a default value.');
        }

        $translations = $value['translatedValues'] ?? [];

        if (! is_array($translations) || ! array_is_list($translations)) {
            throw new InvalidArgumentException('Merchant link translations must be a list.');
        }

        foreach ([$value['defaultValue'], ...$translations] as $translation) {
            if (! is_array($translation) || ! is_string($translation['language'] ?? null)
                || trim($translation['language']) === '' || ! is_string($translation['value'] ?? null)
                || array_diff(array_keys($translation), ['language', 'value']) !== []) {
                throw new InvalidArgumentException('Merchant link translations require a language and string value.');
            }
        }
    }

    /**
     * @param  list<array{latitude: float|int|string, longitude: float|int|string}>  $locations
     */
    public static function locations(array $locations): void
    {
        if (! array_is_list($locations) || count($locations) > 10) {
            throw new InvalidArgumentException('A wallet pass supports at most 10 merchant locations.');
        }

        foreach ($locations as $location) {
            $latitude = is_array($location) ? ($location['latitude'] ?? null) : null;
            $longitude = is_array($location) ? ($location['longitude'] ?? null) : null;

            if (! is_numeric($latitude) || ! is_numeric($longitude)
                || ! is_finite((float) $latitude) || ! is_finite((float) $longitude)
                || abs((float) $latitude) > 90 || abs((float) $longitude) > 180) {
                throw new InvalidArgumentException('Merchant locations require valid latitude and longitude.');
            }
        }
    }

    public static function micros(mixed $micros): string
    {
        if (! is_string($micros) && ! is_int($micros)) {
            throw new InvalidArgumentException('Gift card balance must be an exact integer number of micros.');
        }

        $micros = (string) $micros;

        if (! preg_match('/^(0|[1-9][0-9]*)$/D', $micros)
            || strlen($micros) > 19
            || (strlen($micros) === 19 && strcmp($micros, '9223372036854775807') > 0)) {
            throw new InvalidArgumentException('Gift card balance must be a nonnegative int64 number of micros.');
        }

        return $micros;
    }

    public static function date(mixed $value, string $field): ?DateTimeImmutable
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof DateTimeInterface) {
            return DateTimeImmutable::createFromInterface($value);
        }

        if (! is_string($value) || ! preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d+)?(?:Z|[+-]\d{2}:\d{2})$/D', $value)) {
            throw new InvalidArgumentException("{$field} requires an ISO 8601 timestamp with a timezone offset.");
        }

        try {
            $date = new DateTimeImmutable($value);
            $errors = DateTimeImmutable::getLastErrors();
        } catch (\Exception) {
            throw new InvalidArgumentException("{$field} is not a valid timestamp.");
        }

        if ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0)) {
            throw new InvalidArgumentException("{$field} is not a valid timestamp.");
        }

        return $date;
    }
}

<?php

namespace Yacoubalhaidari\AppleGoogleWallet\Support;

final class AppleColorNormalizer
{
    public static function normalize(mixed $color, string $default = 'rgb(255, 255, 255)'): string
    {
        $color = trim((string) $color);
        if ($color === '') {
            return $default;
        }

        if (str_starts_with(strtolower($color), 'rgb(')) {
            return $color;
        }

        if (!str_starts_with($color, '#')) {
            $color = '#' . $color;
        }

        $hex = ltrim($color, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }

        if (!preg_match('/^[0-9A-Fa-f]{6}$/', $hex)) {
            return $default;
        }

        return sprintf(
            'rgb(%d, %d, %d)',
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2))
        );
    }
}

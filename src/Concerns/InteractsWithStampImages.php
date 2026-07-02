<?php

namespace Yacoubalhaidari\AppleGoogleWallet\Concerns;

use Illuminate\Support\Facades\Storage;

trait InteractsWithStampImages
{
    /**
     * @return array<int, int> columns per row
     */
    protected function stampRows(int $total, int $maxColumns = 5): array
    {
        if ($total <= 5) {
            $first = min(3, $total);

            return array_filter([
                $first,
                $total - $first,
            ]);
        }

        $columns = max(1, min($maxColumns, $total));
        $rows = [];

        for ($remaining = $total; $remaining > 0; $remaining -= $columns) {
            $rows[] = min($columns, $remaining);
        }

        return $rows;
    }

    protected function savePngToPublicDisk(string $relativePath, $image, ?string $disk = null): ?string
    {
        $disk ??= (string) config('apple-wallet.public_disk', 'public');

        Storage::disk($disk)->makeDirectory(dirname($relativePath));

        ob_start();
        imagepng($image);
        $binary = ob_get_clean();
        imagedestroy($image);

        if ($binary === false) {
            return null;
        }

        Storage::disk($disk)->put($relativePath, $binary);

        return Storage::disk($disk)->url($relativePath);
    }

    protected function drawCoverBackground($canvas, int $width, int $height, string $path): bool
    {
        $background = $this->loadImage($path);
        if (!$background) {
            return false;
        }

        $srcWidth = imagesx($background);
        $srcHeight = imagesy($background);
        if ($srcWidth <= 0 || $srcHeight <= 0) {
            imagedestroy($background);

            return false;
        }

        $scale = max($width / $srcWidth, $height / $srcHeight);
        $scaledWidth = max(1, (int) ceil($srcWidth * $scale));
        $scaledHeight = max(1, (int) ceil($srcHeight * $scale));
        $cropX = max(0, (int) floor(($scaledWidth - $width) / 2));
        $cropY = max(0, (int) floor(($scaledHeight - $height) / 2));

        $scaled = imagecreatetruecolor($scaledWidth, $scaledHeight);
        if (!$scaled) {
            imagedestroy($background);

            return false;
        }

        imagecopyresampled(
            $scaled,
            $background,
            0,
            0,
            0,
            0,
            $scaledWidth,
            $scaledHeight,
            $srcWidth,
            $srcHeight
        );

        imagecopy($canvas, $scaled, 0, 0, $cropX, $cropY, $width, $height);

        imagedestroy($background);
        imagedestroy($scaled);

        return true;
    }

    protected function applyStripOverlay($image, int $width, int $height, float $opacity): void
    {
        if ($opacity <= 0) {
            return;
        }

        $alpha = min(127, max(0, (int) round(127 * $opacity)));
        $overlayColor = imagecolorallocatealpha($image, 0, 0, 0, $alpha);
        imagefilledrectangle($image, 0, 0, $width, $height, $overlayColor);
    }

    protected function resolveStampIconPath(mixed $configuredPath): ?string
    {
        if ($configuredPath === null || trim((string) $configuredPath) === '') {
            return null;
        }

        $local = $this->pathFromUrl((string) $configuredPath);
        if (! $local || ! is_file($local)) {
            return null;
        }

        if (strtolower(pathinfo($local, PATHINFO_EXTENSION)) !== 'png') {
            return null;
        }

        return $local;
    }

    protected function pasteStampIcon($canvas, $icon, int $centerX, int $centerY, int $iconSize): void
    {
        $destX = $centerX - (int) ($iconSize / 2);
        $destY = $centerY - (int) ($iconSize / 2);

        imagealphablending($canvas, true);
        imagesavealpha($canvas, true);
        imagecopyresampled(
            $canvas,
            $icon,
            $destX,
            $destY,
            0,
            0,
            $iconSize,
            $iconSize,
            imagesx($icon),
            imagesy($icon)
        );
    }

    protected function resolveIconPath(
        ?string $configuredPath,
        ?string $cardLogoPath = null,
        ?string $cardImageUrl = null,
    ): ?string {
        if ($configuredPath) {
            $local = $this->pathFromUrl($configuredPath);
            if ($local && is_file($local)) {
                return $local;
            }
        }

        if ($cardLogoPath) {
            $local = storage_path('app/public/' . ltrim($cardLogoPath, '/'));
            if (is_file($local)) {
                return $local;
            }
        }

        if ($cardImageUrl) {
            $local = $this->pathFromUrl($cardImageUrl);
            if ($local && is_file($local)) {
                return $local;
            }

            return $this->downloadToTemp($cardImageUrl);
        }

        return null;
    }

    protected function pathFromUrl(string $url): ?string
    {
        if (is_file($url)) {
            return $url;
        }

        if (!str_starts_with($url, 'http://') && !str_starts_with($url, 'https://')) {
            $public = public_path(ltrim($url, '/'));
            if (is_file($public)) {
                return $public;
            }

            $storage = storage_path('app/public/' . ltrim($url, '/'));
            if (is_file($storage)) {
                return $storage;
            }

            return null;
        }

        $path = parse_url($url, PHP_URL_PATH);
        if ($path && str_starts_with($path, '/storage/')) {
            $local = storage_path('app/public/' . ltrim(substr($path, strlen('/storage/')), '/'));
            if (is_file($local)) {
                return $local;
            }
        }

        return $this->downloadToTemp($url);
    }

    protected function downloadToTemp(string $url): ?string
    {
        $contents = @file_get_contents($url);
        if ($contents === false) {
            return null;
        }

        $temp = tempnam(sys_get_temp_dir(), 'wallet_stamp_');
        if ($temp === false) {
            return null;
        }

        file_put_contents($temp, $contents);

        return $temp;
    }

    protected function loadImage(string $path)
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        $loaded = match ($extension) {
            'jpg', 'jpeg' => @imagecreatefromjpeg($path),
            'png' => @imagecreatefrompng($path),
            'webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : false,
            'gif' => @imagecreatefromgif($path),
            default => @imagecreatefromstring(@file_get_contents($path)),
        };

        return $loaded ?: null;
    }

    protected function hexToRgb(string $hex): array
    {
        $hex = ltrim(trim($hex), '#');

        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }

        if (!preg_match('/^[0-9A-Fa-f]{6}$/', $hex)) {
            return [0, 0, 0];
        }

        return [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2)),
        ];
    }

    /**
     * Paste an icon onto a canvas centered at ($centerX, $centerY) with optional
     * dimming. $dim is a percentage 0–100; 100 = fully opaque, 0 = invisible.
     *
     * When $dim < 100 the icon is first composed onto a temporary RGBA layer
     * with the requested alpha and then blended into the canvas, so it works
     * correctly even when the canvas itself has no alpha channel.
     */
    protected function pasteImageWithAlpha(
        $canvas,
        $icon,
        int $centerX,
        int $centerY,
        int $iconSize,
        int $dim = 100
    ): void {
        $dim = max(0, min(100, $dim));
        $destX = $centerX - (int) ($iconSize / 2);
        $destY = $centerY - (int) ($iconSize / 2);

        if ($dim === 100) {
            // Fully opaque — fast path identical to pasteStampIcon
            imagealphablending($canvas, true);
            imagesavealpha($canvas, true);
            imagecopyresampled(
                $canvas, $icon,
                $destX, $destY, 0, 0,
                $iconSize, $iconSize,
                imagesx($icon), imagesy($icon)
            );

            return;
        }

        // Create a temporary true-colour buffer with alpha support
        $tmp = imagecreatetruecolor($iconSize, $iconSize);
        if (! $tmp) {
            return;
        }

        imagealphablending($tmp, false);
        imagesavealpha($tmp, true);

        // Fill with fully-transparent background
        $transparent = imagecolorallocatealpha($tmp, 0, 0, 0, 127);
        imagefilledrectangle($tmp, 0, 0, $iconSize - 1, $iconSize - 1, $transparent);

        // Draw the icon scaled onto the temp buffer
        imagealphablending($tmp, true);
        imagecopyresampled(
            $tmp, $icon,
            0, 0, 0, 0,
            $iconSize, $iconSize,
            imagesx($icon), imagesy($icon)
        );

        // Apply the dim level by re-mapping the alpha channel
        // GD alpha: 0 = opaque, 127 = fully transparent
        $alpha = (int) round(127 * (1 - $dim / 100));

        for ($px = 0; $px < $iconSize; $px++) {
            for ($py = 0; $py < $iconSize; $py++) {
                $pixel = imagecolorat($tmp, $px, $py);
                $r = ($pixel >> 16) & 0xFF;
                $g = ($pixel >> 8) & 0xFF;
                $b = $pixel & 0xFF;
                $a = ($pixel >> 24) & 0x7F;

                // Combine existing pixel alpha with the requested dim alpha
                $newAlpha = min(127, $a + $alpha);
                $newColor = imagecolorallocatealpha($tmp, $r, $g, $b, $newAlpha);
                imagesetpixel($tmp, $px, $py, $newColor);
            }
        }

        // Blend the dimmed icon onto the canvas
        imagealphablending($canvas, true);
        imagecopy($canvas, $tmp, $destX, $destY, 0, 0, $iconSize, $iconSize);

        imagedestroy($tmp);
    }

    /**
     * Draw a filled rounded rectangle.
     * $r is the corner radius (auto-capped so it never exceeds half the side).
     */
    protected function drawRoundedFilledRect(
        $image,
        int $x,
        int $y,
        int $w,
        int $h,
        int $r,
        $color
    ): void {
        $r = min($r, (int) ($w / 2), (int) ($h / 2));

        // Body rectangles
        imagefilledrectangle($image, $x + $r,     $y,     $x + $w - $r, $y + $h,     $color);
        imagefilledrectangle($image, $x,           $y + $r, $x + $w,     $y + $h - $r, $color);

        // Four corner ellipses
        imagefilledellipse($image, $x + $r,       $y + $r,       $r * 2, $r * 2, $color);
        imagefilledellipse($image, $x + $w - $r,  $y + $r,       $r * 2, $r * 2, $color);
        imagefilledellipse($image, $x + $r,       $y + $h - $r,  $r * 2, $r * 2, $color);
        imagefilledellipse($image, $x + $w - $r,  $y + $h - $r,  $r * 2, $r * 2, $color);
    }

    /**
     * Draw the outline of a rounded rectangle.
     * $thickness controls the line width (pixels).
     */
    protected function drawRoundedRectBorder(
        $image,
        int $x,
        int $y,
        int $w,
        int $h,
        int $r,
        $color,
        int $thickness = 1
    ): void {
        $r = min($r, (int) ($w / 2), (int) ($h / 2));
        imagesetthickness($image, $thickness);

        // Straight edges
        imageline($image, $x + $r,       $y,       $x + $w - $r, $y,       $color);
        imageline($image, $x + $r,       $y + $h,  $x + $w - $r, $y + $h,  $color);
        imageline($image, $x,            $y + $r,  $x,           $y + $h - $r, $color);
        imageline($image, $x + $w,       $y + $r,  $x + $w,      $y + $h - $r, $color);

        // Corner arcs (top-left, top-right, bottom-left, bottom-right)
        imagearc($image, $x + $r,       $y + $r,       $r * 2, $r * 2, 180, 270, $color);
        imagearc($image, $x + $w - $r,  $y + $r,       $r * 2, $r * 2, 270, 360, $color);
        imagearc($image, $x + $r,       $y + $h - $r,  $r * 2, $r * 2,  90, 180, $color);
        imagearc($image, $x + $w - $r,  $y + $h - $r,  $r * 2, $r * 2,   0,  90, $color);

        imagesetthickness($image, 1);
    }
}

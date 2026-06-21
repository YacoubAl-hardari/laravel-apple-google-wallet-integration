<?php

namespace Yacoubalhaidari\AppleGoogleWallet\Google;

use Yacoubalhaidari\AppleGoogleWallet\Concerns\InteractsWithStampImages;
use Yacoubalhaidari\AppleGoogleWallet\DTOs\LoyaltyProgramData;
use Yacoubalhaidari\AppleGoogleWallet\DTOs\MemberCardData;

class StampCardImageGenerator
{
    use InteractsWithStampImages;

    public function generate(LoyaltyProgramData $program, MemberCardData $member): ?string
    {
        if (!extension_loaded('gd')) {
            return null;
        }

        $total = max(1, $program->requiredStamps);
        $filled = min($total, max(0, $member->stampsProgress));
        $rows = $this->stampRows($total, (int) config('google-wallet.stamp_columns', 5));

        $cell = (int) config('google-wallet.stamp_cell_size', 88);
        $gap = (int) config('google-wallet.stamp_gap', 20);
        $paddingX = 40;
        $paddingY = 28;
        $statsHeight = 56;

        $maxCols = max($rows ?: [1]);
        $width = (int) config('google-wallet.stamp_wallet_width', 1032);
        $contentWidth = ($maxCols * $cell) + (max(0, $maxCols - 1) * $gap) + ($paddingX * 2);
        if ($contentWidth > $width) {
            $width = $contentWidth;
        }
        $height = (count($rows) * $cell) + (max(0, count($rows) - 1) * $gap) + ($paddingY * 2) + $statsHeight;

        $image = imagecreatetruecolor($width, $height);
        if (!$image) {
            return null;
        }

        imagesavealpha($image, true);

        $this->drawStripBackground($image, $width, $height);

        $completedIconPath = $this->resolveIconPath(
            config('google-wallet.stamp_completed_icon'),
            $program->logoPath,
            $program->imageUrl,
        );
        $emptyIconPath = $this->resolveIconPath(
            config('google-wallet.stamp_empty_icon'),
            null,
            null,
        );

        $stampIndex = 0;
        foreach ($rows as $rowIndex => $colsInRow) {
            $rowWidth = ($colsInRow * $cell) + (max(0, $colsInRow - 1) * $gap);
            $startX = (int) (($width - $rowWidth) / 2);
            $y = $paddingY + ($rowIndex * ($cell + $gap));

            for ($col = 0; $col < $colsInRow; $col++) {
                $x = $startX + ($col * ($cell + $gap));
                $isCompleted = $stampIndex < $filled;

                $this->drawStampSlot(
                    $image,
                    $x,
                    $y,
                    $cell,
                    $isCompleted,
                    $completedIconPath,
                    $emptyIconPath
                );

                $stampIndex++;
            }
        }

        $this->drawStatsRow($image, $width, $height - $statsHeight + 12, $total, $filled, $member->rewardsEarned);

        $basePath = trim((string) config('google-wallet.stamp_storage_path', 'wallet-stamps/google'), '/');
        $relativePath = sprintf(
            '%s/card_%s_%d_of_%d.png',
            $basePath,
            $member->id,
            $filled,
            $total
        );

        return $this->savePngToPublicDisk(
            $relativePath,
            $image,
            (string) config('google-wallet.public_disk', 'public')
        );
    }

    protected function drawStampSlot(
        $image,
        int $x,
        int $y,
        int $size,
        bool $isCompleted,
        ?string $completedIconPath,
        ?string $emptyIconPath
    ): void {
        $borderRgb = $this->hexToRgb(config('google-wallet.stamp_border_color', '#FFFFFF'));
        $emptyRgb = $this->hexToRgb(config('google-wallet.stamp_empty_color', '#1A1A1A'));
        $filledRgb = $this->hexToRgb(config('google-wallet.stamp_filled_color', '#FFFFFF'));

        $borderColor = imagecolorallocate($image, $borderRgb[0], $borderRgb[1], $borderRgb[2]);
        $emptyColor = imagecolorallocatealpha($image, $emptyRgb[0], $emptyRgb[1], $emptyRgb[2], $isCompleted ? 0 : 40);
        $filledColor = imagecolorallocate($image, $filledRgb[0], $filledRgb[1], $filledRgb[2]);

        $radius = (int) ($size / 2);
        $centerX = $x + $radius;
        $centerY = $y + $radius;

        imagefilledellipse($image, $centerX, $centerY, $size, $size, $isCompleted ? $filledColor : $emptyColor);
        imageellipse($image, $centerX, $centerY, $size, $size, $borderColor);

        $iconPath = $isCompleted ? $completedIconPath : ($emptyIconPath ?: $completedIconPath);
        if (!$iconPath) {
            return;
        }

        $icon = $this->loadImage($iconPath);
        if (!$icon) {
            return;
        }

        if (!$isCompleted && !$emptyIconPath && $completedIconPath) {
            $this->pasteIconDimmed($image, $icon, $centerX, $centerY, (int) ($size * 0.62), 45);
        } else {
            $this->pasteIcon($image, $icon, $centerX, $centerY, (int) ($size * 0.62));
        }

        imagedestroy($icon);
    }

    protected function pasteIcon($canvas, $icon, int $centerX, int $centerY, int $iconSize): void
    {
        $destX = $centerX - (int) ($iconSize / 2);
        $destY = $centerY - (int) ($iconSize / 2);

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

    protected function pasteIconDimmed($canvas, $icon, int $centerX, int $centerY, int $iconSize, int $alpha): void
    {
        $w = imagesx($icon);
        $h = imagesy($icon);
        $tmp = imagecreatetruecolor($iconSize, $iconSize);
        imagesavealpha($tmp, true);
        $transparent = imagecolorallocatealpha($tmp, 0, 0, 0, 127);
        imagefill($tmp, 0, 0, $transparent);

        imagecopyresampled($tmp, $icon, 0, 0, 0, 0, $iconSize, $iconSize, $w, $h);

        for ($x = 0; $x < $iconSize; $x++) {
            for ($y = 0; $y < $iconSize; $y++) {
                $rgba = imagecolorat($tmp, $x, $y);
                $a = ($rgba >> 24) & 0x7F;
                if ($a >= 127) {
                    continue;
                }
                $r = ($rgba >> 16) & 0xFF;
                $g = ($rgba >> 8) & 0xFF;
                $b = $rgba & 0xFF;
                $color = imagecolorallocatealpha($tmp, $r, $g, $b, min(127, $a + $alpha));
                imagesetpixel($tmp, $x, $y, $color);
            }
        }

        $destX = $centerX - (int) ($iconSize / 2);
        $destY = $centerY - (int) ($iconSize / 2);
        imagecopy($canvas, $tmp, $destX, $destY, 0, 0, $iconSize, $iconSize);
        imagedestroy($tmp);
    }

    protected function drawStatsRow($image, int $width, int $y, int $total, int $filled, int $rewards): void
    {
        $textRgb = $this->hexToRgb(config('google-wallet.stamp_text_color', '#FFFFFF'));
        $textColor = imagecolorallocate($image, $textRgb[0], $textRgb[1], $textRgb[2]);
        $remaining = max(0, $total - $filled);

        $leftLabel = wallet_trans('remaining');
        $rightLabel = wallet_trans('rewards');
        $leftValue = (string) $remaining;
        $rightValue = (string) $rewards;

        $font = 3;
        $leftX = (int) ($width * 0.18);
        $rightX = (int) ($width * 0.62);

        imagestring($image, $font, $leftX, $y, $leftLabel, $textColor);
        imagestring($image, 5, $leftX, $y + 18, $leftValue, $textColor);
        imagestring($image, $font, $rightX, $y, $rightLabel, $textColor);
        imagestring($image, 5, $rightX, $y + 18, $rightValue, $textColor);
    }

    protected function drawStripBackground($image, int $width, int $height): void
    {
        $backgroundImage = config('google-wallet.stamp_strip_background_image');
        $imagePath = is_string($backgroundImage) && $backgroundImage !== ''
            ? $this->pathFromUrl($backgroundImage)
            : null;

        if ($imagePath && is_file($imagePath) && $this->drawCoverBackground($image, $width, $height, $imagePath)) {
            $this->applyStripOverlay(
                $image,
                $width,
                $height,
                (float) config('google-wallet.stamp_strip_background_overlay', 0.35)
            );

            return;
        }

        $bgRgb = $this->hexToRgb(config('google-wallet.stamp_strip_background', '#000000'));
        $bgColor = imagecolorallocate($image, $bgRgb[0], $bgRgb[1], $bgRgb[2]);
        imagefilledrectangle($image, 0, 0, $width, $height, $bgColor);
    }
}

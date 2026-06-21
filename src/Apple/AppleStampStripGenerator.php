<?php

namespace Yacoubalhaidari\AppleGoogleWallet\Apple;

use Yacoubalhaidari\AppleGoogleWallet\Concerns\InteractsWithStampImages;
use Yacoubalhaidari\AppleGoogleWallet\DTOs\LoyaltyProgramData;
use Yacoubalhaidari\AppleGoogleWallet\DTOs\MemberCardData;

class AppleStampStripGenerator
{
    use InteractsWithStampImages;

    public function generate(LoyaltyProgramData $program, MemberCardData $member): ?string
    {
        if (!extension_loaded('gd')) {
            return null;
        }

        $total = max(1, $program->requiredStamps);
        $filled = min($total, max(0, $member->stampsProgress));
        $rows = $this->stampRows($total, (int) config('apple-wallet.stamp_columns', 5));

        $width = max(375, (int) config('apple-wallet.strip_width', 1125));
        $height = max(123, (int) config('apple-wallet.strip_height', 369));
        $cell = max(48, (int) config('apple-wallet.stamp_cell_size', 118));
        $gap = max(8, (int) config('apple-wallet.stamp_gap', 22));

        $image = imagecreatetruecolor($width, $height);
        if (!$image) {
            return null;
        }

        imagesavealpha($image, true);

        $this->drawStripBackground($image, $width, $height);

        $gridHeight = (count($rows) * $cell) + (max(0, count($rows) - 1) * $gap);
        $maxCols = max($rows ?: [1]);
        $gridWidth = ($maxCols * $cell) + (max(0, $maxCols - 1) * $gap);
        $panelPadding = 28;
        $panelX = (int) (($width - $gridWidth) / 2) - $panelPadding;
        $panelY = (int) (($height - $gridHeight) / 2) - $panelPadding;
        $panelW = $gridWidth + ($panelPadding * 2);
        $panelH = $gridHeight + ($panelPadding * 2);

        $this->drawStampPanel($image, $panelX, $panelY, $panelW, $panelH);

        $stampIndex = 0;
        foreach ($rows as $rowIndex => $colsInRow) {
            $rowWidth = ($colsInRow * $cell) + (max(0, $colsInRow - 1) * $gap);
            $startX = (int) (($width - $rowWidth) / 2);
            $startY = (int) (($height - $gridHeight) / 2);
            $y = $startY + ($rowIndex * ($cell + $gap));

            for ($col = 0; $col < $colsInRow; $col++) {
                $x = $startX + ($col * ($cell + $gap));
                $this->drawStampSlot($image, $x, $y, $cell, $stampIndex < $filled);
                $stampIndex++;
            }
        }

        $basePath = trim((string) config('apple-wallet.stamp_storage_path', 'wallet-stamps/apple'), '/');
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
            (string) config('apple-wallet.public_disk', 'public')
        );
    }

    protected function drawStripBackground($image, int $width, int $height): void
    {
        $backgroundImage = config('apple-wallet.strip_background_image');
        $imagePath = is_string($backgroundImage) && $backgroundImage !== ''
            ? $this->pathFromUrl($backgroundImage)
            : null;

        if ($imagePath && is_file($imagePath) && $this->drawCoverBackground($image, $width, $height, $imagePath)) {
            $this->applyStripOverlay(
                $image,
                $width,
                $height,
                (float) config('apple-wallet.strip_background_overlay', 0.55)
            );

            return;
        }

        $bgRgb = $this->hexToRgb('#8B5E3C');
        $bgColor = imagecolorallocate($image, $bgRgb[0], $bgRgb[1], $bgRgb[2]);
        imagefilledrectangle($image, 0, 0, $width, $height, $bgColor);
    }

    protected function drawStampPanel($image, int $x, int $y, int $width, int $height): void
    {
        $panelColor = imagecolorallocatealpha($image, 0, 0, 0, 100);
        imagefilledrectangle($image, $x, $y, $x + $width, $y + $height, $panelColor);
    }

    protected function drawStampSlot($image, int $x, int $y, int $size, bool $isCompleted): void
    {
        $radius = (int) ($size / 2);
        $centerX = $x + $radius;
        $centerY = $y + $radius;
        $borderWidth = max(2, (int) config('apple-wallet.stamp_border_width', 4));

        $completedRgb = $this->hexToRgb((string) config('apple-wallet.stamp_completed_color', '#E07B2D'));
        $emptyFillRgb = $this->hexToRgb((string) config('apple-wallet.stamp_empty_fill', '#FFFFFF'));
        $emptyBorderRgb = $this->hexToRgb((string) config('apple-wallet.stamp_empty_border', '#FFFFFF'));

        imagesetthickness($image, $borderWidth);

        if ($isCompleted) {
            $fillColor = imagecolorallocate($image, $completedRgb[0], $completedRgb[1], $completedRgb[2]);
            $borderColor = imagecolorallocate($image, 255, 255, 255);
            imagefilledellipse($image, $centerX, $centerY, $size, $size, $fillColor);
            imageellipse($image, $centerX, $centerY, $size, $size, $borderColor);
            $this->drawCheckmark($image, $centerX, $centerY, (int) ($size * 0.42), imagecolorallocate($image, 255, 255, 255));
        } else {
            $fillColor = imagecolorallocatealpha($image, $emptyFillRgb[0], $emptyFillRgb[1], $emptyFillRgb[2], 25);
            $borderColor = imagecolorallocate($image, $emptyBorderRgb[0], $emptyBorderRgb[1], $emptyBorderRgb[2]);
            imagefilledellipse($image, $centerX, $centerY, $size, $size, $fillColor);
            imageellipse($image, $centerX, $centerY, $size, $size, $borderColor);

            $innerSize = (int) ($size * 0.72);
            $innerColor = imagecolorallocatealpha($image, 255, 255, 255, 90);
            imageellipse($image, $centerX, $centerY, $innerSize, $innerSize, $innerColor);
        }

        imagesetthickness($image, 1);
    }

    protected function drawCheckmark($image, int $centerX, int $centerY, int $size, $color): void
    {
        imagesetthickness($image, max(3, (int) round($size / 7)));

        $x1 = $centerX - (int) round($size * 0.42);
        $y1 = $centerY + (int) round($size * 0.05);
        $x2 = $centerX - (int) round($size * 0.08);
        $y2 = $centerY + (int) round($size * 0.38);
        $x3 = $centerX + (int) round($size * 0.48);
        $y3 = $centerY - (int) round($size * 0.36);

        imageline($image, $x1, $y1, $x2, $y2, $color);
        imageline($image, $x2, $y2, $x3, $y3, $color);

        imagesetthickness($image, 1);
    }
}

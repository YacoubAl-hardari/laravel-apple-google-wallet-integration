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

        $cell        = (int) config('google-wallet.stamp_cell_size', 88);
        $gap         = (int) config('google-wallet.stamp_gap', 20);
        $paddingX    = 40;
        $paddingY    = 28;
        $statsHeight = 56;
        $statsGap    = 16; // gap between stats row and stamp grid

        $maxCols      = max($rows ?: [1]);
        $width        = (int) config('google-wallet.stamp_wallet_width', 1032);
        $contentWidth = ($maxCols * $cell) + (max(0, $maxCols - 1) * $gap) + ($paddingX * 2);
        if ($contentWidth > $width) {
            $width = $contentWidth;
        }
        // Stats row is now at the TOP, stamps start below it
        $stampsOffsetY = $paddingY + $statsHeight + $statsGap;
        $height = $stampsOffsetY + (count($rows) * $cell) + (max(0, count($rows) - 1) * $gap) + $paddingY;

        $image = imagecreatetruecolor($width, $height);
        if (!$image) {
            return null;
        }

        imagesavealpha($image, true);

        $this->drawStripBackground($image, $width, $height);

        // Resolve icon paths — supports both the new resolveStampIconPath API
        // and the legacy resolveIconPath API (app/Services style)
        $completedIconPath = $this->resolveStampIconPath(config('google-wallet.stamp_completed_icon'));
        $emptyIconPath     = $this->resolveStampIconPath(config('google-wallet.stamp_empty_icon'));

        // Icon scaling factor (0.4–1.0). Defaults match the app/Services behaviour.
        $iconScale    = (float) config('google-wallet.stamp_icon_scale', 0.88);
        // Dim percentage (0–100) for completed vs empty icons.
        $dimCompleted = (int) config('google-wallet.stamp_icon_dim_completed', 100);
        $dimEmpty     = (int) config('google-wallet.stamp_icon_dim_empty', 75);

        // Draw stats row at the TOP first
        $this->drawStatsRow($image, $width, $paddingY + 8, $total, $filled, $member->rewardsEarned);

        // Draw stamp grid below the stats row
        $stampIndex = 0;
        foreach ($rows as $rowIndex => $colsInRow) {
            $rowWidth = ($colsInRow * $cell) + (max(0, $colsInRow - 1) * $gap);
            $startX   = (int) (($width - $rowWidth) / 2);
            $y        = $stampsOffsetY + ($rowIndex * ($cell + $gap));

            for ($col = 0; $col < $colsInRow; $col++) {
                $x           = $startX + ($col * ($cell + $gap));
                $isCompleted = $stampIndex < $filled;

                $this->drawStampSlot(
                    $image,
                    $x, $y, $cell,
                    $isCompleted,
                    $completedIconPath,
                    $emptyIconPath,
                    $iconScale,
                    $dimCompleted,
                    $dimEmpty,
                );

                $stampIndex++;
            }
        }

        $basePath     = trim((string) config('google-wallet.stamp_storage_path', 'wallet-stamps/google'), '/');
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

    /**
     * Draw a single stamp slot.
     *
     * Shape is controlled via config `google-wallet.stamp_shape`:
     *   - `'circle'`  (default) — filled ellipse, matches original package behaviour
     *   - `'rounded'` — rounded rectangle, matches the app/Services behaviour
     *
     * When an icon image is found it is pasted using `pasteImageWithAlpha`
     * so both $iconScale and $dim* values are fully honoured.
     *
     * @param float $iconScale   Fraction of $size used as icon diameter (0.4–1.0).
     * @param int   $dimCompleted Opacity % for a completed-slot icon (0 = invisible, 100 = opaque).
     * @param int   $dimEmpty     Opacity % for an empty-slot icon (0 = invisible, 100 = opaque).
     */
    protected function drawStampSlot(
        $image,
        int $x,
        int $y,
        int $size,
        bool $isCompleted,
        ?string $completedIconPath,
        ?string $emptyIconPath,
        float $iconScale = 0.88,
        int $dimCompleted = 100,
        int $dimEmpty = 75,
    ): void {
        $shape   = (string) config('google-wallet.stamp_shape', 'circle');
        $radius  = (int) ($size / 2);
        $centerX = $x + $radius;
        $centerY = $y + $radius;

        // ── Background shape ──────────────────────────────────────────────────
        if ($shape === 'rounded') {
            // Rounded-rectangle style (app/Services behaviour)
            $cornerRadius = max(6, (int) ($size * 0.16));

            if ($isCompleted) {
                $filledRgb  = $this->hexToRgb(config('google-wallet.stamp_filled_color', '#FFFFFF'));
                $fillColor  = imagecolorallocate($image, $filledRgb[0], $filledRgb[1], $filledRgb[2]);
                $this->drawRoundedFilledRect($image, $x, $y, $size, $size, $cornerRadius, $fillColor);
            } else {
                $emptyRgb      = $this->hexToRgb(config('google-wallet.stamp_empty_color', '#4A4343'));
                $emptyBorderRgb = $this->hexToRgb(config('google-wallet.stamp_empty_border_color', '#9A7070'));
                $borderWidth   = max(1, (int) config('google-wallet.stamp_border_width', 1));

                $emptyColor  = imagecolorallocate($image, $emptyRgb[0], $emptyRgb[1], $emptyRgb[2]);
                $borderColor = imagecolorallocate($image, $emptyBorderRgb[0], $emptyBorderRgb[1], $emptyBorderRgb[2]);

                $this->drawRoundedFilledRect($image, $x, $y, $size, $size, $cornerRadius, $emptyColor);
                $this->drawRoundedRectBorder($image, $x, $y, $size, $size, $cornerRadius, $borderColor, $borderWidth);
            }
        } else {
            // Circle style (original package behaviour)
            $borderRgb  = $this->hexToRgb(config('google-wallet.stamp_border_color', '#FFFFFF'));
            $emptyRgb   = $this->hexToRgb(config('google-wallet.stamp_empty_color', '#1A1A1A'));
            $filledRgb  = $this->hexToRgb(config('google-wallet.stamp_filled_color', '#FFFFFF'));

            $borderColor = imagecolorallocate($image, $borderRgb[0], $borderRgb[1], $borderRgb[2]);
            $emptyColor  = imagecolorallocatealpha($image, $emptyRgb[0], $emptyRgb[1], $emptyRgb[2], $isCompleted ? 0 : 40);
            $filledColor = imagecolorallocate($image, $filledRgb[0], $filledRgb[1], $filledRgb[2]);

            imagefilledellipse($image, $centerX, $centerY, $size, $size, $isCompleted ? $filledColor : $emptyColor);
            imageellipse($image, $centerX, $centerY, $size, $size, $borderColor);
        }

        // ── Icon overlay ─────────────────────────────────────────────────────
        $iconPath = $isCompleted ? $completedIconPath : ($emptyIconPath ?: $completedIconPath);
        if (!$iconPath) {
            return;
        }

        $icon = $this->loadImage($iconPath);
        if (!$icon) {
            return;
        }

        $iconSize = max(1, (int) round($size * max(0.4, min(1.0, $iconScale))));
        $dim      = $isCompleted ? $dimCompleted : $dimEmpty;

        $this->pasteImageWithAlpha($image, $icon, $centerX, $centerY, $iconSize, $dim);

        imagedestroy($icon);
    }

    protected function drawStatsRow($image, int $width, int $y, int $total, int $filled, int $rewards): void
    {
        $textRgb   = $this->hexToRgb(config('google-wallet.stamp_text_color', '#FFFFFF'));
        $textColor = imagecolorallocate($image, $textRgb[0], $textRgb[1], $textRgb[2]);
        $remaining = max(0, $total - $filled);

        $leftLabel  = wallet_trans('remaining');
        $rightLabel = wallet_trans('rewards');
        $leftValue  = (string) $remaining;
        $rightValue = (string) $rewards;

        $font   = 3;
        $leftX  = (int) ($width * 0.18);
        $rightX = (int) ($width * 0.62);

        imagestring($image, $font, $leftX, $y,      $leftLabel,  $textColor);
        imagestring($image, 5,    $leftX, $y + 18,  $leftValue,  $textColor);
        imagestring($image, $font, $rightX, $y,     $rightLabel, $textColor);
        imagestring($image, 5,    $rightX, $y + 18, $rightValue, $textColor);
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

        $bgRgb    = $this->hexToRgb(config('google-wallet.stamp_strip_background', '#000000'));
        $bgColor  = imagecolorallocate($image, $bgRgb[0], $bgRgb[1], $bgRgb[2]);
        imagefilledrectangle($image, 0, 0, $width, $height, $bgColor);
    }
}

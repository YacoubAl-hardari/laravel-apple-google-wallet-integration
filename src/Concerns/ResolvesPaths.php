<?php

namespace Yacoubalhaidari\AppleGoogleWallet\Concerns;

trait ResolvesPaths
{
    protected function resolvePath(string $path): string
    {
        $path = trim($path);
        if ($path === '') {
            return '';
        }

        if (is_file($path)) {
            return $path;
        }

        $normalized = str_replace('\\', '/', $path);
        $withoutAppPrefix = preg_replace('#^app/+#', '', $normalized) ?? $normalized;
        $storageCandidate = storage_path('app/' . ltrim($withoutAppPrefix, '/'));

        if (is_file($storageCandidate)) {
            return $storageCandidate;
        }

        $baseCandidate = base_path($path);

        return is_file($baseCandidate) ? $baseCandidate : $path;
    }
}

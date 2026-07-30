<?php

namespace App\Support\Sync;

/**
 * Deliberately narrow: only ever touches the specific keys it's given,
 * never rewrites the whole file blindly, and callers are expected to
 * pass an explicit whitelist (see SyncDashboardController) so this can
 * never be used to inject arbitrary .env content from a web form.
 */
class EnvWriter
{
    public static function set(array $values): void
    {
        $path = base_path('.env');

        if (!file_exists($path)) {
            return;
        }

        $contents = file_get_contents($path);
        $lines = preg_split('/\r\n|\r|\n/', $contents);

        $remainingKeys = $values;

        foreach ($lines as $i => $line) {
            foreach ($remainingKeys as $key => $value) {
                if (preg_match('/^' . preg_quote($key, '/') . '\s*=/', $line)) {
                    $lines[$i] = $key . '=' . static::formatValue($value);
                    unset($remainingKeys[$key]);
                }
            }
        }

        if (!empty($remainingKeys)) {
            $lines[] = '';
            $lines[] = '# Offline sync (added via /sync/setup)';
            foreach ($remainingKeys as $key => $value) {
                $lines[] = $key . '=' . static::formatValue($value);
            }
        }

        file_put_contents($path, implode(PHP_EOL, $lines) . PHP_EOL);
    }

    protected static function formatValue(?string $value): string
    {
        $value = str_replace(["\r", "\n"], '', (string) $value);

        if ($value === '' || preg_match('/\s|#|"/', $value)) {
            return '"' . str_replace('"', '\\"', $value) . '"';
        }

        return $value;
    }
}

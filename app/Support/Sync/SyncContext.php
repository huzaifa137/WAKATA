<?php

namespace App\Support\Sync;

use Closure;

/**
 * A simple request-lifetime flag. When we apply a change that came FROM
 * sync (either the central server applying a school's push, or a school
 * install applying a pulled row), model events must not queue that same
 * change back into the outbox — otherwise every sync would create an
 * infinite loop of re-queued changes.
 */
class SyncContext
{
    protected static bool $applyingRemote = false;

    public static function isApplyingRemoteChange(): bool
    {
        return static::$applyingRemote;
    }

    /**
     * Run $callback with remote-apply mode on, guaranteeing it's turned
     * back off afterwards even if $callback throws.
     */
    public static function withRemoteApply(Closure $callback): mixed
    {
        $previous = static::$applyingRemote;
        static::$applyingRemote = true;

        try {
            return $callback();
        } finally {
            static::$applyingRemote = $previous;
        }
    }
}

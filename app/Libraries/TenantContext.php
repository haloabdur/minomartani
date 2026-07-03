<?php

namespace App\Libraries;

/**
 * Request-scoped tenant override. Public slug-prefixed pages (and
 * tests) set this explicitly; admin pages rely on the session values
 * written by TenantFilter instead.
 */
final class TenantContext
{
    public static ?int $rtId = null;

    public static function reset(): void
    {
        self::$rtId = null;
    }
}

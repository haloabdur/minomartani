<?php

namespace App\Libraries;

use CodeIgniter\Cache\CacheInterface;

/**
 * Limits wrong PIN guesses for the public Layanan form. Counted per
 * alamat (not per IP) so rotating IPs doesn't help a guesser: after
 * MAX_FAILURES wrong PINs the alamat is locked for LOCK_SECONDS.
 *
 * Backed by the CI4 cache, so no table is needed. The lock deliberately
 * never reveals itself differently from a wrong PIN to the caller -
 * callers show the same generic error for both.
 */
class PinThrottle
{
    public const MAX_FAILURES = 5;
    public const LOCK_SECONDS = 900;

    private CacheInterface $cache;

    public function __construct(?CacheInterface $cache = null)
    {
        $this->cache = $cache ?? cache();
    }

    private function key(int $idRt, int $idAlamat): string
    {
        // No ':' / '{}' etc. - reserved characters in CI4 cache keys.
        return 'layanan_pin_' . $idRt . '_' . $idAlamat;
    }

    public function isLocked(int $idRt, int $idAlamat): bool
    {
        return (int) $this->cache->get($this->key($idRt, $idAlamat)) >= self::MAX_FAILURES;
    }

    public function recordFailure(int $idRt, int $idAlamat): void
    {
        $key = $this->key($idRt, $idAlamat);

        $this->cache->save($key, (int) $this->cache->get($key) + 1, self::LOCK_SECONDS);
    }

    public function reset(int $idRt, int $idAlamat): void
    {
        $this->cache->delete($this->key($idRt, $idAlamat));
    }
}

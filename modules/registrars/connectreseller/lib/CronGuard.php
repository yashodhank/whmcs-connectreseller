<?php

namespace WHMCS\Module\Registrar\ConnectReseller;

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

class CronGuard
{
    public const DEFAULT_FREQUENCY_HOURS = 24;
    public const LOCK_TTL_SECONDS = 3600;
    public const PRICE_CHUNK_SIZE = 50;
    public const KYC_CHUNK_SIZE = 25;
    public const TIME_BUDGET_SECONDS = 20;
    public const MIN_REMAINING_SECONDS = 15;

    public const LOCK_KYC = 'ConnectResellerKycCronLock';
    public const LOCK_PRICE = 'ConnectResellerPriceSyncLock';
    public const KEY_KYC_LAST_RUN = 'ConnectResellerKycCronLastRun';
    public const KEY_KYC_CURSOR = 'ConnectResellerKycCronCursor';
    public const KEY_PRICE_LAST_RUN = 'ConnectResellerPriceSyncLastRun';
    public const KEY_PRICE_CURSOR = 'ConnectResellerPriceSyncCursor';

    /** @var CronStateStore */
    private $store;

    /** @var callable */
    private $clock;

    /** @var callable */
    private $logger;

    /**
     * @param CronStateStore|null $store
     * @param callable|null $clock
     * @param callable|null $logger
     */
    public function __construct($store = null, $clock = null, $logger = null)
    {
        if ($store instanceof CronStateStore) {
            $this->store = $store;
        } else {
            $this->store = new CapsuleCronStore();
        }

        $this->clock = is_callable($clock)
            ? $clock
            : function () {
                return microtime(true);
            };

        $this->logger = is_callable($logger)
            ? $logger
            : function ($message) {
                if (function_exists('logActivity')) {
                    \logActivity($message);
                }
            };
    }

    /**
     * @return float
     */
    public function now()
    {
        return (float) call_user_func($this->clock);
    }

    /**
     * @param string $key
     * @return string|null
     */
    public function get($key)
    {
        return $this->store->get($key);
    }

    /**
     * @param string $key
     * @param string $value
     * @return void
     */
    public function put($key, $value)
    {
        $this->store->put($key, $value);
    }

    /**
     * @param string $key
     * @return void
     */
    public function forget($key)
    {
        $this->store->forget($key);
    }

    /**
     * Non-blocking lock. Expired locks (TTL, default 1 hour) are stealable.
     *
     * @param string $key
     * @param int|null $ttl
     * @return bool
     */
    public function acquireLock($key, $ttl = null)
    {
        $ttl = $ttl === null ? self::LOCK_TTL_SECONDS : (int) $ttl;
        $now = (int) $this->now();
        $current = $this->store->get($key);
        if ($current !== null && $current !== '' && (int) $current > $now) {
            return false;
        }

        return $this->store->compareAndSet($key, $current, (string) ($now + $ttl));
    }

    /**
     * @param string $key
     * @return void
     */
    public function releaseLock($key)
    {
        $this->store->forget($key);
    }

    /**
     * Empty or non-numeric cron_frequency used to disable hook sync. Default 24.
     *
     * @param mixed $value
     * @return float
     */
    public static function normalizeFrequencyHours($value)
    {
        if ($value === null || $value === '') {
            return (float) self::DEFAULT_FREQUENCY_HOURS;
        }
        if (!is_numeric($value)) {
            return (float) self::DEFAULT_FREQUENCY_HOURS;
        }
        $hours = (float) $value;
        if ($hours <= 0) {
            return (float) self::DEFAULT_FREQUENCY_HOURS;
        }

        return $hours;
    }

    /**
     * @param mixed $lastRunUnix
     * @param float $now
     * @param mixed $hours
     * @return bool
     */
    public static function frequencyElapsed($lastRunUnix, $now, $hours)
    {
        $last = (int) $lastRunUnix;
        if ($last <= 0) {
            return true;
        }
        $seconds = self::normalizeFrequencyHours($hours) * 3600;

        return ((float) $now - $last) >= $seconds;
    }

    /**
     * @param string|null $lastRunDay Y-m-d of last completed KYC pass
     * @param string $today
     * @param string|null $cursor
     * @return bool
     */
    public static function kycCompletedToday($lastRunDay, $today, $cursor)
    {
        if ($cursor !== null && $cursor !== '') {
            return false;
        }

        return $lastRunDay === $today;
    }

    /**
     * Unique client IDs from pending-domain rows. Does not scan tblclients.
     *
     * @param array<int, mixed> $pendingRows
     * @return array<int, int>
     */
    public static function kycClientIdsFromPending(array $pendingRows)
    {
        $ids = array();
        foreach ($pendingRows as $row) {
            $id = 0;
            if (is_array($row) && isset($row['client_id'])) {
                $id = (int) $row['client_id'];
            } elseif (is_object($row) && isset($row->client_id)) {
                $id = (int) $row->client_id;
            }
            if ($id > 0) {
                $ids[$id] = $id;
            }
        }

        return array_values($ids);
    }

    /**
     * Remaining pending-domain rows after a last-processed id cursor.
     *
     * @param array<int, mixed> $pendingRows
     * @param mixed $cursorId
     * @return array<int, mixed>
     */
    public static function pendingAfterCursor(array $pendingRows, $cursorId)
    {
        $cursorId = (int) $cursorId;
        $out = array();
        foreach ($pendingRows as $row) {
            $id = 0;
            if (is_array($row) && isset($row['id'])) {
                $id = (int) $row['id'];
            } elseif (is_object($row) && isset($row->id)) {
                $id = (int) $row->id;
            }
            if ($id > $cursorId) {
                $out[] = $row;
            }
        }

        return $out;
    }

    /**
     * @param mixed $country
     * @return bool
     */
    public static function clientNeedsKyc($country)
    {
        return strtoupper(trim((string) $country)) === 'IN';
    }

    /**
     * @param mixed $encryptedApiKey
     * @param callable|null $decrypt
     * @return bool
     */
    public static function registrarIsConfigured($encryptedApiKey, $decrypt = null)
    {
        if ($encryptedApiKey === null || $encryptedApiKey === '') {
            return false;
        }
        $plain = $encryptedApiKey;
        if (is_callable($decrypt)) {
            $plain = call_user_func($decrypt, $encryptedApiKey);
        }

        return trim((string) $plain) !== '';
    }

    /**
     * @param mixed $addonRowCount
     * @param mixed $apiKey
     * @return bool
     */
    public static function addonIsConfigured($addonRowCount, $apiKey)
    {
        if ((int) $addonRowCount < 1) {
            return false;
        }

        return trim((string) $apiKey) !== '';
    }

    /**
     * @param array<int, mixed> $items
     * @param int $cursor
     * @param int $size
     * @return array<int, mixed>
     */
    public static function sliceChunk(array $items, $cursor, $size)
    {
        $cursor = max(0, (int) $cursor);
        $size = max(1, (int) $size);

        return array_slice($items, $cursor, $size);
    }

    /**
     * Abort when this invocation has used ~20s, or PHP has under ~15s left.
     *
     * @param float $startedAt
     * @param float|null $requestStart
     * @param int|null $maxExecution
     * @return bool
     */
    public function shouldAbort($startedAt, $requestStart = null, $maxExecution = null)
    {
        $now = $this->now();
        if (($now - (float) $startedAt) >= self::TIME_BUDGET_SECONDS) {
            return true;
        }
        if ($maxExecution === null) {
            $maxExecution = (int) ini_get('max_execution_time');
        }
        if ((int) $maxExecution <= 0) {
            return false;
        }
        if ($requestStart === null) {
            if (isset($_SERVER['REQUEST_TIME_FLOAT'])) {
                $requestStart = (float) $_SERVER['REQUEST_TIME_FLOAT'];
            } elseif (isset($_SERVER['REQUEST_TIME'])) {
                $requestStart = (float) $_SERVER['REQUEST_TIME'];
            } else {
                $requestStart = (float) $startedAt;
            }
        }
        $remaining = (int) $maxExecution - ($now - (float) $requestStart);

        return $remaining < self::MIN_REMAINING_SECONDS;
    }

    /**
     * @param string $job
     * @param string $reason
     * @return string
     */
    public function skip($job, $reason)
    {
        $this->log('ConnectReseller ' . $job . ' skipped: ' . $reason);

        return 'skipped';
    }

    /**
     * @param string $message
     * @return void
     */
    public function log($message)
    {
        call_user_func($this->logger, $message);
    }
}

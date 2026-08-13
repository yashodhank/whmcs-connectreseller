<?php

namespace WHMCS\Module\Addon\ConnectReseller;

use WHMCS\Database\Capsule;
use WHMCS\Module\Registrar\ConnectReseller\CronGuard;

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

require_once __DIR__ . '/Helper.php';

class PriceSyncTask
{
    /**
     * Sync enabled TLD prices. Safe to call from AfterCronJob and from
     * crons/priceSync.php. Frequency uses a unix last-run stamp (not TIME).
     * Empty cron_frequency defaults to 24 hours. Chunked by domain pricing id.
     *
     * @param CronGuard|null $guard
     * @return string
     */
    public static function run($guard = null)
    {
        $guard = self::resolveGuard($guard);
        if (!$guard instanceof CronGuard) {
            if (function_exists('logActivity')) {
                logActivity('ConnectReseller price sync skipped: registrar CronGuard unavailable');
            }

            return 'skipped';
        }

        $helper = new Helper();

        if (!Capsule::schema()->hasTable('mod_cron_status') || !Capsule::schema()->hasTable('mod_domain_status')) {
            return $guard->skip('price sync', 'addon tables missing');
        }

        $addonRows = Capsule::table('tbladdonmodules')
            ->where('module', 'connect_reseller')
            ->count();
        $params = $helper->CredentialRegistrar();
        $apiKey = isset($params['APIKey']) ? $params['APIKey'] : '';
        if (!CronGuard::addonIsConfigured($addonRows, $apiKey)) {
            if ((int) $addonRows < 1) {
                return $guard->skip('price sync', 'addon disabled');
            }

            return $guard->skip('price sync', 'APIKey empty');
        }

        $cronSetHour = Capsule::table('tbladdonmodules')
            ->where('module', 'connect_reseller')
            ->where('setting', 'cron_frequency')
            ->value('value');
        $hours = CronGuard::normalizeFrequencyHours($cronSetHour);
        $cursorRaw = $guard->get(CronGuard::KEY_PRICE_CURSOR);
        $inProgress = $cursorRaw !== null && $cursorRaw !== '';
        $lastRun = (int) $guard->get(CronGuard::KEY_PRICE_LAST_RUN);

        // Frequency / idle check before taking the lock.
        if (!$inProgress && !CronGuard::frequencyElapsed($lastRun, $guard->now(), $hours)) {
            return $guard->skip('price sync', 'frequency not elapsed');
        }

        if (!$guard->acquireLock(CronGuard::LOCK_PRICE)) {
            return $guard->skip('price sync', 'lock held');
        }

        try {
            return self::runLocked($guard, $helper, $params, $inProgress, (int) $cursorRaw);
        } finally {
            $guard->releaseLock(CronGuard::LOCK_PRICE);
        }
    }

    /**
     * @param CronGuard|null $guard
     * @return CronGuard|null
     */
    private static function resolveGuard($guard)
    {
        if ($guard instanceof CronGuard) {
            return $guard;
        }

        $base = dirname(__DIR__, 3) . '/registrars/connectreseller/lib';
        $files = array(
            $base . '/CronStateStore.php',
            $base . '/CapsuleCronStore.php',
            $base . '/CronGuard.php',
        );
        foreach ($files as $file) {
            if (!is_readable($file)) {
                return null;
            }
        }
        foreach ($files as $file) {
            require_once $file;
        }

        return new CronGuard();
    }

    /**
     * @param CronGuard $guard
     * @param Helper $helper
     * @param array<string, mixed> $params
     * @param bool $inProgress
     * @param int $cursorDomainId
     * @return string
     */
    private static function runLocked(CronGuard $guard, Helper $helper, array $params, $inProgress, $cursorDomainId)
    {
        $byTld = self::loadTldMap($guard, $helper, $params, $inProgress);
        if ($byTld === null) {
            return 'error';
        }

        $allDomainList = $helper->fetch_table_record('tbldomainpricing', array(), '');
        $work = array();
        if (is_array($allDomainList) || is_object($allDomainList)) {
            foreach ($allDomainList as $tld) {
                $domainId = (int) $tld->id;
                $whmcsExtension = $tld->extension;
                $where = array('domain_id' => $domainId, 'extension' => $whmcsExtension);
                $status = $helper->fetch_table_record('mod_domain_status', $where, 'singleValue', 'status');
                if ($status == 'off' || !isset($byTld[$whmcsExtension])) {
                    continue;
                }
                $work[] = array(
                    'domain_id' => $domainId,
                    'tld' => $tld,
                    'products' => $byTld[$whmcsExtension],
                );
            }
        }

        usort($work, function ($a, $b) {
            return $a['domain_id'] - $b['domain_id'];
        });

        $remaining = CronGuard::workAfterDomainId($work, $cursorDomainId);
        $chunk = CronGuard::sliceChunk($remaining, 0, CronGuard::PRICE_CHUNK_SIZE);
        $started = $guard->now();
        $processed = 0;
        $lastId = $cursorDomainId;

        foreach ($chunk as $item) {
            if ($guard->shouldAbort($started)) {
                break;
            }
            $processed++;
            $lastId = (int) $item['domain_id'];
            $tld = $item['tld'];
            $products = $item['products'];
            $finalDomain = array(
                'tld' => $products->tld,
                'domainregister' => $products->registrationPrice,
                'domainrenew' => $products->renewalPrice,
                'domaintransfer' => $products->transferPrice,
                'currency_code' => $products->currencyCode,
                'min_period' => $products->minPeriod,
                'max_period' => $products->maxPeriod,
            );
            $tldsPrices = $helper->domainPrice($finalDomain, true);
            $updateproductprice = $helper->updateprice($products->currencyCode, $tld->id, $tldsPrices);
            if ($updateproductprice != 'success') {
                $guard->log('Error Occur ConnectReseller Cron error in update price');
            }
        }

        $hitBudget = $processed < count($chunk);
        if ($hitBudget || count($remaining) > $processed) {
            $guard->put(CronGuard::KEY_PRICE_CURSOR, (string) $lastId);
            $guard->log('ConnectReseller price sync paused at domain_id ' . $lastId);

            return 'in_progress';
        }

        $guard->forget(CronGuard::KEY_PRICE_CURSOR);
        $guard->forget(CronGuard::KEY_PRICE_TLD_CACHE);
        $guard->put(CronGuard::KEY_PRICE_LAST_RUN, (string) ((int) $guard->now()));

        $cronStatusTime = array(
            'status' => 'Completed',
            'time' => date('H:i:s', (int) $guard->now()),
        );
        $helper->insertUpdate('mod_cron_status', array('status' => 'Completed'), $cronStatusTime);

        return 'completed';
    }

    /**
     * @param CronGuard $guard
     * @param Helper $helper
     * @param array<string, mixed> $params
     * @param bool $inProgress
     * @return array<string, object>|null
     */
    private static function loadTldMap(CronGuard $guard, Helper $helper, array $params, $inProgress)
    {
        if ($inProgress) {
            $cached = $guard->get(CronGuard::KEY_PRICE_TLD_CACHE);
            if (is_string($cached) && $cached !== '') {
                $decoded = json_decode($cached);
                if (is_object($decoded) || is_array($decoded)) {
                    $byTld = array();
                    foreach ($decoded as $tld => $products) {
                        $byTld[$tld] = $products;
                    }

                    return $byTld;
                }
            }
        }

        $allApiTld = $helper->get('tldsync?APIKey=' . $params['APIKey'], array(), 'Get Domain List');
        if ($helper->isTldSyncError($allApiTld['result'])) {
            $guard->log('Error Occur ConnectReseller Cron ' . $helper->tldSyncErrorMessage($allApiTld['result']));

            return null;
        }

        $byTld = array();
        foreach ($helper->normalizeTldSyncList($allApiTld['result']) as $products) {
            $byTld[$products->tld] = $products;
        }

        $guard->put(CronGuard::KEY_PRICE_TLD_CACHE, json_encode($byTld));

        return $byTld;
    }
}

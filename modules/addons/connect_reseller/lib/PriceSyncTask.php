<?php

namespace WHMCS\Module\Addon\ConnectReseller;

use WHMCS\Database\Capsule;
use WHMCS\Module\Registrar\ConnectReseller\CronGuard;

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

require_once dirname(__DIR__, 3) . '/registrars/connectreseller/lib/CronStateStore.php';
require_once dirname(__DIR__, 3) . '/registrars/connectreseller/lib/CapsuleCronStore.php';
require_once dirname(__DIR__, 3) . '/registrars/connectreseller/lib/CronGuard.php';
require_once __DIR__ . '/Helper.php';

class PriceSyncTask
{
    /**
     * Sync enabled TLD prices. Safe to call from AfterCronJob and from
     * crons/priceSync.php. Frequency uses a unix last-run stamp (not TIME).
     * Empty cron_frequency defaults to 24 hours. Chunked so AfterCronJob
     * does not overrun PHP max_execution_time.
     *
     * @param CronGuard|null $guard
     * @return string
     */
    public static function run($guard = null)
    {
        if (!$guard instanceof CronGuard) {
            $guard = new CronGuard();
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

        if (!$guard->acquireLock(CronGuard::LOCK_PRICE)) {
            return $guard->skip('price sync', 'lock held');
        }

        try {
            return self::runLocked($guard, $helper, $params);
        } finally {
            $guard->releaseLock(CronGuard::LOCK_PRICE);
        }
    }

    /**
     * @param CronGuard $guard
     * @param Helper $helper
     * @param array<string, mixed> $params
     * @return string
     */
    private static function runLocked(CronGuard $guard, Helper $helper, array $params)
    {
        $cronSetHour = Capsule::table('tbladdonmodules')
            ->where('module', 'connect_reseller')
            ->where('setting', 'cron_frequency')
            ->value('value');
        $hours = CronGuard::normalizeFrequencyHours($cronSetHour);
        $cursorRaw = $guard->get(CronGuard::KEY_PRICE_CURSOR);
        $inProgress = $cursorRaw !== null && $cursorRaw !== '';
        $lastRun = (int) $guard->get(CronGuard::KEY_PRICE_LAST_RUN);

        if (!$inProgress && !CronGuard::frequencyElapsed($lastRun, $guard->now(), $hours)) {
            return $guard->skip('price sync', 'frequency not elapsed');
        }

        $allDomainList = $helper->fetch_table_record('tbldomainpricing', array(), '');
        $allApiTld = $helper->get('tldsync?APIKey=' . $params['APIKey'], array(), 'Get Domain List');

        if ($allApiTld['result']->statusCode) {
            $guard->log('Error Occur ConnectReseller Cron ' . $allApiTld['result']->responseText);

            return 'error';
        }

        $byTld = array();
        foreach ($allApiTld['result'] as $products) {
            if (isset($products->tld)) {
                $byTld[$products->tld] = $products;
            }
        }

        $work = array();
        if (is_array($allDomainList) || is_object($allDomainList)) {
            foreach ($allDomainList as $tld) {
                $domainId = $tld->id;
                $whmcsExtension = $tld->extension;
                $where = array('domain_id' => $domainId, 'extension' => $whmcsExtension);
                $status = $helper->fetch_table_record('mod_domain_status', $where, 'singleValue', 'status');
                if ($status == 'off' || !isset($byTld[$whmcsExtension])) {
                    continue;
                }
                $work[] = array(
                    'tld' => $tld,
                    'products' => $byTld[$whmcsExtension],
                );
            }
        }

        $cursor = (int) $cursorRaw;
        $chunk = CronGuard::sliceChunk($work, $cursor, CronGuard::PRICE_CHUNK_SIZE);
        $started = $guard->now();
        $processed = 0;

        foreach ($chunk as $item) {
            if ($guard->shouldAbort($started)) {
                break;
            }
            $processed++;
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

        $newCursor = $cursor + $processed;
        $hitBudget = $processed < count($chunk);
        if ($hitBudget || $newCursor < count($work)) {
            $guard->put(CronGuard::KEY_PRICE_CURSOR, (string) $newCursor);
            $guard->log('ConnectReseller price sync paused at cursor ' . $newCursor);

            return 'in_progress';
        }

        $guard->forget(CronGuard::KEY_PRICE_CURSOR);
        $guard->put(CronGuard::KEY_PRICE_LAST_RUN, (string) ((int) $guard->now()));

        $cronStatusTime = array(
            'status' => 'Completed',
            'time' => date('H:i:s', (int) $guard->now()),
        );
        $helper->insertUpdate('mod_cron_status', array('status' => 'Completed'), $cronStatusTime);

        return 'completed';
    }
}

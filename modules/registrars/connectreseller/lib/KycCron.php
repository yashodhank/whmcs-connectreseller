<?php

namespace WHMCS\Module\Registrar\ConnectReseller;

use WHMCS\Database\Capsule;

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

require_once __DIR__ . '/CronStateStore.php';
require_once __DIR__ . '/CapsuleCronStore.php';
require_once __DIR__ . '/CronGuard.php';

class KycCron
{
    /**
     * Send .in KYC mail and register pending domains after verification.
     * Scoped to mod_kycpending_domains. Chunked with a domain-row id cursor.
     * DailyCronJob starts/completes a day; AfterCronJob continues when a cursor
     * is set ($continueOnly = true).
     *
     * @param CronGuard|null $guard
     * @param bool $continueOnly
     * @return string
     */
    public static function run($guard = null, $continueOnly = false)
    {
        if (!$guard instanceof CronGuard) {
            $guard = new CronGuard();
        }

        if (!\function_exists('sendKYCverifyEmail') || !\function_exists('getRegistrantStatus')) {
            return $guard->skip('KYC cron', 'KYC helpers unavailable');
        }

        $today = date('Y-m-d', (int) $guard->now());
        $last = $guard->get(CronGuard::KEY_KYC_LAST_RUN);
        $cursorRaw = $guard->get(CronGuard::KEY_KYC_CURSOR);
        $inProgress = $cursorRaw !== null && $cursorRaw !== '';

        if ($continueOnly && !$inProgress) {
            return $guard->skip('KYC cron', 'no in-progress cursor');
        }

        if (CronGuard::kycCompletedToday($last, $today, $cursorRaw)) {
            return $guard->skip('KYC cron', 'already completed today');
        }

        $registrarRow = Capsule::table('tblregistrars')
            ->where('registrar', 'connectreseller')
            ->where('setting', 'APIKey')
            ->first();
        if ($registrarRow === null) {
            return $guard->skip('KYC cron', 'registrar inactive or missing');
        }
        $decrypt = \function_exists('decrypt') ? 'decrypt' : null;
        $encrypted = isset($registrarRow->value) ? $registrarRow->value : '';
        if (!CronGuard::registrarIsConfigured($encrypted, $decrypt)) {
            return $guard->skip('KYC cron', 'APIKey empty');
        }

        if (!$guard->acquireLock(CronGuard::LOCK_KYC)) {
            return $guard->skip('KYC cron', 'lock held');
        }

        try {
            return self::runLocked($guard, $today);
        } finally {
            $guard->releaseLock(CronGuard::LOCK_KYC);
        }
    }

    /**
     * @param CronGuard $guard
     * @param string $today
     * @return string
     */
    private static function runLocked(CronGuard $guard, $today)
    {
        if (\function_exists('connectreseller_ensureKycSchema')) {
            \connectreseller_ensureKycSchema();
        }

        $guard->log('KYC Verification Email Cron started on ' . date('Y-m-d H:i:s', (int) $guard->now()));

        $pendingRows = array();
        if (Capsule::schema()->hasTable('mod_kycpending_domains')) {
            $fetched = Capsule::table('mod_kycpending_domains')->orderBy('id')->get();
            foreach ($fetched as $row) {
                $pendingRows[] = $row;
            }
        }

        $cursorId = (int) $guard->get(CronGuard::KEY_KYC_CURSOR);
        $remaining = CronGuard::pendingAfterCursor($pendingRows, $cursorId);
        $chunk = CronGuard::sliceChunk($remaining, 0, CronGuard::KYC_CHUNK_SIZE);
        $started = $guard->now();
        $processed = 0;
        $lastId = $cursorId;
        $mailed = array();

        foreach ($chunk as $order) {
            if ($guard->shouldAbort($started)) {
                break;
            }
            $processed++;
            $lastId = isset($order->id) ? (int) $order->id : $lastId;

            $clientId = isset($order->client_id) ? (int) $order->client_id : 0;
            if ($clientId < 1) {
                continue;
            }

            $client = Capsule::table('tblclients')->where('id', $clientId)->first();
            if ($client === null || !CronGuard::clientNeedsKyc(isset($client->country) ? $client->country : '')) {
                continue;
            }

            if (!isset($mailed[$clientId])) {
                try {
                    $result = \sendKYCverifyEmail($clientId);
                    $mailed[$clientId] = true;
                    if (empty($result)) {
                        $guard->log(
                            "KYC email not sent for clientId {$clientId} (conditions not met or failed send)"
                        );
                    }
                } catch (\Exception $e) {
                    $guard->log("KYC send failed for clientId {$clientId}. Error: " . $e->getMessage());
                }
            }

            try {
                $kycStatus = \getRegistrantStatus($clientId);
                if (!empty($kycStatus['status']) && $kycStatus['status'] === 'Verified') {
                    $results = \localAPI('DomainRegister', array('domainid' => $order->domainid));
                    if (!isset($results['error'])) {
                        Capsule::table('mod_kycpending_domains')->where('id', $order->id)->delete();
                        $guard->log('Domain successfully registered after KYC: ' . $order->domainname);
                    } else {
                        $guard->log(
                            'Domain registration API error for ' . $order->domainname . ': ' . $results['error']
                        );
                    }
                }
            } catch (\Exception $e) {
                $guard->log(
                    'Domain Registration failed, Domain: ' . $order->domainname
                    . ', ClientId: ' . $order->client_id . '. Error: ' . $e->getMessage()
                );
            }
        }

        $hitBudget = $processed < count($chunk);
        if ($hitBudget || count($remaining) > $processed) {
            $guard->put(CronGuard::KEY_KYC_CURSOR, (string) $lastId);
            $guard->log('KYC Verification Email Cron paused at cursor ' . $lastId);

            return 'in_progress';
        }

        $guard->forget(CronGuard::KEY_KYC_CURSOR);
        $guard->put(CronGuard::KEY_KYC_LAST_RUN, $today);
        $guard->log('KYC Verification Email Cron completed on ' . date('Y-m-d H:i:s', (int) $guard->now()));

        return 'completed';
    }
}

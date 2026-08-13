<?php

namespace WHMCS\Module\Addon\ConnectReseller;

use WHMCS\Database\Capsule;

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

class PriceSyncTask
{
    /**
     * Sync enabled TLD prices. Safe to call from AfterCronJob and from
     * crons/priceSync.php; frequency is gated by the addon cron_frequency setting.
     *
     * @return string
     */
    public static function run()
    {
        $helper = new Helper();

        if (!Capsule::schema()->hasTable('mod_cron_status') || !Capsule::schema()->hasTable('mod_domain_status')) {
            return 'skipped';
        }

        $cronSetLastStatus = $helper->fetch_table_record('mod_cron_status', array(), 'singleRowData');
        $cronSetHour = Capsule::table('tbladdonmodules')
            ->where('module', 'connect_reseller')
            ->where('setting', 'cron_frequency')
            ->value('value');

        $cronFrequencyInSeconds = is_numeric($cronSetHour) ? ((float) $cronSetHour * 3600) : 0;
        if ($cronFrequencyInSeconds <= 0) {
            return 'skipped';
        }

        $lastTimestamp = 0;
        if (is_object($cronSetLastStatus) && !empty($cronSetLastStatus->time)) {
            $parsed = strtotime((string) $cronSetLastStatus->time);
            $lastTimestamp = $parsed !== false ? $parsed : 0;
        }

        $currentTimestamp = strtotime(date('H:i:s'));
        if ($currentTimestamp === false) {
            $currentTimestamp = time();
        }

        if (($currentTimestamp - $lastTimestamp) < $cronFrequencyInSeconds) {
            return 'skipped';
        }

        $allDomainList = $helper->fetch_table_record('tbldomainpricing', array(), '');
        $params = $helper->CredentialRegistrar();
        $allApiTld = $helper->get('tldsync?APIKey=' . $params['APIKey'], array(), 'Get Domain List');

        if ($allApiTld['result']->statusCode) {
            \logActivity('Error Occur ConnectReseller Cron ' . $allApiTld['result']->responseText);

            return 'error';
        }

        $byTld = array();
        foreach ($allApiTld['result'] as $products) {
            if (isset($products->tld)) {
                $byTld[$products->tld] = $products;
            }
        }

        foreach ($allDomainList as $tld) {
            $domainId = $tld->id;
            $whmcsExtension = $tld->extension;
            $where = array('domain_id' => $domainId, 'extension' => $whmcsExtension);
            $status = $helper->fetch_table_record('mod_domain_status', $where, 'singleValue', 'status');
            if ($status == 'off' || !isset($byTld[$whmcsExtension])) {
                continue;
            }
            $products = $byTld[$whmcsExtension];
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
            $updateproductprice = $helper->updateprice($products->currencyCode, $domainId, $tldsPrices);
            if ($updateproductprice != 'success') {
                \logActivity('Error Occur ConnectReseller Cron error in update price');
            }
        }

        $cronStatusTime = array(
            'status' => 'Completed',
            'time' => date('H:i:s'),
        );
        $helper->insertUpdate('mod_cron_status', array('status' => 'Completed'), $cronStatusTime);

        return 'completed';
    }
}

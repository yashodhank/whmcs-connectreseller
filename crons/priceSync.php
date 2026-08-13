<?php

$whmcspath = "";

if (file_exists(dirname(__FILE__) . "/config.php"))
    require_once dirname(__FILE__) . "/config.php";

if (!empty($whmcspath)) {
    require_once $whmcspath . "/init.php";
} else {
    require(__DIR__ . "/../init.php");
}

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

use WHMCS\Database\Capsule;
use WHMCS\Module\Addon\ConnectReseller\Helper;

try {
    global $CONFIG;

    $helper = new Helper();
    $cronSetLastStatus = $helper->fetch_table_record('mod_cron_status', [], 'singleRowData');

    $cronSetHour = Capsule::table("tbladdonmodules")
        ->where('module', 'connect_reseller')
        ->where('setting', 'cron_frequency')
        ->value('value');

    $lastTimestamp = strtotime($cronSetLastStatus->time);
    $currentTimestamp = strtotime(date('H:i:s'));

    $timeDifference = $currentTimestamp - $lastTimestamp;
    $cronFrequencyInSeconds = $cronSetHour * 3600;

    if ($timeDifference >= $cronFrequencyInSeconds) {

        $allDomainList = $helper->fetch_table_record("tbldomainpricing", [], "");

        foreach ($allDomainList as $key => $tld) {

            $domainId = $tld->id;
            $whmcsExtension = $tld->extension;

            $where = ['domain_id' => $domainId, "extension" => $whmcsExtension];
            $status = $helper->fetch_table_record('mod_domain_status', $where, 'singleValue', 'status');

            if ($status == "off") {
                continue;
            }

            $params = $helper->CredentialRegistrar();
            $allApiTld = $helper->get("tldsync?APIKey=" . $params['APIKey'], [], "Get Domain List");

            if ($allApiTld['result']->statusCode) {
                logActivity("Error Occur ConnectReseller Cron " . $allApiTld['result']->responseText);
            }

            $price = '';
            foreach ($allApiTld['result'] as $key => $products) {

                if ($whmcsExtension == $products->tld) {
                    $finalDomain = [];
                    $finalDomain = [
                        'tld' => $products->tld,
                        'domainregister' => $products->registrationPrice,
                        'domainrenew' => $products->renewalPrice,
                        'domaintransfer' => $products->transferPrice,
                        'currency_code' => $products->currencyCode,
                        'min_period' => $products->minPeriod,
                        'max_period' => $products->maxPeriod,
                    ];

                    $tldsPrices = $helper->domainPrice($finalDomain, true);

                    $updateproductprice = $helper->updateprice($products->currencyCode, $domainId, $tldsPrices);

                    if ($updateproductprice != 'success') {
                        logActivity("Error Occur ConnectReseller Cron error in update price");
                    }
                }
            }
        }

        $cronStatusTime = [
            "status" => "Completed",
            "time" => date('H:i:s'),
        ];
        $helper->insertUpdate('mod_cron_status', ['status' => "Completed"], $cronStatusTime);
    }
} catch (\Exception $e) {
    logActivity("Error Occur ConnectReseller Cron" . $e->getMessage());
}

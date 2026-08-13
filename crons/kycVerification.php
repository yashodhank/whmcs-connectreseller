<?php

// Optional fallback. Default install uses WHMCS system cron (DailyCronJob) only.

$whmcspath = '';

if (file_exists(dirname(__FILE__) . '/config.php')) {
    require_once dirname(__FILE__) . '/config.php';
}

if (!empty($whmcspath)) {
    require_once $whmcspath . '/init.php';
    $hooks = $whmcspath . '/modules/registrars/connectreseller/hooks.php';
} else {
    require dirname(__DIR__) . '/init.php';
    $hooks = dirname(__DIR__) . '/modules/registrars/connectreseller/hooks.php';
}

if (file_exists($hooks)) {
    require_once $hooks;
} else {
    logActivity('KYC Cron error, File (/modules/registrars/connectreseller/hooks.php) not found');
}

use WHMCS\Module\Registrar\ConnectReseller\KycCron;

try {
    KycCron::run();
} catch (\Exception $e) {
    logActivity('Exception in KYC Verification Email Cron: ' . $e->getMessage());
}

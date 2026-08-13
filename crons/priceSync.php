<?php

$whmcspath = '';

if (file_exists(dirname(__FILE__) . '/config.php')) {
    require_once dirname(__FILE__) . '/config.php';
}

if (!empty($whmcspath)) {
    require_once $whmcspath . '/init.php';
} else {
    require dirname(__DIR__) . '/init.php';
}

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

use WHMCS\Module\Addon\ConnectReseller\PriceSyncTask;

try {
    PriceSyncTask::run();
} catch (\Exception $e) {
    logActivity('Error Occur ConnectReseller Cron' . $e->getMessage());
}

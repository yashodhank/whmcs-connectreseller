<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

require_once __DIR__ . '/lib/PriceSyncTask.php';

use WHMCS\Module\Addon\ConnectReseller\PriceSyncTask;

add_hook('AfterCronJob', 1, function () {
    try {
        PriceSyncTask::run();
    } catch (\Exception $e) {
        logActivity('ConnectReseller price sync via WHMCS cron failed: ' . $e->getMessage());
    }
});

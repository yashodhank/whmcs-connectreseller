<?php

use WHMCS\Module\Addon\ConnectReseller\Helper;
use WHMCS\Module\Addon\ConnectReseller\Admin\AdminDispatcher;
use WHMCS\Database\Capsule;

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}
/* config */

function connect_reseller_config()
{
    global $CONFIG;

    /* adding lang file according to whmcs default language */
    $language = $CONFIG['Language'];

    $langfilename = __DIR__ . '/lang/' . $language . '.php';
    if (file_exists($langfilename)) {
        require($langfilename);
    } else {
        require(__DIR__ . '/lang/english.php');
    }

    $lang = $_ADDONLANG;
    return [
        'name' => $lang["addon_name"],
        'description' =>  $lang["addon_desc"],
        'author' => '<a href="https://www.connectreseller.com/" target="_blank"><img src="/modules/addons/connect_reseller/assets/images/logo.svg" alt="ConnectReseller"  width="150"></a>',
        'language' => 'english',
        'version' => '3.0.0',
        'fields' => [
            'margin' => [
                'FriendlyName' => 'Domain Margin',
                'Type' => 'text',
                'Size' => '35',
                'Default' => '',
                'Description' => 'Enter the margin for the Domain here as a percentage.'
            ],
            'cron_frequency' => [
                'FriendlyName' => 'Cron Frequency',
                'Type' => 'text',
                'Size' => '3',
                'Default' => '24',
                'Description' => '(Hours) How often WHMCS system cron may sync TLD prices. Defaults to 24 if empty. No separate crontab is required when the addon is active.',
            ],
            'delete_db' => [
                'FriendlyName' => 'Delete Database Table',
                'Type' => 'yesno',
                'Description' => 'Tick this box to delete the addon module database table when deactivating the module.',
            ],
        ]
    ];
}
function connect_reseller_activate()
{
    try {
        $helper = new Helper();
        $helper->cerateTable();
        return [
            'status' => 'success',
            'description' => 'This is a Reseller module Activated Successfully. ',
        ];
    } catch (\Exception $e) {
        return [

            'status' => "error",
            'description' => 'Unable to : module Activated' . $e->getMessage(),
        ];
    }
}
function connect_reseller_deactivate()
{
    try {
        $helper = new Helper();
        $helper->dropTbale();
        return [
            'status' => 'success',
            'description' => 'This module table created successfully',
        ];
    } catch (\Exception $e) {
        return ['status' => "error", 'description' => 'Unable to deactivate module: ' . $e->getMessage(),];
    }
}
function connect_reseller_output($vars)
{
    try {
        $helper = new Helper($vars);
        $whmcs = WHMCS\Application::getInstance();
        $action = !empty($whmcs->get_req_var("action")) ? $whmcs->get_req_var("action") : 'domainsync';
        $dispatcher = new AdminDispatcher($vars);
        $dispatcher->dispatch($action, $vars);
    } catch (\Exception $e) {
        return ['status' => "error", 'description' => 'Unable to Showing addon module: ' . $e->getMessage(),];
    }
}

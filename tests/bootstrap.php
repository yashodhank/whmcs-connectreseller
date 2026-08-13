<?php

declare(strict_types=1);

if (!defined('WHMCS')) {
    define('WHMCS', true);
}

if (!function_exists('logModuleCall')) {
    /**
     * @param mixed $request
     * @param mixed $response
     */
    function logModuleCall($module, $action, $request, $response = null)
    {
        $GLOBALS['connectreseller_last_log'] = array(
            'module' => $module,
            'action' => $action,
            'request' => $request,
            'response' => $response,
        );
    }
}

require_once dirname(__DIR__) . '/vendor/autoload.php';

<?php

namespace WHMCS\Module\Addon\ConnectReseller\Admin;

use WHMCS\Module\Addon\ConnectReseller\Admin\Controller;

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}
class AdminDispatcher
{
    public function dispatch($action, $parameters)
    {
        $controller = new Controller($parameters);
        if (is_callable(array($controller, $action))) {
            return $controller->$action($parameters);
        }
        return '<p>Invalid action requested. Please go back and try again.</p>';
    }
}

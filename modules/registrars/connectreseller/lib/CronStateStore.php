<?php

namespace WHMCS\Module\Registrar\ConnectReseller;

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

interface CronStateStore
{
    /**
     * @param string $key
     * @return string|null
     */
    public function get($key);

    /**
     * @param string $key
     * @param string $value
     * @return void
     */
    public function put($key, $value);

    /**
     * Write $value only when the stored value is identical to $expected (null if missing).
     *
     * @param string $key
     * @param string|null $expected
     * @param string $value
     * @return bool
     */
    public function compareAndSet($key, $expected, $value);

    /**
     * @param string $key
     * @return void
     */
    public function forget($key);
}

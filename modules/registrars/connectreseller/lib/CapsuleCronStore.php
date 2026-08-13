<?php

namespace WHMCS\Module\Registrar\ConnectReseller;

use WHMCS\Database\Capsule;

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

class CapsuleCronStore implements CronStateStore
{
    /**
     * @param string $key
     * @return string|null
     */
    public function get($key)
    {
        $value = Capsule::table('tblconfiguration')
            ->where('setting', $key)
            ->value('value');
        if ($value === null) {
            return null;
        }

        return (string) $value;
    }

    /**
     * @param string $key
     * @param string $value
     * @return void
     */
    public function put($key, $value)
    {
        $current = $this->get($key);
        if ($current === null) {
            Capsule::table('tblconfiguration')->insert(array(
                'setting' => $key,
                'value' => $value,
            ));

            return;
        }

        Capsule::table('tblconfiguration')
            ->where('setting', $key)
            ->update(array('value' => $value));
    }

    /**
     * @param string $key
     * @param string|null $expected
     * @param string $value
     * @return bool
     */
    public function compareAndSet($key, $expected, $value)
    {
        if ($expected === null) {
            try {
                Capsule::table('tblconfiguration')->insert(array(
                    'setting' => $key,
                    'value' => $value,
                ));

                return true;
            } catch (\Throwable $e) {
                return false;
            }
        }

        $affected = Capsule::table('tblconfiguration')
            ->where('setting', $key)
            ->where('value', $expected)
            ->update(array('value' => $value));

        return (int) $affected > 0;
    }

    /**
     * @param string $key
     * @return void
     */
    public function forget($key)
    {
        Capsule::table('tblconfiguration')->where('setting', $key)->delete();
    }
}

<?php

namespace WHMCS\Module\Addon\ConnectReseller;

use \WHMCS\Database\Capsule;

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

class Schema
{
    static function CreateDBTable()
    {
        try {
            if (!Capsule::schema()->hasTable('mod_domain_status')) {
                Capsule::schema()
                    ->create(
                        'mod_domain_status',
                        function ($table) {
                            $table->increments('id');
                            $table->integer('domain_id');
                            $table->string('extension');
                            $table->enum('status', ['on', 'off'])->default('off');
                        }
                    );
            }

            if (!Capsule::schema()->hasTable('mod_cron_status')) {
                Capsule::schema()
                    ->create(
                        'mod_cron_status',
                        function ($table) {
                            $table->increments('id');
                            $table->string('status');
                            $table->time('time');
                        }
                    );
            }
        } catch (\Exception $e) {
            return [
                'status' => "error",
                'description' => 'Unable to : create all tables in ConnectReseller' . $e->getMessage(),
            ];
        }
    }
    static function DeleteAllDBTable()
    {
        try {
            $deleteDbTable = Capsule::table('tbladdonmodules')->where('module', 'connect_reseller')->where('setting', 'delete_db')->value('value');
            if ($deleteDbTable == 'on') {
                $tables = [
                    'mod_domain_status',
                    'mod_cron_status',
                ];
                foreach ($tables as $table) {
                    Capsule::schema()->dropIfExists($table);
                }
            }
        } catch (\Exception $e) {
            return [
                'status' => "error",
                'description' => 'Unable to : Delete All DBTable in ConnectReseller' . $e->getMessage(),
            ];
        }
    }
}

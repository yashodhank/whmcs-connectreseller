<?php

/**
 * Runs registrar hooks.php in a fresh process with a fake Capsule and a recording add_hook.
 * Usage: php kyc_hook_harness.php <scenario-json>; prints a JSON result.
 * scenario: {"domains": {id: {registrar, userid}}, "clients": {id: {country, email}}, "hook": name, "vars": {...}}
 */

namespace WHMCS\Database {
    require_once __DIR__ . '/FakeCapsuleQuery.php';

    final class Capsule
    {
        /** @var array<string, array<int, array<string, mixed>>> */
        public static $tables = array();
        /** @var list<string> */
        public static $log = array();

        public static function table($name)
        {
            return new FakeQuery($name);
        }

        public static function schema()
        {
            self::$log[] = 'schema';
            return new class {
                public function hasTable($t)
                {
                    return true;
                }
                public function create($t, $cb)
                {
                }
            };
        }
    }

}

namespace {
    define('WHMCS', true);
    $GLOBALS['hooks'] = array();
    function add_hook($name, $prio, $fn)
    {
        $GLOBALS['hooks'][$name][] = $fn;
    }
    function logActivity($msg)
    {
        \WHMCS\Database\Capsule::$log[] = 'activity:' . $msg;
    }
    function logModuleCall($module, $action, $request, $response = null)
    {
    }

    $scenario = json_decode((string) ($_SERVER['argv'][1] ?? '{}'), true);
    \WHMCS\Database\Capsule::$tables = array(
        'tbldomains' => $scenario['domains'] ?? array(),
        'tblclients' => $scenario['clients'] ?? array(),
    );
    require dirname(__DIR__, 2) . '/modules/registrars/connectreseller/hooks.php';
    \WHMCS\Database\Capsule::$log = array();

    $ret = null;
    foreach ($GLOBALS['hooks'][$scenario['hook']] ?? array() as $fn) {
        $ret = $fn($scenario['vars']);
    }
    echo json_encode(array(
        'return' => $ret,
        'log' => \WHMCS\Database\Capsule::$log,
        'usesRegistrar' => array_map('connectreseller_domainUsesRegistrar', $scenario['probe'] ?? array()),
    ));
}

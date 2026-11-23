<?php
/**
 * Runs registrar hooks.php in a fresh process with a fake Capsule and a recording add_hook.
 * Usage: php kyc_hook_harness.php <scenario-json>; prints a JSON result.
 * scenario: {"domains": {id: {registrar, userid}}, "clients": {id: {country, email}}, "hook": name, "vars": {...}}
 */
namespace WHMCS\Database {
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
                public function hasTable($t) { return true; }
                public function create($t, $cb) {}
            };
        }
    }

    final class FakeQuery
    {
        private $name;
        private $wheres = array();

        public function __construct($name) { $this->name = $name; }

        public function where($col, $op = null, $val = null)
        {
            if (func_num_args() === 2) { $val = $op; $op = '='; }
            $this->wheres[] = array($col, $op, $val);
            return $this;
        }

        private function rows()
        {
            Capsule::$log[] = $this->name . ':' . json_encode($this->wheres);
            $out = array();
            foreach (Capsule::$tables[$this->name] ?? array() as $id => $row) {
                $row = array('id' => $id) + $row;
                foreach ($this->wheres as $w) {
                    if ($w[1] === '=' && (string) ($row[$w[0]] ?? '') !== (string) $w[2]) { continue 2; }
                }
                $out[] = (object) $row;
            }
            return $out;
        }

        public function value($col) { $r = $this->rows(); return $r ? ($r[0]->$col ?? null) : null; }
        public function first() { $r = $this->rows(); return $r ? $r[0] : null; }
        public function get() { return $this->rows(); }
        public function exists() { return count($this->rows()) > 0; }
        public function count() { return count($this->rows()); }
        public function insert($row) { Capsule::$log[] = 'insert:' . $this->name; return true; }
    }
}

namespace {
    define('WHMCS', true);
    $GLOBALS['hooks'] = array();
    function add_hook($name, $prio, $fn) { $GLOBALS['hooks'][$name][] = $fn; }
    function logActivity($msg) { \WHMCS\Database\Capsule::$log[] = 'activity:' . $msg; }
    function logModuleCall() {}

    $scenario = json_decode($argv[1], true);
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

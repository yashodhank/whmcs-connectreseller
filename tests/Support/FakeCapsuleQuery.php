<?php

// Query builder half of the fake Capsule used by kyc_hook_harness.php.
namespace WHMCS\Database;

final class FakeQuery
{
    private $name;
    private $wheres = array();

    public function __construct($name)
    {
        $this->name = $name;
    }

    public function where($col, $op = null, $val = null)
    {
        if (func_num_args() === 2) {
            $val = $op;
            $op = '=';
        }
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
                if ($w[1] === '=' && (string) ($row[$w[0]] ?? '') !== (string) $w[2]) {
                    continue 2;
                }
            }
            $out[] = (object) $row;
        }
        return $out;
    }

    public function value($col)
    {
        $r = $this->rows();
        return $r ? ($r[0]->$col ?? null) : null;
    }
    public function first()
    {
        $r = $this->rows();
        return $r ? $r[0] : null;
    }
    public function get()
    {
        return $this->rows();
    }
    public function exists()
    {
        return count($this->rows()) > 0;
    }
    public function count()
    {
        return count($this->rows());
    }
    public function insert($row)
    {
        Capsule::$log[] = 'insert:' . $this->name;
        return true;
    }
}

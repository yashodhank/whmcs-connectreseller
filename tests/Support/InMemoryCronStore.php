<?php

declare(strict_types=1);

namespace ConnectReseller\Tests\Support;

use WHMCS\Module\Registrar\ConnectReseller\CronStateStore;

final class InMemoryCronStore implements CronStateStore
{
    /** @var array<string, string> */
    private $data;

    /**
     * @param array<string, string> $data
     */
    public function __construct(array $data = array())
    {
        $this->data = $data;
    }

    /**
     * @param string $key
     * @return string|null
     */
    public function get($key)
    {
        if (!array_key_exists($key, $this->data)) {
            return null;
        }

        return $this->data[$key];
    }

    /**
     * @param string $key
     * @param string $value
     * @return void
     */
    public function put($key, $value)
    {
        $this->data[$key] = $value;
    }

    /**
     * @param string $key
     * @param string|null $expected
     * @param string $value
     * @return bool
     */
    public function compareAndSet($key, $expected, $value)
    {
        $current = $this->get($key);
        if ($current !== $expected) {
            return false;
        }
        $this->data[$key] = $value;

        return true;
    }

    /**
     * @param string $key
     * @return void
     */
    public function forget($key)
    {
        unset($this->data[$key]);
    }

    /**
     * @return array<string, string>
     */
    public function all()
    {
        return $this->data;
    }
}

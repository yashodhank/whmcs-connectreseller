<?php

namespace WHMCS\Module\Registrar\ConnectReseller;

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

class ApiClient
{
    /** @var string */
    private $baseUrl = 'https://api.connectreseller.com/ConnectReseller/ESHOP/';

    /** @var callable|null */
    private $transport;

    /** @var int */
    private $lastHttpCode = 0;

    /**
     * @param callable|null $transport function(string $method, string $url, ?string $payload): string
     * @param string|null $baseUrl
     */
    public function __construct($transport = null, $baseUrl = null)
    {
        $this->transport = $transport;
        if (is_string($baseUrl) && $baseUrl !== '') {
            $this->baseUrl = rtrim($baseUrl, '/') . '/';
        }
    }

    /**
     * HTTP status from the most recent curl/transport call (0 if unknown).
     *
     * @return int
     */
    public function getLastHttpCode()
    {
        return (int) $this->lastHttpCode;
    }

    /**
     * @param array<string, mixed> $query
     * @return string
     */
    public function buildUrl($action, array $query = array())
    {
        $path = ltrim((string) $action, '/');
        $qs = http_build_query($query);

        return $this->baseUrl . $path . ($qs !== '' ? '?' . $qs : '');
    }

    /**
     * @param mixed $data
     * @return bool
     */
    public static function hasPayload($data)
    {
        return is_array($data) && count($data) > 0;
    }

    /**
     * @param mixed $json
     * @return array<string, mixed>
     */
    public static function decodeJson($json)
    {
        if (!is_string($json) || $json === '') {
            throw new \Exception('Empty JSON from ConnectReseller');
        }

        $decoded = json_decode($json, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Invalid JSON from ConnectReseller: ' . json_last_error_msg());
        }
        if (!is_array($decoded)) {
            throw new \Exception('ConnectReseller JSON was not an object or array');
        }

        return $decoded;
    }

    /**
     * @param mixed $data
     * @return array<string, mixed>
     */
    public function requestUrl($method, $url, $data = null, $action = '')
    {
        $url = Sensitive::normalizeUrl((string) $url);
        $payload = self::hasPayload($data) ? json_encode($data) : '';
        $raw = $this->dispatch($method, $url, $payload === false ? '' : $payload);
        $decoded = self::decodeJson($raw);

        $logRequest = empty($data) ? array('url' => $url) : $data;
        if (function_exists('logModuleCall')) {
            logModuleCall(
                'Connect Reseller',
                $action,
                Sensitive::redact($logRequest),
                Sensitive::redact($decoded)
            );
        }

        return array('result' => $decoded);
    }

    /**
     * @param array<string, mixed> $query
     * @return array<string, mixed>
     */
    public function get($action, array $query = array(), $logAction = '')
    {
        return $this->requestUrl('GET', $this->buildUrl($action, $query), null, $logAction);
    }

    /**
     * @param string $payload
     * @return string
     */
    private function dispatch($method, $url, $payload)
    {
        if (is_callable($this->transport)) {
            $this->lastHttpCode = 200;

            return (string) call_user_func($this->transport, $method, $url, $payload);
        }

        return $this->curlDispatch($method, $url, $payload);
    }

    /**
     * @param string $payload
     * @return string
     */
    private function curlDispatch($method, $url, $payload)
    {
        $curl = curl_init();
        $method = strtoupper($method);

        if ($method === 'POST') {
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $payload);
        } elseif ($method === 'PUT' || $method === 'DELETE') {
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $payload);
        } else {
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');
        }

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($curl, CURLOPT_MAXREDIRS, 10);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($curl, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($curl);
        if (curl_errno($curl)) {
            $error = curl_error($curl);
            curl_close($curl);
            throw new \Exception($error);
        }
        $this->lastHttpCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        return is_string($response) ? $response : '';
    }
}

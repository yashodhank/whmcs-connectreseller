<?php

namespace WHMCS\Module\Registrar\ConnectReseller;

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

class Sensitive
{
    /**
     * Cryptographically random password for ConnectReseller AddClient.
     * Never log the return value.
     *
     * @return string
     */
    public static function randomPassword()
    {
        return bin2hex(random_bytes(16));
    }

    /**
     * Redact API keys and passwords from values sent to logModuleCall.
     *
     * @param mixed $value
     * @return mixed
     */
    public static function redact($value)
    {
        if (is_array($value)) {
            $redacted = array();
            foreach ($value as $key => $item) {
                if (is_string($key) && preg_match('/api[_-]?key|password|authcode|eppcode/i', $key)) {
                    $redacted[$key] = '***';
                } else {
                    $redacted[$key] = self::redact($item);
                }
            }

            return $redacted;
        }

        if (!is_string($value)) {
            return $value;
        }

        $value = preg_replace('/(APIKey=)[^&]*/i', '$1***', $value);
        $value = preg_replace('/(Password=)[^&]*/i', '$1***', $value);
        $value = preg_replace('/(authCode=)[^&]*/i', '$1***', $value);

        return $value;
    }

    /**
     * Rebuild a URL query string with http_build_query.
     *
     * @param string $url
     * @return string
     */
    public static function normalizeUrl($url)
    {
        $parts = parse_url($url);
        if ($parts === false || !isset($parts['scheme'], $parts['host'])) {
            return $url;
        }

        $rebuilt = $parts['scheme'] . '://' . $parts['host'];
        if (isset($parts['port'])) {
            $rebuilt .= ':' . $parts['port'];
        }
        $rebuilt .= isset($parts['path']) ? $parts['path'] : '';

        if (isset($parts['query']) && $parts['query'] !== '') {
            $query = array();
            parse_str($parts['query'], $query);
            $rebuilt .= '?' . http_build_query($query);
        }

        return $rebuilt;
    }

    /**
     * @param mixed $value
     * @return string
     */
    public static function escapeHtml($value)
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

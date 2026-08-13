<?php

namespace WHMCS\Module\Registrar\ConnectReseller;

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

class ContractSupport
{
    /**
     * @param array<string, mixed> $result
     * @return array<string, mixed>
     */
    public static function interpretFundsResponse(array $result)
    {
        $msg = (isset($result['responseMsg']) && is_array($result['responseMsg']))
            ? $result['responseMsg']
            : array();
        $code = isset($msg['statusCode']) ? (int) $msg['statusCode'] : -1;
        $message = isset($msg['message']) ? (string) $msg['message'] : '';

        if ($code === 200 || $code === 0) {
            return array('success' => true);
        }

        if ($message !== '') {
            return array('error' => $message);
        }

        return array('error' => 'ConnectReseller connection test failed');
    }

    /**
     * @param array<string, mixed> $responseData
     * @param string $sld
     * @param string $tld
     * @return array<string, mixed>
     */
    public static function domainInformationFromView(array $responseData, $sld, $tld)
    {
        $expiryMs = isset($responseData['expirationDate']) ? $responseData['expirationDate'] : 0;
        $expiry = is_numeric($expiryMs) ? (int) floor(((float) $expiryMs) / 1000) : 0;
        $status = isset($responseData['status']) ? (string) $responseData['status'] : '';
        $protected = isset($responseData['isThiefProtected'])
            ? $responseData['isThiefProtected']
            : 'False';

        return array(
            'domain' => $sld . '.' . $tld,
            'nameservers' => DomainMapper::nameservers($responseData),
            'locked' => DomainMapper::lockStatus($protected) === 'locked',
            'expiry_timestamp' => $expiry,
            'status' => $status,
        );
    }

    /**
     * @param array<string, mixed> $info
     * @return mixed
     */
    public static function toWhmcsDomain(array $info)
    {
        if (!class_exists('\\WHMCS\\Domain\\Registrar\\Domain')) {
            return $info;
        }

        $domain = new \WHMCS\Domain\Registrar\Domain();
        if (method_exists($domain, 'setDomain')) {
            $domain->setDomain($info['domain']);
        }
        if (method_exists($domain, 'setNameservers')) {
            $domain->setNameservers($info['nameservers']);
        }
        if (method_exists($domain, 'setTransferLock')) {
            $domain->setTransferLock($info['locked']);
        }
        if ($info['expiry_timestamp'] > 0 && class_exists('\\WHMCS\\Carbon')) {
            $carbon = \WHMCS\Carbon::createFromTimestamp($info['expiry_timestamp']);
            if (method_exists($domain, 'setExpiryDate')) {
                $domain->setExpiryDate($carbon);
            }
        }

        return $domain;
    }
}

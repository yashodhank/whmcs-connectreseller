<?php

namespace WHMCS\Module\Registrar\ConnectReseller;

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

class DomainMapper
{
    /**
     * @param string $sld
     * @param string $tld
     * @return string
     */
    public static function websiteName($sld, $tld)
    {
        $domainname = $sld . '.' . $tld;
        if (function_exists('mb_check_encoding') && !mb_check_encoding($domainname, 'ASCII')) {
            return urlencode($domainname);
        }

        return $domainname;
    }

    /**
     * True when the domain is an .in name (not merely a TLD that ends with "in").
     *
     * @param string $domainName
     * @return bool
     */
    public static function isInDomain($domainName)
    {
        $domainName = strtolower((string) $domainName);
        if (substr($domainName, -3) === '.in') {
            return true;
        }

        return (bool) preg_match('/\.in\./', $domainName);
    }

    /**
     * @param array<string, mixed> $responseData
     * @return array<string, string>
     */
    public static function nameservers(array $responseData)
    {
        $values = array();
        for ($i = 1; $i <= 13; $i++) {
            $key = 'nameserver' . $i;
            $values['ns' . $i] = isset($responseData[$key]) ? (string) $responseData[$key] : '';
        }

        return $values;
    }

    /**
     * Build UpdateNameServer query for ns1–ns13 (WHMCS commonly supplies ns1–ns5).
     *
     * @param array<string, mixed> $params
     * @param string $apiKey
     * @param string $domainname
     * @param mixed $domainNameId
     * @return string
     */
    public static function nameserverUpdateQuery(array $params, $apiKey, $domainname, $domainNameId)
    {
        $query = 'APIKey=' . $apiKey . '&websiteName=' . $domainname . '&domainNameId=' . $domainNameId;
        for ($i = 1; $i <= 13; $i++) {
            $key = 'ns' . $i;
            if (!empty($params[$key])) {
                $query .= '&nameServer' . $i . '=' . $params[$key];
            }
        }

        return $query;
    }

    /**
     * Map WHMCS IDN language to V11 lang code. Falls back to the ISO code from
     * whmcsLangArray when provideLangArray has no TLD-specific entry (V11 §7.2).
     *
     * @param string $idnLanguage
     * @param string $tld
     * @param array<string, string> $whmcsArray
     * @param array<string, array<string, mixed>> $provideLang
     * @return string
     */
    public static function idnLanguageCode($idnLanguage, $tld, array $whmcsArray, array $provideLang)
    {
        $lang = '';
        foreach ($whmcsArray as $key => $whmcsval) {
            if ($whmcsval != $idnLanguage) {
                continue;
            }
            if (isset($provideLang[$key]['code'])) {
                $lang = $provideLang[$key]['code'];
            } else {
                $lang = strtolower((string) $whmcsval);
            }
            if ($tld == 'com' && $idnLanguage == 'kor') {
                $lang = 'KOR';
            }
        }
        if ($lang === '' && is_string($idnLanguage) && $idnLanguage !== '') {
            $lang = $idnLanguage;
        }

        return $lang;
    }

    /**
     * @param array<int, string> $domains
     * @return bool
     */
    public static function listHasInDomain(array $domains)
    {
        foreach ($domains as $domain) {
            if (self::isInDomain($domain)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param mixed $isThiefProtected
     * @return string
     */
    public static function lockStatus($isThiefProtected)
    {
        $value = is_bool($isThiefProtected)
            ? ($isThiefProtected ? 'True' : 'False')
            : (string) $isThiefProtected;

        return strcasecmp($value, 'True') === 0 ? 'locked' : 'unlocked';
    }

    /**
     * @param array<string, mixed> $responseData
     * @return array<string, string>
     */
    public static function contactFields(array $responseData)
    {
        $name = isset($responseData['name']) ? (string) $responseData['name'] : '';
        $email = isset($responseData['emailAddress']) ? (string) $responseData['emailAddress'] : '';
        $company = isset($responseData['companyName']) ? (string) $responseData['companyName'] : '';
        $address1 = isset($responseData['address1']) ? (string) $responseData['address1'] : '';
        $address2 = isset($responseData['address2']) ? (string) $responseData['address2'] : '';
        $address3 = isset($responseData['address3']) ? (string) $responseData['address3'] : '';
        $city = isset($responseData['city']) ? (string) $responseData['city'] : '';
        $state = isset($responseData['stateName']) ? (string) $responseData['stateName'] : '';
        $country = isset($responseData['countryName']) ? (string) $responseData['countryName'] : '';
        $postcode = isset($responseData['postalCode']) ? (string) $responseData['postalCode'] : '';
        $phoneCode = isset($responseData['phoneCode']) ? (string) $responseData['phoneCode'] : '';
        $phoneNo = isset($responseData['phoneNo']) ? (string) $responseData['phoneNo'] : '';

        return array(
            'Full Name' => $name,
            'Email' => $email,
            'Company Name' => $company,
            'Address 1' => $address1,
            'Address 2' => $address2,
            'Address 3' => $address3,
            'City' => $city,
            'State' => $state,
            'Country' => $country,
            'Postcode' => $postcode,
            'Phone Number' => $phoneCode . $phoneNo,
        );
    }
}

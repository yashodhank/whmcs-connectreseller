<?php

namespace WHMCS\Module\Registrar\ConnectReseller;

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

use WHMCS\Domains\DomainLookup\ResultsList;
use WHMCS\Domains\DomainLookup\SearchResult;
use WHMCS\Domain\TopLevel\ImportItem;

class Pricing
{
    /**
     * @param array<string, mixed> $params
     * @return mixed
     */
    public static function checkAvailability($params)
    {
            try {
                $helper = new Helper();

                $tldsToInclude = $params['tldsToInclude'];
                $premiumEnabled = (bool) $params['premiumEnabled'] == true ? 1 : 0;
                $sld = $params["sld"];
                $ApiKey = $params['APIKey'];
                $tldsToInclude = implode(",", $params['tldsToInclude']);
                $query = 'APIKey=' . $ApiKey . '&searchString=' . $sld . '&tldsInclude=' . $tldsToInclude . '&premiumEnable=' . $premiumEnabled;
                $viewDomainurl = "whmcscheckdomain/?" . $query;
                $viewDomainurl = trim($viewDomainurl);
                $viewDomainurl = str_replace(' ', '%20', $viewDomainurl);

                $response = $helper->get($viewDomainurl, [], "CheckAvailability");

                // if ($response['result']['responseMsg']['statusCode'] != 200) {
                //     $values = $helper->sendResponse($response['result']);
                //     return $values;
                // }

                $response = $response['result'];
                $results = new ResultsList();

                foreach ($response["responseData"] as $domain) {
                    $arr = explode(".", $domain['domain'], 2);
                    $searchResult = new SearchResult($arr[0], "." . $arr[1]);
                    if ($domain['status'] == 'available') {
                        $status = SearchResult::STATUS_NOT_REGISTERED;
                    } elseif ($domain['status'] == 'registered') {
                        $status = SearchResult::STATUS_REGISTERED;
                    } elseif ($domain['status'] == 'reserved') {
                        $status = SearchResult::STATUS_RESERVED;
                    } else {
                        $status = SearchResult::STATUS_TLD_NOT_SUPPORTED;
                    }
                    $searchResult->setStatus($status);
                    if ($params['premiumEnabled']) {
                        if ($domain['premium']) {
                            $searchResult->setPremiumDomain(true);
                            $searchResult->setPremiumCostPricing(
                                array(
                                    'register' => $domain['price'],
                                    'renew' => $domain['renewalPrice'],
                                    'CurrencyCode' => $domain['currencyCode'],
                                )
                            );
                        }
                    }
                    $results->append($searchResult);
                }
                return $results;
            } catch (\Exception $e) {
                return array(
                    'error' => $e->getMessage(),
                );
            }
    }

    /**
     * Map ESHOP domainSuggestion to WHMCS GetDomainSuggestions.
     *
     * @param array<string, mixed> $params
     * @return mixed
     */
    public static function getDomainSuggestions($params)
    {
        try {
            $helper = new Helper();
            $apiKey = isset($params['APIKey']) ? $params['APIKey'] : '';
            $keyword = '';
            if (!empty($params['searchTerm'])) {
                $keyword = (string) $params['searchTerm'];
            } elseif (!empty($params['sld'])) {
                $keyword = (string) $params['sld'];
            }
            if ($keyword === '' || $apiKey === '') {
                return new ResultsList();
            }

            $maxResult = 10;
            if (!empty($params['suggestionSettings']['maxResults'])) {
                $maxResult = (int) $params['suggestionSettings']['maxResults'];
            } elseif (!empty($params['maxResults'])) {
                $maxResult = (int) $params['maxResults'];
            }
            if ($maxResult < 1) {
                $maxResult = 10;
            }
            if ($maxResult > 50) {
                $maxResult = 50;
            }

            $query = 'APIKey=' . $apiKey
                . '&keyword=' . rawurlencode($keyword)
                . '&maxResult=' . $maxResult;
            $url = str_replace(' ', '%20', trim('domainSuggestion/?' . $query));
            $response = $helper->get($url, array(), 'GetDomainSuggestions');
            $result = isset($response['result']) ? $response['result'] : array();

            $list = self::extractSuggestionList($result);
            $allowedTlds = array();
            if (!empty($params['tldsToInclude']) && is_array($params['tldsToInclude'])) {
                foreach ($params['tldsToInclude'] as $tld) {
                    $allowedTlds[] = ltrim(strtolower((string) $tld), '.');
                }
            }

            $results = new ResultsList();
            foreach ($list as $domain) {
                if (!is_array($domain) || empty($domain['domainName'])) {
                    continue;
                }
                $full = (string) $domain['domainName'];
                $parts = explode('.', $full, 2);
                if (count($parts) < 2) {
                    continue;
                }
                $tld = strtolower($parts[1]);
                if ($allowedTlds && !in_array($tld, $allowedTlds, true)) {
                    continue;
                }
                $searchResult = new SearchResult($parts[0], '.' . $parts[1]);
                $searchResult->setStatus(SearchResult::STATUS_NOT_REGISTERED);
                if (!empty($params['premiumEnabled']) && isset($domain['price'])) {
                    $searchResult->setPremiumDomain(false);
                }
                $results->append($searchResult);
            }

            return $results;
        } catch (\Exception $e) {
            return array('error' => $e->getMessage());
        }
    }

    /**
     * @param array<string, mixed> $result
     * @return array<int, array<string, mixed>>
     */
    public static function extractSuggestionList(array $result)
    {
        if (isset($result['responseData']['registryDomainSuggestionList'])
            && is_array($result['responseData']['registryDomainSuggestionList'])
        ) {
            return $result['responseData']['registryDomainSuggestionList'];
        }
        if (isset($result['registryDomainSuggestionList'])
            && is_array($result['registryDomainSuggestionList'])
        ) {
            return $result['registryDomainSuggestionList'];
        }
        // Vendor PDF sometimes nests the list under responseMsg.
        if (isset($result['responseMsg']['registryDomainSuggestionList'])
            && is_array($result['responseMsg']['registryDomainSuggestionList'])
        ) {
            return $result['responseMsg']['registryDomainSuggestionList'];
        }

        return array();
    }

    /**
     * @param array<string, mixed> $params
     * @return mixed
     */
    public static function getTldPricing($params)
    {
            try {

                $helper = new Helper();
                $ApiKey = $params['APIKey'];
                $tldsyncurl = "tldsync/?APIKey=" . $ApiKey;
                $tldsyncurl = trim($tldsyncurl);
                $tldsyncurl = str_replace(' ', '%20', $tldsyncurl);

                $response = $helper->get($tldsyncurl, [], "GetTldPricing");

                if (isset($response['result']['statusCode']) && $response['result']['statusCode'] != 200) {
                    $values["error"] = "Error: " . $response['result']['responseText'];
                    return $values;
                }

                $response = $response['result'];
                $results = new ResultsList();

                foreach ($response as $extension) {
                    $item = (new ImportItem)
                        ->setExtension($extension['tld'])
                        ->setMinYears($extension['minPeriod'])
                        ->setMaxYears($extension['maxPeriod'])
                        ->setRegisterPrice($extension['registrationPrice'])
                        ->setRenewPrice($extension['renewalPrice'])
                        ->setTransferPrice($extension['transferPrice'])
                        ->setCurrency($extension['currencyCode']);

                    $results[] = $item;
                }
                return $results;
            } catch (\Exception $e) {
                return array(
                    'error' => $e->getMessage(),
                );
            }
    }
}

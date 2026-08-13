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

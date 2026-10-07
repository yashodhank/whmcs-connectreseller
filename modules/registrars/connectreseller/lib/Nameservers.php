<?php

namespace WHMCS\Module\Registrar\ConnectReseller;

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

class Nameservers
{
    /**
     * @param array<string, mixed> $params
     * @return mixed
     */
    public static function get($params)
    {
            try {
                $helper = new Helper();

                $domainname = DomainMapper::websiteName($params["sld"], $params["tld"]);



                $viewDomainurl = 'ViewDomain?APIKey=' . $params['APIKey'] . '&websiteName=' . $domainname;
                $viewDomainurl = str_replace(' ', '%20', $viewDomainurl);

                $response = $helper->get($viewDomainurl, [], "GetNameservers");
                if ($response['result']['responseMsg']['statusCode'] != 200) {
                    $values = $helper->sendResponse($response['result']);
                    return $values;
                }
                $response = $response['result'];
                $values = DomainMapper::nameservers($response["responseData"]);

                return $values;
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
    public static function save($params)
    {
            try {
                $helper = new Helper();

                $tld = $params["tld"];
                $sld = $params["sld"];
                $ApiKey = $params['APIKey'];
                $nameserver1 = $params["ns1"];
                $nameserver2 = $params["ns2"];
                $nameserver3 = $params["ns3"];
                $nameserver4 = $params["ns4"];
                $nameserver5 = $params["ns5"];
                $domainname = DomainMapper::websiteName($params["sld"], $params["tld"]);
                $viewDomainurl = "ViewDomain/?APIKey=" . $ApiKey . '&websiteName=' . $domainname;

                $res = $helper->get($viewDomainurl, [], "SaveNameservers ViewDomain");

                // Check for errors in the res
                if ($res['result']['responseMsg']['statusCode'] != 200) {
                    $values = $helper->sendResponse($res['result']);
                    return $values;
                }
                $res = $res['result'];

                $DomainNameID = $res["responseData"]['domainNameId'];
                if ($res["responseData"]['isDomainLocked'] != 'True') {
                    $query = DomainMapper::nameserverUpdateQuery($params, $ApiKey, $domainname, $DomainNameID);
                    $updateDomainurl = "UpdateNameServer/?" . $query;
                    $updateDomainurl = trim($updateDomainurl);
                    $updateDomainurl = str_replace(' ', '%20', $updateDomainurl);


                    $res = $helper->get($updateDomainurl, [], "SaveNameservers updateDomainurl");

                    if ($res['result']['responseMsg']['statusCode'] != 200) {
                        $values = $helper->sendResponse($res['result']);
                        return $values;
                    }

                    return array(
                        'success' => true,
                    );
                } else {
                    $values = $helper->sendResponse($res);
                }
                return $values;
            } catch (\Exception $e) {
                return array(
                    'error' => $e->getMessage(),
                );
            }
    }

    /**
     * Bulk nameserver update (ESHOP nameserverbulkaction, POST JSON).
     *
     * @param string $apiKey
     * @param array<int, string> $domainNames
     * @param array<string, string> $nameservers keys nameserver1..nameserver13
     * @param Helper|null $helper
     * @return array<string, mixed>
     */
    public static function bulkUpdate($apiKey, array $domainNames, array $nameservers, $helper = null)
    {
        try {
            $helper = $helper instanceof Helper ? $helper : new Helper();
            $domains = array();
            foreach ($domainNames as $name) {
                $name = trim((string) $name);
                if ($name !== '') {
                    $domains[] = $name;
                }
            }
            if ($apiKey === '' || count($domains) === 0) {
                return array('error' => 'API key and at least one domain are required');
            }

            $body = array('DomainNames' => $domains);
            $nsCount = 0;
            for ($i = 1; $i <= 13; $i++) {
                $key = 'nameserver' . $i;
                if (!empty($nameservers[$key])) {
                    $body[$key] = (string) $nameservers[$key];
                    $nsCount++;
                }
            }
            if ($nsCount < 2) {
                return array('error' => 'At least two nameservers are required');
            }

            $url = 'nameserverbulkaction?APIKey=' . rawurlencode((string) $apiKey);
            $res = $helper->post($url, $body, 'nameserverbulkaction');
            $result = isset($res['result']) ? $res['result'] : array();

            $statusCode = null;
            if (isset($result['statusCode'])) {
                $statusCode = (int) $result['statusCode'];
            } elseif (isset($result['responseMsg']['statusCode'])) {
                $statusCode = (int) $result['responseMsg']['statusCode'];
            }

            if ($statusCode !== null && $statusCode !== 200) {
                $message = isset($result['message'])
                    ? (string) $result['message']
                    : (isset($result['responseMsg']['message'])
                        ? (string) $result['responseMsg']['message']
                        : 'Bulk nameserver update failed');

                return array('error' => $message);
            }

            $message = isset($result['message'])
                ? (string) $result['message']
                : 'Bulk nameserver update accepted';

            return array('success' => true, 'message' => $message);
        } catch (\Exception $e) {
            return array('error' => $e->getMessage());
        }
    }

    /**
     * @param array<string, mixed> $params
     * @return mixed
     */
    public static function register($params)
    {
            try {
                $helper = new Helper();
                $tld = $params["tld"];
                $sld = $params["sld"];
                $ApiKey = $params['APIKey'];
                $websitename = $params['domainname'];
                $ipaddress = $params["ipaddress"];
                $Server = $params["nameserver"];
                $domainname = $params["sld"] . '.' . $params["tld"];
                if (!mb_check_encoding($domainname, 'ASCII')) {
                    $domainname = urlencode($domainname);
                }
                $query = 'APIKey=' . $ApiKey . '&websiteName=' . $domainname;
                $viewDomainurl = "ViewDomain/?" . $query;
                $viewDomainurl = trim($viewDomainurl);
                $viewDomainurl = str_replace(' ', '%20', $viewDomainurl);

                $res = $helper->get($viewDomainurl, [], "RegisterNameserver");

                if ($res['result']['responseMsg']['statusCode'] != 200) {
                    $values = $helper->sendResponse($res['result']);
                    return $values;
                }
                $res = $res['result'];

                $domainNameId = $res["responseData"]['domainNameId'];
                $query = 'APIKey=' . $ApiKey . '&websiteName=' . $domainname . '&domainNameId=' . $domainNameId . '&hostName=' . $Server . '&ipAddress=' . $ipaddress;
                $addChildUrl = "AddChildNameServer/?" . $query;
                $addChildUrl = trim($addChildUrl);
                $addChildUrl = str_replace(' ', '%20', $addChildUrl);

                $res = $helper->get($addChildUrl, [], "AddChildNameServer");

                // Check for errors in the res
                if ($res['result']['responseMsg']['statusCode'] != 200) {
                    $values = $helper->sendResponse($res['result']);
                    return $values;
                }
                return array(
                    'success' => 'success',
                );
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
    public static function modify($params)
    {
            try {
                $helper = new Helper();
                $tld = $params["tld"];
                $sld = $params["sld"];
                $ApiKey = $params['APIKey'];
                $websitename = $params['domainname'];
                $Server = $params["nameserver"];
                $currentipaddress = $params["currentipaddress"];
                $newipaddress = $params["newipaddress"];
                $domainname = $params["sld"] . '.' . $params["tld"];
                if (!mb_check_encoding($domainname, 'ASCII')) {
                    $domainname = urlencode($domainname);
                }
                $query = 'APIKey=' . $ApiKey . '&websiteName=' . $domainname;
                $viewDomainurl = "ViewDomain/?" . $query;
                $viewDomainurl = trim($viewDomainurl);
                $viewDomainurl = str_replace(' ', '%20', $viewDomainurl);

                $response = $helper->get($viewDomainurl, [], "ViewDomain");

                if ($response['result']['responseMsg']['statusCode'] != 200) {
                    $values = $helper->sendResponse($response['result']);
                    return $values;
                }
                $response = $response['result'];

                $domainNameId = $response["responseData"]['domainNameId'];
                $query = 'APIKey=' . $ApiKey . '&websiteName=' . $domainname . '&domainNameId=' . $domainNameId . '&hostName=' . $Server . '&oldIpAddress=' . $currentipaddress . '&newIpAddress=' . $newipaddress;
                $modifyChildUrl = "ModifyChildNameServerIP/?" . $query;
                $modifyChildUrl = trim($modifyChildUrl);
                $modifyChildUrl = str_replace(' ', '%20', $modifyChildUrl);

                $res = $helper->get($modifyChildUrl, [], "ModifyChildNameServerIP");

                if ($res['result']['responseMsg']['statusCode'] != 200) {
                    $values = $helper->sendResponse($res['result']);
                    return $values;
                }

                return array(
                    'success' => 'success',
                );
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
    public static function delete($params)
    {
            try {
                $helper = new Helper();
                $tld = $params["tld"];
                $sld = $params["sld"];
                $ApiKey = $params['APIKey'];
                $websitename = $params['domainname'];
                $Server = $params["nameserver"];
                $domainname = $params["sld"] . '.' . $params["tld"];
                if (!mb_check_encoding($domainname, 'ASCII')) {
                    $domainname = urlencode($domainname);
                }
                $viewDomainurl = "ViewDomain/?APIKey=" . $ApiKey . '&websiteName=' . $domainname;
                $viewDomainurl = trim($viewDomainurl);
                $viewDomainurl = str_replace(' ', '%20', $viewDomainurl);

                $response = $helper->get($viewDomainurl, [], "DeleteNameserver");

                // Check for errors in the res
                if ($response['result']['responseMsg']['statusCode'] != 200) {
                    $values = $helper->sendResponse($response['result']);
                    return $values;
                }
                $response = $response['result'];

                $domainNameId = $response["responseData"]['domainNameId'];

                $query = 'APIKey=' . $ApiKey . '&websiteName=' . $domainname . '&domainNameId=' . $domainNameId . '&hostName=' . $Server;
                $deleteChildUrl = "DeleteChildNameServer/?" . $query;
                $deleteChildUrl = trim($deleteChildUrl);
                $deleteChildUrl = str_replace(' ', '%20', $deleteChildUrl);

                $res = $helper->get($deleteChildUrl, [], "DeleteChildNameServer");

                // Check for errors in the res
                if ($res['result']['responseMsg']['statusCode'] != 200) {
                    $values = $helper->sendResponse($res['result']);
                    return $values;
                }

                return array(
                    'success' => 'success',
                );
            } catch (\Exception $e) {
                return array(
                    'error' => $e->getMessage(),
                );
            }
    }
}

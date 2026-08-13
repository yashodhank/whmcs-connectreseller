<?php

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

use WHMCS\Module\Registrar\ConnectReseller\Helper;
use WHMCS\Domains\DomainLookup\ResultsList;
use WHMCS\Domains\DomainLookup\SearchResult;
use WHMCS\Domain\TopLevel\ImportItem;
use WHMCS\Database\Capsule;	

ini_set("display_errors", "1");
$apiUrl = "https://api.connectreseller.com/ConnectReseller/";

function connectreseller_getConfigArray()
{
    $configarray = array(
        'APIKey' => array('Type' => "text", 'Size' => "20", 'Description' => "Enter your API key"),
        'BrandId' => array('Type' => "text", 'Size' => "20", 'Description' => " Enter your BrandId  "),
        'CouponCode' => array('Type' => "text", 'Size' => "20", 'Description' => " Enter your Coupon code  "),
    );
    return $configarray;
}
function connectreseller_MetaData()
{
    return array(
        'DisplayName' => 'connectreseller',
        'APIVersion' => '2.5.1',
        'NonLinearRegistrationPricing' => TRUE,
    );
}
function connectreseller_GetNameservers($params)
{
    try {
        $helper = new Helper();

        $domainname = $params["sld"] . '.' . $params["tld"];
        if (!mb_check_encoding($domainname, 'ASCII')) {

            $domainname = urlencode($domainname);
        }



        $viewDomainurl = 'ViewDomain?APIKey=' . $params['APIKey'] . '&websiteName=' . $domainname;
        $viewDomainurl = str_replace(' ', '%20', $viewDomainurl);

        $response = $helper->get($viewDomainurl, [], "GetNameservers");
        if ($response['result']['responseMsg']['statusCode'] != 200) {
            $values = $helper->sendResponse($response['result']);
            return $values;
        }
        $response = $response['result'];

        $values["ns1"] = $response["responseData"]['nameserver1'];
        $values["ns2"] = $response["responseData"]['nameserver2'];
        $values["ns3"] = $response["responseData"]['nameserver3'];
        $values["ns4"] = $response["responseData"]['nameserver4'];
        $values["ns5"] = $response["responseData"]['nameserver5'];
        $values["ns6"] = $response["responseData"]['nameserver6'];
        $values["ns7"] = $response["responseData"]['nameserver7'];
        $values["ns8"] = $response["responseData"]['nameserver8'];
        $values["ns9"] = $response["responseData"]['nameserver9'];
        $values["ns10"] = $response["responseData"]['nameserver10'];
        $values["ns11"] = $response["responseData"]['nameserver11'];
        $values["ns12"] = $response["responseData"]['nameserver12'];
        $values["ns13"] = $response["responseData"]['nameserver13'];

        return $values;
    } catch (\Exception $e) {
        return array(
            'error' => $e->getMessage(),
        );
    }
}
function connectreseller_SaveNameservers($params)
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
        $domainname = $params["sld"] . '.' . $params["tld"];
        if (!mb_check_encoding($domainname, 'ASCII')) {

            $domainname = urlencode($domainname);
        }
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
            $query = 'APIKey=' . $ApiKey . '&websiteName=' . $domainname . '&domainNameId=' . $DomainNameID;
            if ($nameserver1 != "") $query .= '&nameServer1=' . $nameserver1;
            if ($nameserver2 != "") $query .= '&nameServer2=' . $nameserver2;
            if ($nameserver3 != "") $query .= '&nameServer3=' . $nameserver3;
            if ($nameserver4 != "") $query .= '&nameServer4=' . $nameserver4;
            if ($nameserver5 != "") $query .= '&nameServer5=' . $nameserver5;
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
function connectreseller_GetRegistrarLock($params)
{
    try {
        $helper = new Helper();

        $tld = $params["tld"];
        $sld = $params["sld"];
        $ApiKey = $params['APIKey'];

        $domainname = $params["sld"] . '.' . $params["tld"];
        if (!mb_check_encoding($domainname, 'ASCII')) {

            $domainname = urlencode($domainname);
        }

        $viewDomainurl = "ViewDomain/?APIKey=" . $ApiKey . '&websiteName=' . $domainname;
        $viewDomainurl = trim($viewDomainurl);
        $viewDomainurl = str_replace(' ', '%20', $viewDomainurl);

        $response = $helper->get($viewDomainurl, [], "GetRegistrarLock");

        if ($response['result']['responseMsg']['statusCode'] != 200) {
            $values = $helper->sendResponse($response['result']);
            return $values;
        }
        $response = $response['result'];

        if ($response["responseData"]['isThiefProtected'] == "True") {
            $lockstatus = "locked";
        } else {
            $lockstatus = "unlocked";
        }

        return $lockstatus;
    } catch (\Exception $e) {
        return array(
            'error' => $e->getMessage(),
        );
    }
}
function connectreseller_SaveRegistrarLock($params)
{
    try {
        $helper = new Helper();

        $tld = $params["tld"];
        $sld = $params["sld"];
        $ApiKey = $params['APIKey'];

        if ($params["lockenabled"] == 'unlocked') {
            $DomainLockStatus = 'false';
        } else {
            $DomainLockStatus = 'true';
        }
        $domainname = $params["sld"] . '.' . $params["tld"];
        if (!mb_check_encoding($domainname, 'ASCII')) {

            $domainname = urlencode($domainname);
        }

        $viewDomainurl = "ViewDomain/?APIKey=" . $ApiKey . '&websiteName=' . $domainname;

        $response = $helper->get($viewDomainurl, [], "SaveRegistrarLock ViewDomain");

        if ($response['result']['responseMsg']['statusCode'] != 200) {
            $values = $helper->sendResponse($response['result']);
            return $values;
        }
        $response = $response['result'];

        $domainNameId = $response["responseData"]['domainNameId'];
        $query = 'APIKey=' . $ApiKey . '&websiteName=' . $domainname . '&domainNameId=' . $domainNameId . '&isTheftProtection=' . $DomainLockStatus;
        $manageUrl = trim("ManageTheftProtection/?" . $query);

        $res = $helper->get($manageUrl, [], "SaveRegistrarLock ManageTheftProtection");

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
function connectreseller_GetDNS($params)
{
    try {
        $helper = new Helper();
        $tld = $params["tld"];
        $sld = $params["sld"];
        $ApiKey = $params['APIKey'];

        $domainname = $params["sld"] . '.' . $params["tld"];
        if (!mb_check_encoding($domainname, 'ASCII')) {

            $domainname = urlencode($domainname);
        }
        $viewDomainurl = "ViewDomain/?APIKey=" . $ApiKey . '&websiteName=' . $domainname;
        $viewDomainurl = trim($viewDomainurl);
        $viewDomainurl = str_replace(' ', '%20', $viewDomainurl);

        $res = $helper->get($viewDomainurl, [], "GetDNS ViewDomain");

        if ($res['result']['responseMsg']['statusCode'] != 200) {
            $values = $helper->sendResponse($res['result']);
            return $values;
        }
        $res = $res['result'];

        $websiteId = $res["responseData"]['websiteId'];

        if ($res["responseData"]['dnszoneStatus'] == "1") {
            $viewDnsUrl = trim("ViewDNSRecord/?APIKey=" . $ApiKey . '&WebsiteId=' . $websiteId);
            $viewDnsUrl = trim($viewDnsUrl);
            $viewDnsUrl = str_replace(' ', '%20', $viewDnsUrl);

            $res = $helper->get($viewDnsUrl, [], "GetDNS ViewDNSRecord");

            $viewDnsRes = $res['result'];

            if ($viewDnsRes["responseMsg"]['statusCode'] != '200') {
                $values = $helper->sendResponse($viewDnsRes);
            } else {
                $host = $viewDnsRes['responseData'];
                foreach ($host as $v) {
                    if (($v['recordType'] == 'SRV') || ($v['recordType'] == 'SOA')  || ($v['recordType'] == 'NS')) {
                    } else {
                        $values[] = array(
                            'hostname' => $v['recordName'],
                            'type'     => $v['recordType'],
                            'address'  => $v['recordContent'],
                            'priority' => $v['recordPriority'],
                            'recid' => $v['dnszoneRecordID']
                        );
                    }
                }
            }
        } else {
            $manageDnsUrl = "ManageDNSRecords/?APIKey=" . $ApiKey . '&WebsiteId=' . $websiteId;

            $res = $helper->get($manageDnsUrl, [], "GetDNS ManageDNSRecords");
            $manageDnsRes = $res['result'];
            if ($manageDnsRes["responseMsg"]['statusCode'] != '200') {
                $values = $helper->sendResponse($manageDnsRes);
            } else {
                $viewDnsUrl = trim("ViewDNSRecord/?APIKey=" . $ApiKey . '&WebsiteId=' . $websiteId);
                $viewDnsUrl = trim($viewDnsUrl);
                $viewDnsUrl = str_replace(' ', '%20', $viewDnsUrl);

                $res = $helper->get($viewDnsUrl, [], "GetDNS ViewDNSRecord");

                $viewDnsRes = $res['result'];

                if ($viewDnsRes["responseMsg"]['statusCode'] != '200') {
                    $values = $helper->sendResponse($viewDnsRes);
                } else {
                    $host = $viewDnsRes['responseData'];
                    foreach ($host as $v) {
                        if (($v['recordType'] == 'SRV') || ($v['recordType'] == 'SOA')  || ($v['recordType'] == 'NS')) {
                        } else {
                            $values[] = array(
                                'hostname' => $v['recordName'],
                                'type'     => $v['recordType'],
                                'address'  => $v['recordContent'],
                                'priority' => $v['recordPriority'],
                                'recid' => $v['dnszoneRecordID']
                            );
                        }
                    }
                }
            }
        }

        return $values;
    } catch (\Exception $e) {
        return array(
            'error' => $e->getMessage(),
        );
    }
}
function connectreseller_SaveDNS($params)
{
    try {
        $helper = new Helper();
        $sld = $params['sld'];
        $tld =  $params['tld'];
        $ApiKey = $params['APIKey'];
        $websitename = $sld . '.' . $tld;
        # Put your code to get the lock status here

        $domainname = $params["sld"] . '.' . $params["tld"];
        if (!mb_check_encoding($domainname, 'ASCII')) {

            $domainname = urlencode($domainname);
        }
        $viewDomainurl = "ViewDomain/?APIKey=" . $ApiKey . '&websiteName=' . $domainname;
        $viewDomainurl = trim($viewDomainurl);
        $viewDomainurl = str_replace(' ', '%20', $viewDomainurl);

        $res = $helper->get($viewDomainurl, [], "SaveDNS");

        // Check for errors in the res
        if ($res['result']['responseMsg']['statusCode'] != 200) {
            $values = $helper->sendResponse($res['result']);
            return $values;
        }
        $res = $res['result'];

        $websiteId = $res["responseData"]['websiteId'];

        if ($res["responseData"]['dnszoneStatus'] == "1") {
            $DNSZoneId = $res["responseData"]['dnszoneId'];
            $viewDnsUrl = "ViewDNSRecord/?APIKey=" . $ApiKey . '&WebsiteId=' . $websiteId;
            $viewDnsUrl = trim($viewDnsUrl);
            $viewDnsUrl = str_replace(' ', '%20', $viewDnsUrl);

            $res = $helper->get($viewDnsUrl, [], "ViewDNSRecord");

            $viewDnsRes = $res['result'];

            if ($viewDnsRes["responseMsg"]['statusCode'] != '200') {
                $values = $helper->sendResponse($viewDnsRes);
            } else {
                $host = $viewDnsRes['responseData'];
                foreach ($params['dnsrecords'] as $k => $v) {
                    if (!empty($v['hostname'])  && !empty($v['address'])) {
                        if ($v['recid'] != "" && $v['recid'] != null) {
                            $key = array_search($v['recid'], array_column($host, 'dnszoneRecordID'));
                            if ($key != -1) {
                                $checkHost = $host[$key];

                                if (($v['hostname'] != $checkHost['recordName']) || ($v['type'] != $checkHost['recordType']) || ($v['address'] != $checkHost['recordContent']) || (($v['priority'] != $checkHost['recordPriority']) && $v['type'] == "MX")) {
                                    $hostName = $v['hostname'];
                                    if ($hostName == "@")
                                        $hostName = $websitename;
                                    else if ($hostName == "*")
                                        $hostName = "*." . $websitename;
                                    else if (strpos($hostName, $websitename) === false)
                                        $hostName = $hostName . "." . $websitename;
                                    $query = 'APIKey=' . $ApiKey . '&WebsiteId=' . $websiteId . '&DNSZoneID=' . $DNSZoneId . '&DNSZoneRecordID=' . $v['recid'] . '&RecordName=' . $hostName . '&RecordType=' . $v['type'] . '&RecordValue=' . $v['address'] . '&RecordTTL=43200';

                                    if ($v['type'] == "MX") {
                                        $query = $query . '&RecordPriority=' . $v['priority'];
                                    }
                                    $modifyDnsUrl = "ModifyDNSRecord/?" . $query;
                                    $modifyDnsUrl = trim($modifyDnsUrl);
                                    $modifyDnsUrl = str_replace(' ', '%20', $modifyDnsUrl);

                                    $res = $helper->get($modifyDnsUrl, [], "ModifyDNSRecord");

                                    if ($res['result']['responseMsg']['statusCode'] != 200) {
                                        $values = $helper->sendResponse($res['result']);
                                        return $values;
                                    }
                                }
                            }
                        } else {
                            $status = true;
                            $hostName = $v['hostname'];
                            if ($hostName == "@")
                                $hostName = $websitename;
                            else if ($hostName == "*")
                                $hostName = "*." . $websitename;
                            else if (strpos($hostName, $websitename) === false)
                                $hostName = $hostName . "." . $websitename;
                            $key1 = array_search($hostName, array_column($host, 'recordName'));
                            if ($key1 != -1) {
                                $checkHost1 = $host[$key1];
                                if ($v['address'] == $checkHost1['recordContent'])
                                    $status = false;
                            }
                            if ($status) {
                                $query = 'APIKey=' . $ApiKey . '&WebsiteId=' . $websiteId . '&DNSZoneID=' . $DNSZoneId . '&RecordName=' . $hostName . '&RecordType=' . $v['type'] . '&RecordValue=' . $v['address'] . '&RecordTTL=43200';
                                if ($v['type'] == "MX") {
                                    $query = $query . '&RecordPriority=' . $v['priority'];
                                }
                                $addDnsUrl = "AddDNSRecord/?" . $query;
                                $addDnsUrl = trim($addDnsUrl);
                                $addDnsUrl = str_replace(' ', '%20', $addDnsUrl);


                                $res = $helper->get($addDnsUrl, [], "AddDNSRecord");

                                if ($res['result']['responseMsg']['statusCode'] != 200) {
                                    $values = $helper->sendResponse($res['result']);
                                    return $values;
                                }
                            }
                        }
                    } else {
                        if ($v['recid'] != "" && $v['recid'] != null) {
                            $query = 'APIKey=' . $ApiKey . '&DNSZoneID=' . $DNSZoneId . '&DNSZoneRecordID=' . $v['recid'];
                            $deleteDnsUrl = "DeleteDNSRecord/?" . $query;
                            $deleteDnsUrl = trim($deleteDnsUrl);
                            $deleteDnsUrl = str_replace(' ', '%20', $deleteDnsUrl);

                            $res = $helper->get($deleteDnsUrl, [], "DeleteDNSRecord");

                            if ($res['result']['responseMsg']['statusCode'] != 200) {
                                $values = $helper->sendResponse($res['result']);
                                return $values;
                            }
                        }
                    }
                }
            }
        }
        return $values;
    } catch (\Exception $e) {
        return array(
            'error' => $e->getMessage(),
        );
    }
}
function connectreseller_RegisterDomain($params)
{
    try {
        $helper = new Helper();


        $whmcsArray = $helper->whmcsLangArray();
        $provideLang = $helper->provideLangArray();

        $lang  = "";
        foreach ($whmcsArray as $key => $whmcsval) {
            if ($whmcsval == $params['idnLanguage']) {
                $lang = $provideLang[$key]['code'];
              
                if ($params["tld"] == "com" && $params['idnLanguage'] == "kor") {
                    $lang = 'KOR';
                }
            }
        }
        
        $domainname = $params["sld"] . '.' . $params["tld"];
        if (!mb_check_encoding($domainname, 'ASCII')) {
            $domainname = urlencode($domainname);
        }

        $tld = $params["tld"];
        $sld = $params["sld"];
        $ApiKey = $params['APIKey'];
        $websitename = $domainname;
        $regperiod = $params["regperiod"];
        $nameserver1 = $params["ns1"];
        $nameserver2 = $params["ns2"];
        $nameserver3 = $params["ns3"];
        $nameserver4 = $params["ns4"];
        $IsWhoisProtectionFalse = "false";
        $CouponCode = $params["CouponCode"];
        $IsWhoisProtection = $params["idprotection"] == 1 ? true : $IsWhoisProtectionFalse;
        $RegistrantEmailAddress = $params["email"];
        $viewClienturl = "ViewClient/?APIKey=" . $ApiKey . '&UserName=' . $RegistrantEmailAddress;
        $viewClienturl = trim($viewClienturl);
        $viewClienturl = str_replace(' ', '%20', $viewClienturl);

        $res = $helper->get($viewClienturl, [], "ViewClient");

        $res = $res['result'];

        $msgResult = array_key_exists("responseMsg", $res);
        if ($msgResult) {

            if ($res['responseMsg']['statusCode'] != '200') {
                $str = "cr123456";
                $Password = str_shuffle($str);
                $companyname = $params["companyname"];
                $firstname = $params["fullname"];
                $address1 = $params["address1"];
                $address2 = $params["address2"];
                $countryname = $params["countryname"];
                if ($params["fullstate"] == '') {
                    $state = 'other';
                } else {
                    $state = $params["fullstate"];
                }
                $city = $params["city"];
                $postcode = $params["postcode"];
                $phonecc = $params["phonecc"];
                $phonenumber = $params["phonenumber"];

                $query = "APIKey=" . urlencode($ApiKey);
                $query .= "&UserName=" . urlencode($RegistrantEmailAddress);
                $query .= "&Password=" . urlencode($Password) . "&CompanyName=" . urlencode($companyname) . "&FirstName=" . urlencode($firstname) . "&Address1=" . urlencode($address1 . $address2) . "&City=" . urlencode($city) . "&StateName=" . $state . "&CountryName=" . $countryname . "&Zip=" . $postcode . "&PhoneNo_cc=" . $phonecc . "&PhoneNo=" . $phonenumber;
                $addClienturl = "AddClient?" . trim($query);
                $addClienturl = trim($addClienturl);
                $addClienturl = str_replace(' ', '%20', $addClienturl);


                $res = $helper->get($addClienturl, [], "AddClient");

                $addClientRes = $res['result'];

                if ($addClientRes['responseMsg']['statusCode'] != 200) {
                    $values = $helper->sendResponse($addClientRes);
                } else {
                    $UserName = $addClientRes['responseData']['userName'];
                    $CustomerID = $addClientRes['responseData']['clientId'];
                    $query = 'APIKey=' . $ApiKey . '&Id=' . $CustomerID;
                    $defaultRegistranturl = "DefaultRegistrantContact/?" . $query;

                    $res = $helper->get($defaultRegistranturl, [], "DefaultRegistrantContact");

                    $defaultRegistrantRes = $res['result'];

                    if ($defaultRegistrantRes['responseMsg']['statusCode'] != 200) {
                        $values = $helper->sendResponse($defaultRegistrantRes);
                    } else {
                        $ContactId = $defaultRegistrantRes['responseData']['registrantContactId'];
                    }
                    $regperiod = $params["regperiod"];
                    $websitename = $domainname;

                    if ($tld == "us") {
                        $NexusCategory = $helper->nexusCategory($params);
                        $appPurpose = $helper->appPurpose($params, "P2");
                    }

                    $query = 'APIKey=' . $ApiKey . '&Id=' . $CustomerID . '&ProductType=1&Websitename=' . $websitename . '&Duration=' . $regperiod . '&IsWhoisProtection=' . $IsWhoisProtection;
                    if ($nameserver1 != "") $query .= '&ns1=' . $nameserver1;
                    if ($nameserver2 != "") $query .= '&ns2=' . $nameserver2;
                    if ($nameserver3 != "") $query .= '&ns3=' . $nameserver3;
                    if ($nameserver4 != "") $query .= '&ns4=' . $nameserver4;
                    if ($tld == "us") {
                        $query .= '&appPurpose=' . $appPurpose;
                        $query .= '&nexusCategory=' . $NexusCategory;
                        $isUs = true;
                        $query .= '&isUs=' . $isUs;
                    }
                    $premiumEnabled = (bool) $params['premiumEnabled'] == true ? 1 : 0;
                    $query .= '&isEnablePremium=' . $premiumEnabled;


                    if (isset($params['is_idn']) && isset($params['idnLanguage'])) {
                        $query .= '&lang=' . $lang;
                    }
                    if (!(!isset($CouponCode) || trim($CouponCode) === '')) {
                        $query .= '&couponCode=' . $CouponCode;
                    }
                    $orderUrl = "domainorder/?" . $query;
                    $orderUrl = trim($orderUrl);
                    $orderUrl = str_replace(' ', '%20', $orderUrl);




                    $res = $helper->get($orderUrl, [], "domainorder");

                    $orderRes = $res['result'];

                    if ($orderRes['responseMsg']['statusCode'] != 200) {
                        $values = $helper->sendResponse($orderRes);
                    } else {
                        return array(
                            'success' => 'success',
                        );
                    }
                }
            } else {
                $UserName = $res['responseData']['userName'];
                $CustomerID = $res['responseData']['clientId'];
                $query = 'APIKey=' . $ApiKey . '&Id=' . $CustomerID;
                $regperiod = $params["regperiod"];
                $websitename = $domainname;
                if ($tld == "us") {

                    $NexusCategory = $helper->nexusCategory($params);

                    $appPurpose = $helper->appPurpose($params, 'P1');
                }
                $query = 'APIKey=' . $ApiKey . '&Id=' . $CustomerID . '&ProductType=1&Websitename=' . $websitename . '&Duration=' . $regperiod . '&IsWhoisProtection=' . $IsWhoisProtection;
                if ($nameserver1 != "") $query .= '&ns1=' . $nameserver1;
                if ($nameserver2 != "") $query .= '&ns2=' . $nameserver2;
                if ($nameserver3 != "") $query .= '&ns3=' . $nameserver3;
                if ($nameserver4 != "") $query .= '&ns4=' . $nameserver4;
                if ($tld == "us") {
                    $query .= '&appPurpose=' . $appPurpose;
                    $query .= '&nexusCategory=' . $NexusCategory;
                    $isUs = true;
                    $query .= '&isUs=' . $isUs;
                }
                $premiumEnabled = (bool) $params['premiumEnabled'] == true ? 1 : 0;
                $query .= '&isEnablePremium=' . $premiumEnabled;
                if (isset($params['is_idn']) && isset($params['idnLanguage'])) {
                    $query .= '&lang=' . $lang;
                }
                if (!(!isset($CouponCode) || trim($CouponCode) === '')) {
                    $query .= '&couponCode=' . $CouponCode;
                }
                $orderUrl = "domainorder/?" . $query;
                $orderUrl = trim($orderUrl);
                $orderUrl = str_replace(' ', '%20', $orderUrl);

                $res = $helper->get($orderUrl, [], "domainorder");

                $orderRes = $res['result'];

                if ($orderRes['responseMsg']['statusCode'] != 200) {
                    $values = $helper->sendResponse($orderRes);
                } else {
                    return array(
                        'success' => 'success',
                    );
                }
            }
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
function connectreseller_TransferDomain($params)
{
    try {
        $helper = new Helper();
        $tld = $params["tld"];
        $sld = $params["sld"];
        $ApiKey = $params['APIKey'];
        $websitename = $sld . '.' . $tld;

        $domainname = $params["sld"] . '.' . $params["tld"];
        if (!mb_check_encoding($domainname, 'ASCII')) {

            $websitename = urlencode($domainname);
        }
        $CouponCode = $params["CouponCode"];
        $IsWhoisProtectionFalse = "false";
        $IsWhoisProtection = $params["idprotection"] == 1 ? true : $IsWhoisProtectionFalse;
        $RegistrantEmailAddress = $params["email"];
        $authCode = $params["eppcode"];
        $viewClienturl = "ViewClient/?APIKey=" . $ApiKey . '&UserName=' . $RegistrantEmailAddress;
        $viewClienturl = trim($viewClienturl);
        $viewClienturl = str_replace(' ', '%20', $viewClienturl);

        $res = $helper->get($viewClienturl, [], "ViewClient");

        $res = $res['result'];

        $msgResult = array_key_exists("responseMsg", $res);
        if ($msgResult) {
            if ($res["responseMsg"]['statusCode'] != '200') {
                $str = "cr123456";
                $Password = str_shuffle($str);
                $companyname = $params["companyname"];
                $firstname = $params["fullname"];
                $address1 = $params["address1"];
                $address2 = $params["address2"];
                $countryname = $params["countryname"];
                if ($params["fullstate"] == '') {
                    $state = 'other';
                } else {
                    $state = $params["fullstate"];
                }
                $city = $params["city"];
                $postcode = $params["postcode"];
                $phonecc = $params["phonecc"];
                $phonenumber = $params["phonenumber"];
                $query = "APIKey=" . urlencode($ApiKey);
                if ($tld == "us") {

                    $NexusCategory = $helper->nexusCategory($params);

                    $appPurpose = $helper->appPurpose($params, '');

                    $query .= '&appPurpose=' . $appPurpose;
                    $query .= '&nexusCategory=' . $NexusCategory;
                    $isUs = true;
                    $query .= '&isUs=' . $isUs;
                }
                $query .= "&UserName=" . urlencode($RegistrantEmailAddress);
                $query .= "&Password=" . urlencode($Password) . "&CompanyName=" . urlencode($companyname) . "&FirstName=" . urlencode($firstname) . "&Address1=" . urlencode($address1 . $address2) . "&City=" . urlencode($city) . "&StateName=" . $state . "&CountryName=" . $countryname . "&Zip=" . $postcode . "&PhoneNo_cc=" . $phonecc . "&PhoneNo=" . $phonenumber;
                $addClienturl = "AddClient?" . trim($query);
                $addClienturl = trim($addClienturl);
                $addClienturl = str_replace(' ', '%20', $addClienturl);

                $res = $helper->get($addClienturl, [], "AddClient");

                $addClientRes = $res['result'];

                if ($addClientRes['responseMsg']['statusCode'] != 200) {
                    $values = $helper->sendResponse($addClientRes);
                } else {
                    $CustomerID = $addClientRes['responseData']['clientId'];
                    try {
                        $dataArr = array(
                            'Id' => intval($CustomerID),
                            'OrderType' => 4,
                            'APIKey' => $ApiKey,
                            'Websitename' => $websitename,
                            'AuthCode' => $authCode
                        );
                        $query = http_build_query($dataArr);
                        $query = $query . '&IsWhoisProtection=' . $IsWhoisProtection;
                        if (!(!isset($CouponCode) || trim($CouponCode) === '')) {
                            $query .= '&couponCode=' . $CouponCode;
                        }
                        $orderUrl = "TransferOrder/?" . $query;
                        $orderUrl = trim($orderUrl);
                        $orderUrl = str_replace(' ', '%20', $orderUrl);

                        $res = $helper->get($orderUrl, [], "TransferOrder");

                        $orderRes = $res['result'];

                        if ($orderRes['responseMsg']['statusCode'] != 200) {
                            $values = $helper->sendResponse($orderRes);
                        } else {

                            return array(
                                'success' => true,
                            );
                        }
                    } catch (Exception $e) {
                        $values['error'] = "An error occurred: " . $e->getMessage();
                    }
                }
            } else {
                $CustomerID = $res['responseData']['clientId'];
                try {
                    $dataArr = array(
                        'Id' => intval($CustomerID),
                        'OrderType' => 4,
                        'APIKey' => $ApiKey,
                        'Websitename' => $websitename,
                        'AuthCode' => $authCode
                    );
                    $query = http_build_query($dataArr);
                    if (!(!isset($CouponCode) || trim($CouponCode) === '')) {
                        $query .= '&couponCode=' . $CouponCode;
                    }
                    if ($tld == "us") {

                        $NexusCategory = $helper->nexusCategory($params);

                        $appPurpose = $helper->appPurpose($params, '');

                        $query .= '&appPurpose=' . $appPurpose;
                        $query .= '&nexusCategory=' . $NexusCategory;
                        $isUs = true;
                        $query .= '&isUs=' . $isUs;
                    }
                    $orderUrl = "TransferOrder/?" . $query;
                    $orderUrl = trim($orderUrl);
                    $orderUrl = str_replace(' ', '%20', $orderUrl);
                    $query = $query . '&IsWhoisProtection=' . $IsWhoisProtection;

                    $res = $helper->get($orderUrl, [], "TransferOrder");

                    $orderRes = $res['result'];

                    if ($orderRes['responseMsg']['statusCode'] != 200) {
                        $values = $helper->sendResponse($orderRes);
                    } else {
                        return array(
                            'success' => true,
                        );
                    }
                } catch (Exception $e) {
                    $values['error'] = "An error occurred: " . $e->getMessage();
                }
            }
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
function connectreseller_RenewDomain($params)
{
    try {
        $helper = new Helper();
        $tld = $params["tld"];
        $sld = $params["sld"];
        $ApiKey = $params['APIKey'];
        $regperiod = $params['regperiod'];
        $CouponCode = $params["CouponCode"];
        $IsWhoisProtectionFalse = "false";
        $IsWhoisProtection = $params["idprotection"] == 1 ? true : $IsWhoisProtectionFalse;
        $domainname = $params["sld"] . '.' . $params["tld"];
        if (!mb_check_encoding($domainname, 'ASCII')) {
            $domainname = urlencode($domainname);
        }
        $viewDomainurl = "ViewDomain/?APIKey=" . $ApiKey . '&websiteName=' . $domainname;
        $viewDomainurl = trim($viewDomainurl);
        $viewDomainurl = str_replace(' ', '%20', $viewDomainurl);

        $res = $helper->get($viewDomainurl, [], "ViewDomain");

        $res = $res['result'];

        $msgResult = array_key_exists("responseMsg", $res);
        if ($msgResult) {
            if ($res["responseMsg"]['statusCode'] != '200') {
                $values = $helper->sendResponse($res);
            } else {
                $CustomerId = $res["responseData"]['customerId'];
                $query = 'APIKey=' . $ApiKey . '&Websitename=' . $domainname . '&OrderType=2&Duration=' . $regperiod . '&Id=' . $CustomerId . '&IsWhoisProtection=' . $IsWhoisProtection;
                $premiumEnabled = (bool) $params['premiumEnabled'] == true ? 1 : 0;
                $query .= '&isEnablePremium=' . $premiumEnabled;
                if (!(!isset($CouponCode) || trim($CouponCode) === '')) {
                    $query .= '&couponCode=' . $CouponCode;
                }
                $renewDomainurl = "renewalorder/?" . $query;
                $renewDomainurl = trim($renewDomainurl);
                $renewDomainurl = str_replace(' ', '%20', $renewDomainurl);

                $res = $helper->get($renewDomainurl, [], "renewalorder");
                $res1 = $res['result'];

                if ($res1["responseMsg"]['statusCode'] != '200') {
                    $values = $helper->sendResponse($res1);
                } else {
                    return array(
                        'success' => true,
                    );
                }
            }
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
function connectreseller_GetContactDetails($params)
{
    try {

        $helper = new Helper();
        $tld = $params["tld"];
        $sld = $params["sld"];
        $ApiKey = $params['APIKey'];
        $domainname = $params["sld"] . '.' . $params["tld"];
        if (!mb_check_encoding($domainname, 'ASCII')) {
            $domainname = urlencode($domainname);
        }
        $viewDomainurl = "ViewDomain/?APIKey=" . $ApiKey . '&websiteName=' . $domainname;
        $viewDomainurl = trim($viewDomainurl);
        $viewDomainurl = str_replace(' ', '%20', $viewDomainurl);

        $response = $helper->get($viewDomainurl, [], "GetContactDetails");

        if ($response['result']['responseMsg']['statusCode'] != 200) {
            $values = $helper->sendResponse($response['result']);
            return $values;
        }
        $response = $response['result'];

        $RegistrantContactId = $response["responseData"]['registrantContactId'];  // 
        $AdminContactId = $response["responseData"]['adminContactId'];  // 
        $BillingContactId = $response["responseData"]['billingContactId'];  // 
        $TechnicalContactId = $response["responseData"]['technicalContactId'];

        $GetContactDetails = 'ViewRegistrant?APIKey=' . $ApiKey . '&RegistrantContactId=' . $RegistrantContactId;
        $GetContactDetails = trim($GetContactDetails);
        $GetContactDetails = str_replace(' ', '%20', $GetContactDetails);

        $res = $helper->get($GetContactDetails, [], "ViewRegistrant");

        // Check for errors in the res
        if ($res['result']['responseMsg']['statusCode'] != 200) {
            $values = $helper->sendResponse($res['result']);
            return $values;
        }
        $contactDetailsRes = $res['result'];

        $result = array();
        $result['name'] = $contactDetailsRes["responseData"]['name'];
        $result['Company'] = $contactDetailsRes["responseData"]['companyName'];
        $result['Address1'] = $contactDetailsRes["responseData"]['address1'];
        $result['Address2'] = $contactDetailsRes["responseData"]['address2'];;
        $result['Address3'] = $contactDetailsRes["responseData"]['address3'];
        $result['City'] = $contactDetailsRes["responseData"]['city'];
        $result['State'] = $contactDetailsRes["responseData"]['stateName'];
        $result['Country'] = $contactDetailsRes["responseData"]['countryName'];
        $result['Zip'] = $contactDetailsRes["responseData"]['postalCode'];
        $result['PhoneNo_CountryCode'] = $contactDetailsRes["responseData"]['phoneCode'];
        $result['PhoneNo'] = $contactDetailsRes["responseData"]['phoneNo'];
        $result['PhoneNo'] = substr($result['PhoneNo'], 0, 10);
        $result['emailaddr'] = $contactDetailsRes["responseData"]['emailAddress'];
        $values['Registrant'] = array('Full Name' => $result['name'], 'Email' => $result['emailaddr'], 'Company Name' => $result['Company'], 'Address 1' => $result['Address1'], 'Address 2' => $result['Address2'], 'Address 3' => $result['Address3'], 'City' => $result['City'], 'State' => $result['State'], 'Country' => $result['Country'], 'Postcode' => $result['Zip'], 'Phone Number' => $result['PhoneNo_CountryCode'] . $result['PhoneNo']); // 
        if ($RegistrantContactId === $TechnicalContactId) {
            $values['Technical'] = array('Full Name' => $result['name'], 'Email' => $result['emailaddr'], 'Company Name' => $result['Company'], 'Address 1' => $result['Address1'], 'Address 2' => $result['Address2'], 'Address 3' => $result['Address3'], 'City' => $result['City'], 'State' => $result['State'], 'Country' => $result['Country'], 'Postcode' => $result['Zip'], 'Phone Number' => $result['PhoneNo_CountryCode'] . $result['PhoneNo']);
        } else {
            $GetContactDetails1 = 'ViewRegistrant?APIKey=' . $ApiKey . '&RegistrantContactId=' . $TechnicalContactId;
            $GetContactDetails1 = trim($GetContactDetails1);
            $GetContactDetails1 = str_replace(' ', '%20', $GetContactDetails1);

            $res = $helper->get($GetContactDetails1, [], "ViewRegistrant");

            if ($res['result']['responseMsg']['statusCode'] != 200) {
                $values = $helper->sendResponse($res['result']);
                return $values;
            }
            $contactDetailsRes1 = $res['result'];

            $result1 = array();
            $result1['name'] = $contactDetailsRes1["responseData"]['name'];
            $result1['Company'] = $contactDetailsRes1["responseData"]['companyName'];
            $result1['Address1'] = $contactDetailsRes1["responseData"]['address1'];
            $result1['Address2'] = $contactDetailsRes1["responseData"]['address2'];;
            $result1['Address3'] = $contactDetailsRes1["responseData"]['address3'];
            $result1['City'] = $contactDetailsRes1["responseData"]['city'];
            $result1['State'] = $contactDetailsRes1["responseData"]['stateName'];
            $result1['Country'] = $contactDetailsRes1["responseData"]['countryName'];
            $result1['Zip'] = $contactDetailsRes1["responseData"]['postalCode'];
            $result1['PhoneNo_CountryCode'] = $contactDetailsRes1["responseData"]['phoneCode'];
            $result1['PhoneNo'] = $contactDetailsRes1["responseData"]['phoneNo'];
            $result1['PhoneNo'] = substr($result1['PhoneNo'], 0, 10);
            $result1['emailaddr'] = $contactDetailsRes1["responseData"]['emailAddress'];
            $values['Technical'] = array('Full Name' => $result1['name'], 'Email' => $result1['emailaddr'], 'Company Name' => $result1['Company'], 'Address 1' => $result1['Address1'], 'Address 2' => $result1['Address2'], 'Address 3' => $result1['Address3'], 'City' => $result1['City'], 'State' => $result1['State'], 'Country' => $result1['Country'], 'Postcode' => $result1['Zip'], 'Phone Number' => $result1['PhoneNo_CountryCode'] . $result1['PhoneNo']);
        }

        if ($RegistrantContactId === $BillingContactId) {
            $values['Billing'] = array('Full Name' => $result['name'], 'Email' => $result['emailaddr'], 'Company Name' => $result['Company'], 'Address 1' => $result['Address1'], 'Address 2' => $result['Address2'], 'Address 3' => $result['Address3'], 'City' => $result['City'], 'State' => $result['State'], 'Country' => $result['Country'], 'Postcode' => $result['Zip'], 'Phone Number' => $result['PhoneNo_CountryCode'] . $result['PhoneNo']);
        } else {
            $GetContactDetails2 = 'ViewRegistrant?APIKey=' . $ApiKey . '&RegistrantContactId=' . $BillingContactId;
            $GetContactDetails2 = trim($GetContactDetails2);
            $GetContactDetails2 = str_replace(' ', '%20', $GetContactDetails2);

            $res = $helper->get($GetContactDetails2, [], "ViewRegistrant");

            // Check for errors in the res
            if ($res['result']['responseMsg']['statusCode'] != 200) {
                $values = $helper->sendResponse($res['result']);
                return $values;
            }
            $contactDetailsRes2 = $res['result'];

            $result2 = array();
            $result2['name'] = $contactDetailsRes2["responseData"]['name'];
            $result2['Company'] = $contactDetailsRes2["responseData"]['companyName'];
            $result2['Address2'] = $contactDetailsRes2["responseData"]['address2'];
            $result2['Address2'] = $contactDetailsRes2["responseData"]['address2'];;
            $result2['Address3'] = $contactDetailsRes2["responseData"]['address3'];
            $result2['City'] = $contactDetailsRes2["responseData"]['city'];
            $result2['State'] = $contactDetailsRes2["responseData"]['stateName'];
            $result2['Country'] = $contactDetailsRes2["responseData"]['countryName'];
            $result2['Zip'] = $contactDetailsRes2["responseData"]['postalCode'];
            $result2['PhoneNo_CountryCode'] = $contactDetailsRes2["responseData"]['phoneCode'];
            $result2['PhoneNo'] = $contactDetailsRes2["responseData"]['phoneNo'];
            $result2['PhoneNo'] = substr($result2['PhoneNo'], 0, 20);
            $result2['emailaddr'] = $contactDetailsRes2["responseData"]['emailAddress'];
            $values['Billing'] = array('Full Name' => $result2['name'], 'Email' => $result2['emailaddr'], 'Company Name' => $result2['Company'], 'Address 2' => $result2['Address2'], 'Address 2' => $result2['Address2'], 'Address 3' => $result2['Address3'], 'City' => $result2['City'], 'State' => $result2['State'], 'Country' => $result2['Country'], 'Postcode' => $result2['Zip'], 'Phone Number' => $result2['PhoneNo_CountryCode'] . $result2['PhoneNo']);
        }

        if ($RegistrantContactId === $AdminContactId) {
            $values['Admin'] = array('Full Name' => $result['name'], 'Email' => $result['emailaddr'], 'Company Name' => $result['Company'], 'Address 1' => $result['Address1'], 'Address 2' => $result['Address2'], 'Address 3' => $result['Address3'], 'City' => $result['City'], 'State' => $result['State'], 'Country' => $result['Country'], 'Postcode' => $result['Zip'], 'Phone Number' => $result['PhoneNo_CountryCode'] . $result['PhoneNo']);
        } else {

            $GetContactDetails3 = 'ViewRegistrant?APIKey=' . $ApiKey . '&RegistrantContactId=' . $AdminContactId;
            $GetContactDetails3 = trim($GetContactDetails3);
            $GetContactDetails3 = str_replace(' ', '%30', $GetContactDetails3);

            $res = $helper->get($GetContactDetails3, [], "ViewRegistrant");

            if ($res['result']['responseMsg']['statusCode'] != 200) {
                $values = $helper->sendResponse($res['result']);
                return $values;
            }
            $contactDetailsRes3 = $res['result'];

            $result3 = array();
            $result3['name'] = $contactDetailsRes3["responseData"]['name'];
            $result3['Company'] = $contactDetailsRes3["responseData"]['companyName'];
            $result3['Address3'] = $contactDetailsRes3["responseData"]['address3'];
            $result3['Address3'] = $contactDetailsRes3["responseData"]['address3'];;
            $result3['Address3'] = $contactDetailsRes3["responseData"]['address3'];
            $result3['City'] = $contactDetailsRes3["responseData"]['city'];
            $result3['State'] = $contactDetailsRes3["responseData"]['stateName'];
            $result3['Country'] = $contactDetailsRes3["responseData"]['countryName'];
            $result3['Zip'] = $contactDetailsRes3["responseData"]['postalCode'];
            $result3['PhoneNo_CountryCode'] = $contactDetailsRes3["responseData"]['phoneCode'];
            $result3['PhoneNo'] = $contactDetailsRes3["responseData"]['phoneNo'];
            $result3['PhoneNo'] = substr($result3['PhoneNo'], 0, 30);
            $result3['emailaddr'] = $contactDetailsRes3["responseData"]['emailAddress'];
            $values['Admin'] = array('Full Name' => $result3['name'], 'Email' => $result3['emailaddr'], 'Company Name' => $result3['Company'], 'Address 3' => $result3['Address3'], 'Address 3' => $result3['Address3'], 'Address 3' => $result3['Address3'], 'City' => $result3['City'], 'State' => $result3['State'], 'Country' => $result3['Country'], 'Postcode' => $result3['Zip'], 'Phone Number' => $result3['PhoneNo_CountryCode'] . $result3['PhoneNo']);
        }

        return $values;
    } catch (\Exception $e) {
        return array(
            'error' => $e->getMessage(),
        );
    }
}
function connectreseller_SaveContactDetails($params)
{
    try {
        $helper = new Helper();
        $tld = $params["tld"];
        $sld = $params["sld"];
        $ApiKey = $params['APIKey'];
        $domainname = $params["sld"] . '.' . $params["tld"];
        if (!mb_check_encoding($domainname, 'ASCII')) {
            $domainname = urlencode($domainname);
        }
        $query = 'APIKey=' . $ApiKey . '&websiteName=' . $domainname;
        $viewDomainurl = "ViewDomain/?" . $query;
        $viewDomainurl = trim($viewDomainurl);
        $viewDomainurl = str_replace(' ', '%20', $viewDomainurl);

        $response = $helper->get($viewDomainurl, [], "SaveContactDetails");

        if ($response['result']['responseMsg']['statusCode'] != 200) {
            $values = $helper->sendResponse($response['result']);
            return $values;
        }
        $response = $response['result'];

        $query = 'APIKey=' . $ApiKey;
        $query .= '&Id=' . $response["responseData"]['customerId'];
        $query .= '&EmailAddress=' . $params['contactdetails']['Registrant']['Email'];
        $query .= '&Name=' . $params['contactdetails']['Registrant']['Full Name'];
        $query .= '&Address1=' . $params['contactdetails']['Registrant']['Address 1'];
        $query .= '&Address2=' . $params['contactdetails']['Registrant']['Address 2'];
        $query .= '&Address3=' . $params['contactdetails']['Registrant']['Address 3'];
        $query .= '&City=' . $params['contactdetails']['Registrant']['City'];
        $query .= '&StateName=' . $params['contactdetails']['Registrant']['State'];
        $query .= '&CountryName=' . $params['contactdetails']['Registrant']['Country'];
        $query .= '&PhoneNo_cc=' . $params['contactdetails']['Registrant']['Phone Country Code'];
        $query .= '&PhoneNo=' . $params['contactdetails']['Registrant']['Phone Number'];
        $query .= '&Zip=' . $params['contactdetails']['Registrant']['Postcode'];
        $query .= '&CompanyName=' . $params['contactdetails']['Registrant']['Company Name'];
        $query .= '&domainId=' . $response["responseData"]['domainNameId'];
        $SaveContactDetails = "ModifyRegistrantContact_whmcs?" . $query;
        $SaveContactDetails = trim($SaveContactDetails);
        $SaveContactDetails = str_replace(' ', '%20', $SaveContactDetails);

        $res = $helper->get($SaveContactDetails, [], "ModifyRegistrantContact_whmcs");

        if ($res['result']['responseMsg']['statusCode'] != 200) {
            $values = $helper->sendResponse($res['result']);
            return $values;
        }

        return array(
            'success' => true,
        );
    } catch (\Exception $e) {
        return array(
            'error' => $e->getMessage(),
        );
    }
}
function connectreseller_GetEPPCode($params)
{
    try {
        $helper = new Helper();
        $tld = $params["tld"];
        $sld = $params["sld"];
        $ApiKey = $params['APIKey'];

        $websitename = $params["sld"] . '.' . $params["tld"];
        if (!mb_check_encoding($domainname, 'ASCII')) {
            $websitename = urlencode($domainname);
        }

        $viewDomainurl = "ViewDomain/?APIKey=" . $ApiKey . '&websiteName=' . $websitename;
        $viewDomainurl = trim($viewDomainurl);
        $viewDomainurl = str_replace(' ', '%20', $viewDomainurl);

        $response = $helper->get($viewDomainurl, [], "GetEPPCode");

        if ($response['result']['responseMsg']['statusCode'] != 200) {
            $values = $helper->sendResponse($response['result']);
            return $values;
        }
        $response = $response['result'];

        $eppcode = $response["responseData"]["authCode"];
        $values["eppcode"] = $eppcode;
        return $values;
    } catch (\Exception $e) {
        return array(
            'error' => $e->getMessage(),
        );
    }
}
function connectreseller_RegisterNameserver($params)
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
function connectreseller_ModifyNameserver($params)
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
function connectreseller_DeleteNameserver($params)
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
function connectreseller_IDProtectToggle($params)
{
    try {
        $helper = new Helper();
        $tld = $params["tld"];
        $sld = $params["sld"];
        $ApiKey = $params['APIKey'];

        if ($params['protectenable'] == 1)
            $protectEnable = 'true';
        else
            $protectEnable = 'false';


        $domainname = $params["sld"] . '.' . $params["tld"];
        if (!mb_check_encoding($domainname, 'ASCII')) {
            $domainname = urlencode($domainname);
        }
        $query = 'APIKey=' . $ApiKey . '&websiteName=' . $domainname;
        $viewDomainurl = "ViewDomain/?" . $query;

        $res = $helper->get($viewDomainurl, [], "IDProtectToggle");

        if ($res['result']['responseMsg']['statusCode'] != 200) {
            $values = $helper->sendResponse($res['result']);
            return $values;
        }
        $res = $res['result'];

        $domainNameId = $res["responseData"]['domainNameId'];
        $query = 'APIKey=' . $ApiKey . '&websiteName=' . $domainname . '&domainNameId=' . $domainNameId . '&iswhoisprotected=' . $protectEnable;
        $manageUrl = trim("ManageDomainPrivacyProtection/?" . $query);

        $res = $helper->get($manageUrl, [], "ManageDomainPrivacyProtection");

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
function connectreseller_TransferSync($params)
{
    try {
        $helper = new Helper();
        $ApiKey = $params['APIKey'];
        // $BrandId = $params['BrandId'];
        $domainname = $params["sld"] . '.' . $params["tld"];
        if (!mb_check_encoding($domainname, 'ASCII')) {
            $domainname = urlencode($domainname);
        }
        $query = 'APIKey=' . $ApiKey . '&domainName=' . $domainname;
        $viewDomainurl = "syncTransfer/?" . $query;

        $response = $helper->get($viewDomainurl, [], "TransferSync");

        if ($response['result']['responseMsg']['statusCode'] != 200) {
            $values = $helper->sendResponse($response['result']);
            return $values;
        }
        $response = $response['result'];

        if ($response["responseMsg"]['statusCode'] != '200') {
            $result['completed'] = false;
            $result['failed'] = false;
            $result['error'] = $response["responseMsg"]['message'];
        } else {
            if ($response["responseData"]['status'] == 'completed') {
                $result['completed'] = true;
                $result['failed'] = false;
                $result['expirydate'] = $date = date('Y-m-d', intval($response["responseData"]['expiryDate']/1000));
            } else if ($response["responseData"]['status'] == 'pending') {
                $result['completed'] = false;
                $result['failed'] = false;
            } else {
                $result['completed'] = false;
                $result['failed'] = true;
                $result['reason'] = $response["responseData"]['reason'];
            }
        }

        return $result;
    } catch (\Exception $e) {
        return array(
            'error' => $e->getMessage(),
        );
    }
}

function connectreseller_Sync($params)
{
    try {
        $helper = new Helper();
        $tld = $params["tld"];
        $sld = $params["sld"];
        $ApiKey = $params['APIKey'];

        $domainname = $params["sld"] . '.' . $params["tld"];
        if (!mb_check_encoding($domainname, 'ASCII')) {
            $domainname = urlencode($domainname);
        }

        $query = 'APIKey=' . $ApiKey . '&websiteName=' . $domainname;
        $viewDomainurl = "ViewDomain/?" . $query;
        $viewDomainurl = trim($viewDomainurl);
        $viewDomainurl = str_replace(' ', '%20', $viewDomainurl);

        $res = $helper->get($viewDomainurl, [], "Sync");
        $res = $res['result'];

        if ($res["responseMsg"]['statusCode'] != '200') {
            $values = $helper->sendResponse($res);
        } else {
            if ($res["responseData"]["status"] == "Expired" || $res["responseData"]["status"] == "Pending Delete Restorable"  || $res["responseData"]["status"] == "Deleted" || $res["responseData"]["status"] == "Renewal Hold") {
                $result['active'] = false;
                $result['expired'] = true;
                $result['expirydate'] = $date = date('Y-m-d', intval($res["responseData"]['expirationDate']/1000));
                $result['transferredAway'] = false;
            } else if (trim($res["responseData"]["status"]) == "Domain Transfer-out") {
                $result['active'] = false;
                $result['expired'] = false;
                $result['expirydate'] = $date = date('Y-m-d', intval($res["responseData"]['expirationDate']/1000));
                $result['transferredAway'] = true;
            } else {
                $result['active'] = true;
                $result['expired'] = false;
                $result['expirydate'] = $date = date('Y-m-d', intval($res["responseData"]['expirationDate']/1000));
                $result['transferredAway'] = false;
            }
            return $result;
        }

        return $values;
    } catch (\Exception $e) {
        return array(
            'error' => $e->getMessage(),
        );
    }
}

function connectreseller_CheckAvailability($params)
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

function connectreseller_GetTldPricing($params)
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
function connectreseller_ClientAreaCustomButtonArray()
{
    try {
        return array(
            'Lock' => 'clientarealock',
            'Unlock' => 'clientareaunlock',
        );
    } catch (\Exception $e) {
        return array(
            'error' => $e->getMessage(),
        );
    }
}
function connectreseller_clientarealock($params)
{
    try {
        $helper = new Helper();

        // // domain parameters
        $ApiKey = $params['APIKey'];
        $sld = $params['sld'];
        $tld = $params['tld'];

        $domainname = $params["sld"] . '.' . $params["tld"];
        if (!mb_check_encoding($domainname, 'ASCII')) {
            $domainname = urlencode($domainname);
        }


        $viewDomainurl = "ViewDomain/?APIKey=" . $ApiKey . '&websiteName=' . $domainname;

        $response = $helper->get($viewDomainurl, [], "lock ViewDomain");

        // Check for errors in the res
        if ($response['result']['responseMsg']['statusCode'] != 200) {
            return array("templatefile" => "connectreseller", "vars" => array("error" => "Error: " . $response['responseMsg']['statusCode'] . " " . $response['responseMsg']['message']));
        }

        $response = $response['result'];

        $DomainNameID = $response["responseData"]['domainNameId'];

        $viewDomainurl = 'ManageDomainLock?APIKey=' . $params['APIKey'] . '&domainNameId=' . $DomainNameID . '&websiteName=' . $domainname . "&isDomainLocked=" . true;
        $viewDomainurl = str_replace(' ', '%20', $viewDomainurl);

        $res = $helper->get($viewDomainurl, [], "lock");

        if ($res['result']['responseMsg']['statusCode'] != 200) {
            return array("templatefile" => "connectreseller", "vars" => array("error" => "Error: " . $res['result']['responseData']['statusCode'] . " " . $res['result']['responseData']['message']));
        }

        return array("templatefile" => "connectreseller", "vars" => array("successful" => 'The changes to the domain were saved successfully'));
    } catch (\Exception $e) {
        return array(
            'error' => $e->getMessage(),
        );
    }
}
function connectreseller_clientareaunlock($params)
{
    try {
        $helper = new Helper();

        $ApiKey = $params['APIKey'];
        $sld = $params['sld'];
        $tld = $params['tld'];

        $domainname = $params["sld"] . '.' . $params["tld"];
        if (!mb_check_encoding($domainname, 'ASCII')) {
            $domainname = urlencode($domainname);
        }


        $viewDomainurl = "ViewDomain/?APIKey=" . $ApiKey . '&websiteName=' . $domainname;

        $response = $helper->get($viewDomainurl, [], "unlock ViewDomain");

        if ($response['result']['responseMsg']['statusCode'] != 200) {
            return array("templatefile" => "connectreseller", "vars" => array("error" => "Error: " . $response['responseMsg']['statusCode'] . " " . $response['responseMsg']['message']));
        }
        $response = $response['result'];

        $DomainNameID = $response["responseData"]['domainNameId'];

        $viewDomainurl = 'ManageDomainLock?APIKey=' . $params['APIKey'] . '&domainNameId=' . $DomainNameID . '&websiteName=' . $domainname . "&isDomainLocked=false";
        $viewDomainurl = str_replace(' ', '%20', $viewDomainurl);

        $res = $helper->get($viewDomainurl, [], "unlock");

        if ($res['result']['responseMsg']['statusCode'] != 200) {
            return array("templatefile" => "connectreseller", "vars" => array("error" => "Error: " . $res['result']['responseData']['statusCode'] . " " . $res['result']['responseData']['message']));
        }
        return array("templatefile" => "connectreseller", "vars" => array("successful" => 'The changes to the domain were saved successfully'));
    } catch (\Exception $e) {
        return array(
            'error' => $e->getMessage(),
        );
    }
}

function connectreseller_lock($params)
{
    try {
        $helper = new Helper();

        $ApiKey = $params['APIKey'];
        $sld = $params['sld'];
        $tld = $params['tld'];

        $domainname = $params["sld"] . '.' . $params["tld"];
        if (!mb_check_encoding($domainname, 'ASCII')) {
            $domainname = urlencode($domainname);
        }

        $viewDomainurl = "ViewDomain/?APIKey=" . $ApiKey . '&websiteName=' . $domainname;

        $response = $helper->get($viewDomainurl, [], "lock ViewDomain");

        // Check for errors in the res
        if ($response['result']['responseMsg']['statusCode'] != 200) {
            $values = $helper->sendResponse($response['result']);
            return $values;
        }
        $response = $response['result'];

        $DomainNameID = $response["responseData"]['domainNameId'];

        $viewDomainurl = 'ManageDomainLock?APIKey=' . $params['APIKey'] . '&domainNameId=' . $DomainNameID . '&websiteName=' . $domainname . "&isDomainLocked=" . true;
        $viewDomainurl = str_replace(' ', '%20', $viewDomainurl);

        $res = $helper->get($viewDomainurl, [], "lock");

        if ($res['result']['responseMsg']['statusCode'] != 200) {
            $values["error"] = "Error: " . $res['result']['responseData']['statusCode'] . " " . $res['result']['responseData']['message'];
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
function connectreseller_unlock($params)
{
    try {
        $helper = new Helper();

        $ApiKey = $params['APIKey'];
        $sld = $params['sld'];
        $tld = $params['tld'];


        $domainname = $params["sld"] . '.' . $params["tld"];
        if (!mb_check_encoding($domainname, 'ASCII')) {
            $domainname = urlencode($domainname);
        }

        $viewDomainurl = "ViewDomain/?APIKey=" . $ApiKey . '&websiteName=' . $domainname;
        $response = $helper->get($viewDomainurl, [], "unlock ViewDomain");

        if ($response['result']['responseMsg']['statusCode'] != 200) {
            $values = $helper->sendResponse($response['result']);
            return $values;
        }
        $response = $response['result'];

        $DomainNameID = $response["responseData"]['domainNameId'];

        $viewDomainurl = 'ManageDomainLock?APIKey=' . $params['APIKey'] . '&domainNameId=' . $DomainNameID . '&websiteName=' . $domainname . "&isDomainLocked=false";
        $viewDomainurl = str_replace(' ', '%20', $viewDomainurl);

        $res = $helper->get($viewDomainurl, [], "unlock");

        if ($res['result']['responseMsg']['statusCode'] != 200) {
            $values["error"] = "Error: " . $res['result']['responseData']['statusCode'] . " " . $res['result']['responseData']['message'];
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

function connectreseller_AdminCustomButtonArray($params)
{
    try {

        return [
            'Lock' => 'lock',
            'Unlock' => 'unlock',
        ];
    } catch (\Exception $e) {
        return array(
            'error' => $e->getMessage(),
        );
    }
}

function connectreseller_AdminDomainsTabFields($params)
{
    try {
        global $whmcs;
        $helper = new Helper();

        $domainname = $params["sld"] . '.' . $params["tld"];
        if (!mb_check_encoding($domainname, 'ASCII')) {
            $domainname = urlencode($domainname);
        }

        $encryptApi = Capsule::table("tblregistrars")->where("registrar", "connectreseller")->where("setting", "APIKey")->value('value');
        $ApiKey = decrypt($encryptApi);


        $viewDomainurl = "ViewDomain/?APIKey=" . $ApiKey . '&websiteName=' . $domainname;

        $response = $helper->get($viewDomainurl, [], "AdminDomainsTabFields ViewDomain");

        if ($response['result']['responseMsg']['statusCode'] != 200) {
            $values = $helper->sendResponse($response['result']);
            return $values;
        }

        $orderInfo = $helper->orderInfo($response);

        $values['Domain Information'] = $orderInfo;
    } catch (Exception $ex) {
        $values['error'] = $ex->getMessage();
    }
    if (!empty($client))
        switchepp_logoutepp($client, $params);
    return $values;
}

<?php

namespace WHMCS\Module\Registrar\ConnectReseller;

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

class DomainLifecycle
{
    /**
     * @param array<string, mixed> $params
     * @return mixed
     */
    public static function register($params)
    {
            try {
                $helper = new Helper();


                $whmcsArray = $helper->whmcsLangArray();
                $provideLang = $helper->provideLangArray();

                $lang = DomainMapper::idnLanguageCode(
                    isset($params['idnLanguage']) ? $params['idnLanguage'] : '',
                    $params['tld'],
                    $whmcsArray,
                    $provideLang
                );

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
                        $Password = Sensitive::randomPassword();
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

    /**
     * @param array<string, mixed> $params
     * @return mixed
     */
    public static function getEppCode($params)
    {
            try {
                $helper = new Helper();
                $tld = $params["tld"];
                $sld = $params["sld"];
                $ApiKey = $params['APIKey'];

                $websitename = DomainMapper::websiteName($params["sld"], $params["tld"]);

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

    /**
     * @param array<string, mixed> $params
     * @return mixed
     */
    public static function idProtectToggle($params)
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
}

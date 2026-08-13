<?php

namespace WHMCS\Module\Registrar\ConnectReseller;

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

class Transfers
{
    /**
     * @param array<string, mixed> $params
     * @return mixed
     */
    public static function transfer($params)
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
                            } catch (\Exception $e) {
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
                        } catch (\Exception $e) {
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

    /**
     * @param array<string, mixed> $params
     * @return mixed
     */
    public static function renew($params)
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

    /**
     * @param array<string, mixed> $params
     * @return mixed
     */
    public static function syncTransfer($params)
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

    /**
     * @param array<string, mixed> $params
     * @return mixed
     */
    public static function sync($params)
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
}

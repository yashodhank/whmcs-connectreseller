<?php

namespace WHMCS\Module\Registrar\ConnectReseller;

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

class Contacts
{
    /**
     * @param array<string, mixed> $params
     * @return mixed
     */
    public static function get($params)
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
                $values['Registrant'] = DomainMapper::contactFields($contactDetailsRes["responseData"]);
                if ($RegistrantContactId === $TechnicalContactId) {
                    $values['Technical'] = $values['Registrant'];
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
                    $values['Technical'] = DomainMapper::contactFields($contactDetailsRes1["responseData"]);
                }

                if ($RegistrantContactId === $BillingContactId) {
                    $values['Billing'] = $values['Registrant'];
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
                    $values['Billing'] = DomainMapper::contactFields($contactDetailsRes2["responseData"]);
                }

                if ($RegistrantContactId === $AdminContactId) {
                    $values['Admin'] = $values['Registrant'];
                } else {

                    $GetContactDetails3 = 'ViewRegistrant?APIKey=' . $ApiKey . '&RegistrantContactId=' . $AdminContactId;
                    $GetContactDetails3 = trim($GetContactDetails3);
                    $GetContactDetails3 = str_replace(' ', '%20', $GetContactDetails3);

                    $res = $helper->get($GetContactDetails3, [], "ViewRegistrant");

                    if ($res['result']['responseMsg']['statusCode'] != 200) {
                        $values = $helper->sendResponse($res['result']);
                        return $values;
                    }
                    $contactDetailsRes3 = $res['result'];
                    $values['Admin'] = DomainMapper::contactFields($contactDetailsRes3["responseData"]);
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
    public static function save($params)
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
}

<?php

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

use WHMCS\Module\Registrar\ConnectReseller\Helper;
use WHMCS\Module\Registrar\ConnectReseller\Sensitive;
use WHMCS\Module\Registrar\ConnectReseller\DomainMapper;
use WHMCS\Module\Registrar\ConnectReseller\ApiClient;
use WHMCS\Module\Registrar\ConnectReseller\ContractSupport;
use WHMCS\Module\Registrar\ConnectReseller\DomainLock;
use WHMCS\Module\Registrar\ConnectReseller\Nameservers;
use WHMCS\Module\Registrar\ConnectReseller\Dns;
use WHMCS\Module\Registrar\ConnectReseller\Contacts;
use WHMCS\Module\Registrar\ConnectReseller\Transfers;
use WHMCS\Module\Registrar\ConnectReseller\Pricing;
use WHMCS\Module\Registrar\ConnectReseller\DomainLifecycle;
use WHMCS\Domains\DomainLookup\ResultsList;
use WHMCS\Domains\DomainLookup\SearchResult;
use WHMCS\Domain\TopLevel\ImportItem;
use WHMCS\Database\Capsule;

if (!defined('CONNECTRESELLER_MODULE_VERSION')) {
    define('CONNECTRESELLER_MODULE_VERSION', '3.0.0');
}

$apiUrl = "https://api.connectreseller.com/ConnectReseller/";

function connectreseller_getConfigArray()
{
    $configarray = array(
        'APIKey' => array('Type' => "text", 'Size' => "20", 'Description' => "Enter your API key"),
        'BrandId' => array(
            'Type' => "text",
            'Size' => "20",
            'Description' => "Reseller ID used for Test Connection (V11 availablefund). Most ESHOP calls authenticate with API Key only.",
        ),
        'CouponCode' => array('Type' => "text", 'Size' => "20", 'Description' => " Enter your Coupon code  "),
        'ModuleVersion' => array(
            'FriendlyName' => 'Module Version',
            'Type' => 'System',
            'Description' => CONNECTRESELLER_MODULE_VERSION,
        ),
    );
    return $configarray;
}
function connectreseller_MetaData()
{
    return array(
        'DisplayName' => 'ConnectReseller',
        'APIVersion' => '1.1',
        'NonLinearRegistrationPricing' => true,
    );
}

/**
 * @param array<string, mixed> $params
 * @return array<string, mixed>
 */
function connectreseller_TestConnection($params)
{
    try {
        $client = new ApiClient();
        $query = array('APIKey' => $params['APIKey']);
        if (!empty($params['BrandId'])) {
            $query['resellerId'] = $params['BrandId'];
        }
        $response = $client->get('availablefund', $query, 'TestConnection');

        return ContractSupport::interpretFundsResponse($response['result']);
    } catch (\Throwable $e) {
        return array(
            'error' => $e->getMessage(),
        );
    }
}

/**
 * @param array<string, mixed> $params
 * @return mixed
 */
function connectreseller_GetDomainInformation($params)
{
    try {
        $helper = new Helper();
        $domainname = DomainMapper::websiteName($params['sld'], $params['tld']);
        $viewDomainurl = 'ViewDomain?APIKey=' . $params['APIKey'] . '&websiteName=' . $domainname;
        $response = $helper->get($viewDomainurl, array(), 'GetDomainInformation');
        if ($response['result']['responseMsg']['statusCode'] != 200) {
            return $helper->sendResponse($response['result']);
        }
        $info = ContractSupport::domainInformationFromView(
            $response['result']['responseData'],
            $params['sld'],
            $params['tld']
        );

        return ContractSupport::toWhmcsDomain($info);
    } catch (\Throwable $e) {
        return array(
            'error' => $e->getMessage(),
        );
    }
}
function connectreseller_GetNameservers($params)
{
    return Nameservers::get($params);
}

function connectreseller_SaveNameservers($params)
{
    return Nameservers::save($params);
}

function connectreseller_GetRegistrarLock($params)
{
    try {
        $helper = new Helper();

        $tld = $params["tld"];
        $sld = $params["sld"];
        $ApiKey = $params['APIKey'];

        $domainname = DomainMapper::websiteName($params["sld"], $params["tld"]);

        $viewDomainurl = "ViewDomain/?APIKey=" . $ApiKey . '&websiteName=' . $domainname;
        $viewDomainurl = trim($viewDomainurl);
        $viewDomainurl = str_replace(' ', '%20', $viewDomainurl);

        $response = $helper->get($viewDomainurl, [], "GetRegistrarLock");

        if ($response['result']['responseMsg']['statusCode'] != 200) {
            $values = $helper->sendResponse($response['result']);
            return $values;
        }
        $response = $response['result'];

        return DomainMapper::lockStatus($response["responseData"]['isThiefProtected']);
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
        $domainname = DomainMapper::websiteName($params["sld"], $params["tld"]);

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
    return Dns::get($params);
}

function connectreseller_SaveDNS($params)
{
    return Dns::save($params);
}

function connectreseller_RegisterDomain($params)
{
    return DomainLifecycle::register($params);
}

function connectreseller_TransferDomain($params)
{
    return Transfers::transfer($params);
}

function connectreseller_RenewDomain($params)
{
    return Transfers::renew($params);
}

function connectreseller_GetContactDetails($params)
{
    return Contacts::get($params);
}

function connectreseller_SaveContactDetails($params)
{
    return Contacts::save($params);
}

function connectreseller_GetEPPCode($params)
{
    return DomainLifecycle::getEppCode($params);
}

function connectreseller_RegisterNameserver($params)
{
    return Nameservers::register($params);
}

function connectreseller_ModifyNameserver($params)
{
    return Nameservers::modify($params);
}

function connectreseller_DeleteNameserver($params)
{
    return Nameservers::delete($params);
}

function connectreseller_IDProtectToggle($params)
{
    return DomainLifecycle::idProtectToggle($params);
}

function connectreseller_TransferSync($params)
{
    return Transfers::syncTransfer($params);
}


function connectreseller_Sync($params)
{
    return Transfers::sync($params);
}


function connectreseller_CheckAvailability($params)
{
    return Pricing::checkAvailability($params);
}


function connectreseller_GetTldPricing($params)
{
    return Pricing::getTldPricing($params);
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
        return DomainLock::setLock(new Helper(), $params, true, true);
    } catch (\Exception $e) {
        return array(
            'error' => $e->getMessage(),
        );
    }
}
function connectreseller_clientareaunlock($params)
{
    try {
        return DomainLock::setLock(new Helper(), $params, false, true);
    } catch (\Exception $e) {
        return array(
            'error' => $e->getMessage(),
        );
    }
}

function connectreseller_lock($params)
{
    try {
        return DomainLock::setLock(new Helper(), $params, true, false);
    } catch (\Exception $e) {
        return array(
            'error' => $e->getMessage(),
        );
    }
}
function connectreseller_unlock($params)
{
    try {
        return DomainLock::setLock(new Helper(), $params, false, false);
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

        $values = array();
        $orderInfo = $helper->orderInfo($response);

        $values['Domain Information'] = $orderInfo;
    } catch (\Exception $ex) {
        $values['error'] = $ex->getMessage();
    }
    return $values;
}

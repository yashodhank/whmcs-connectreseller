<?php

namespace WHMCS\Module\Addon\ConnectReseller\Admin;

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

use WHMCS\Database\Capsule;
use WHMCS\Module\Addon\ConnectReseller\Helper;
// use WHMCS\Smarty;
use Smarty;

class Controller
{
    public $params = [];
    public $tplDIR;
    public $lang;
    public $tplFileName;
    public $smarty;
    public $tplVar = array();
    public function __construct($params)
    {
        global $CONFIG;
        $this->params = $params;
        $this->tplVar['rootURL'] = $CONFIG["SystemURL"];
        $this->tplVar['urlPath'] = $CONFIG["SystemURL"] . "/modules/addons/{$params['module']}/";
        $this->tplVar['lang'] = $params["_lang"];
        $this->tplVar['moduleLink'] = $params['modulelink'];
        $this->tplVar['module'] = $params['module'];
        $this->tplVar['tplDIR'] = ROOTDIR . "/modules/addons/{$params['module']}/templates/admin/";
        $this->tplVar['header'] = ROOTDIR . "/modules/addons/{$params['module']}/templates/admin/header.tpl";
        $this->tplVar['cssPath'] = $CONFIG["SystemURL"] . "/modules/addons/{$params['module']}/assets/css/";
        $this->tplVar['scriptPath'] = $CONFIG["SystemURL"] . "/modules/addons/{$params['module']}/assets/js/";
    }
    public function enabledisable($vars)
    {
        try {
            global $whmcs;
            $helper = new Helper();
            $lang = $this->tplVar['lang'];
            $formSubmitMessage = [];
            $checkboxStatus ='';

            if (!empty($whmcs->get_req_var("formaction"))) {
                $formaction = $whmcs->get_req_var("formaction");
                if($formaction == 'checkall'){
                    Capsule::table('mod_domain_status')->update(['status'=>'on']);
                    $formSubmitMessage = ['status'=>'success', 'message'=>$lang['TLDsStatusEnabled']];
                }
                if($formaction == 'uncheckall'){
                    Capsule::table('mod_domain_status')->update(['status'=>'off']);
                    $formSubmitMessage = ['status'=>'success', 'message'=>$lang['TLDsStatusDisabled']];
                }
            }

            if (($whmcs->get_req_var("ajaxaction") == "Enable/Disable TLD List") && ($whmcs->get_req_var("ajaxcall") == "true")) {

                $data = $helper->tldsList($_POST);
                echo $data;
                exit;
            }

            if (($whmcs->get_req_var("ajaxaction") == "Enable/Disable TLD") && ($whmcs->get_req_var("ajaxcall") == "true")) {

                if (empty($whmcs->get_req_var("tld"))) {
                    $message = ["status" => false, "message" => 'Something Went Wrong'];
                    echo json_encode($message);
                    exit;
                }

                $data = ['status' => $whmcs->get_req_var("status")];
                $condition = ['domain_id' => $whmcs->get_req_var("tld")];

                $updateReseller = $helper->insertUpdate('mod_domain_status', $condition, $data);
                if (str_contains($updateReseller, 'Error')) {
                    $message = ["status" => false, "message" => $updateReseller];
                } else {
                    $message = ["status" => true, "message" => $updateReseller];
                }

                echo json_encode($message);
                exit;
            }

            if (($whmcs->get_req_var("ajaxaction") == "manual Sync TLDs") && ($whmcs->get_req_var("ajaxcall") == "true")) {

                $allDomainList = $helper->fetch_table_record("tbldomainpricing", [], "");
                foreach ($allDomainList as $key => $tld) {

                    $domainId = $tld->id;
                    $whmcsExtension = $tld->extension;

                    $where = ['domain_id' => $domainId, "extension" => $whmcsExtension];
                    $status = $helper->fetch_table_record('mod_domain_status', $where, 'singleValue', 'status');

                    if ($status == "off") {
                        continue;
                    }

                    $message = '';
                    $params = $helper->CredentialRegistrar();
                    $allApiTld = $helper->get("tldsync?APIKey=" . $params['APIKey'], [], "Get Domain List");

                    if ($allApiTld['result']->statusCode) {
                        $helper->sendResponse(false, $allApiTld['result']->responseText);
                    }

                    $price = '';
                    foreach ($allApiTld['result'] as $key => $products) {

                        if ($whmcsExtension == $products->tld) {
                            $finalDomain = [];
                            $finalDomain = [
                                'tld' => $products->tld,
                                'domainregister' => $products->registrationPrice,
                                'domainrenew' => $products->renewalPrice,
                                'domaintransfer' => $products->transferPrice,
                                'currency_code' => $products->currencyCode,
                                'min_period' => $products->minPeriod,
                                'max_period' => $products->maxPeriod,
                            ];

                            $tldsPrices = $helper->domainPrice($finalDomain, 'true');

                            $updateproductprice = $helper->updateprice($products->currencyCode, $domainId, $tldsPrices);

                            if ($updateproductprice != 'success') {
                                $helper->sendResponse(false, $lang['sync_error']);
                            }
                        }
                    }
                }
                $helper->sendResponse(true, $lang['sync_success']);
            }

            // check 
            $offTldStatus = Capsule::table('mod_domain_status')->where('status','off')->count();
            if($offTldStatus == 0){
                $checkboxStatus ='true';
            }

            $this->tplFileName = $this->tplVar['tab'] = __FUNCTION__;
            $this->tplVar['formSubmitMessage'] = $formSubmitMessage;
            $this->tplVar['checkboxStatus'] = $checkboxStatus;
            $this->output();
        } catch (\Exception $e) {
            $this->tplVar['error'] = $e->getMessage();
        }
    }
    public function domainsync($vars)
    {
        try {
            global $whmcs;
            $helper = new Helper();
            $lang = $this->tplVar['lang'];

            $params = $helper->CredentialRegistrar();

            if (($whmcs->get_req_var("ajaxaction") == "Get Domain Sync") && ($whmcs->get_req_var("ajaxcall") == "true")) {
                $allDomainList = $helper->get("tldsync?APIKey=" . $params['APIKey'], [], "Get Domain List");

                if ($allDomainList['result']->statusCode) {
                    $helper->sendResponse(false, $allDomainList['result']->responseText);
                }

                $data = $helper->domainTable($allDomainList['result'], $_POST);
                echo $data;
                exit;
            }

            // if (($whmcs->get_req_var("ajaxaction") == "default") && ($whmcs->get_req_var("ajaxcall") == "true")) {
            //     $allDomainList = $helper->fetch_table_record("mod_domain_info", [], "");

            //     $formattedDomainList = [];
            //     // Loop through the original data to format it into the desired structure
            //     foreach ($allDomainList as $domain) {
            //         $formattedDomainList[] = (object) [
            //             'tld' => $domain->tld,
            //             'minPeriod' => $domain->min_period,
            //             'maxPeriod' => $domain->max_period,
            //             'registrationPrice' => $domain->domainregister,
            //             'renewalPrice' => $domain->domainrenew,
            //             'transferPrice' => $domain->domaintransfer,
            //             'currencyCode' => $domain->currency_code
            //         ];
            //     }

            //     $data = $helper->domainTable(($formattedDomainList), $_POST);
            //     echo $data;
            //     exit;
            // }

            if (($whmcs->get_req_var("ajaxaction") == "Create Domain") && ($whmcs->get_req_var("ajaxcall") == "true")) {

                $data = html_entity_decode($whmcs->get_req_var("data"));
                parse_str($data, $dataArray);

                $finalDomain = [];

                foreach ($dataArray['checkbox'] as $key) {
                    // Using the $key to get the corresponding data from other arrays
                    $finalDomain[$key] = [
                        'tld' => $dataArray['tld'][$key],
                        'domainregister' => $dataArray['registration_price'][$key],
                        'domainrenew' => $dataArray['renewal_price'][$key],
                        'domaintransfer' => $dataArray['transfer_price'][$key],
                        'currency_code' => $dataArray['currency_code'][$key],
                        'min_period' => $dataArray['min_period'][$key],
                        'max_period' => $dataArray['max_period'][$key],
                    ];
                }

                if (empty($finalDomain)) {
                    $helper->sendResponse(false, 'Tlds Not selected');
                }

                foreach ($finalDomain as $key => $domain) {
                    $existingDomain = Capsule::table('tbldomainpricing')->where('extension', $domain['tld'])->first();

                    $domainId = '';
                    if (empty($existingDomain)) {
                        $domainId = Capsule::table('tbldomainpricing')->insertGetId([
                            'extension' => $domain['tld'],
                        ]);
                    } else {
                        $domainId = $existingDomain->id;
                    }

                    $tldData = [
                        "domain_id" => $domainId,
                        "extension" => $domain['tld'],
                    ];

                    $helper->insertUpdate('mod_domain_status', ['domain_id' => $domainId, 'extension' => $domain['tld']], $tldData);

                    // $domainInfoData = [
                    //     "domain_id" => $domainId,
                    //     "tld" => $domain['tld'],
                    //     "currency_code" => $domain['currency_code'],
                    //     "domainregister" => $domain['domainregister'],
                    //     "domainrenew" => $domain['domainrenew'],
                    //     "domaintransfer" => $domain['domaintransfer'],
                    //     // "min_period" => $domain['min_period'],
                    //     // "max_period" => $domain['max_period'],
                    // ];

                    // $helper->insertUpdate('mod_domain_info', ['domain_id' => $domainId, 'tld' => $domain['tld']], $domainInfoData);

                    $productPrices = $helper->domainPrice($domain);
                    $updateproductprice = $helper->updateprice($domain['currency_code'], $domainId, $productPrices);

                    if ($updateproductprice != 'success') {
                        $helper->sendResponse(false, $lang['sync_error']);
                    }
                }

                $helper->sendResponse(true, $lang['sync_success']);
            }

            $this->tplFileName = $this->tplVar['tab'] = __FUNCTION__;
            $this->output();
        } catch (\Exception $e) {
            $this->tplVar['error'] = $e->getMessage();
        }
    }
    public function output($data = null)
    {
        try {
            $this->tplVar['data'] = $data;
            $this->smarty = new Smarty();
            $this->smarty->assign('tplVar', $this->tplVar);
            if (!empty($this->tplFileName)) {
                $this->smarty->display($this->tplVar['tplDIR'] . $this->tplFileName . '.tpl');
            } else {
                $this->tplVar['errorMsg'] = 'not found';
                $this->smarty->display($this->tplDIR . 'error.tpl');
            }
        } catch (\Exception $e) {
            $this->tplVar['error'] = $e->getMessage();
            $this->smarty->display($this->tplDIR . 'error.tpl');
        }
    }
}

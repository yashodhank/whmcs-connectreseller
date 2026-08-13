<?php

namespace WHMCS\Module\Addon\ConnectReseller\Admin;

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

use WHMCS\Database\Capsule;
use WHMCS\Module\Addon\ConnectReseller\Helper;
use WHMCS\Module\Addon\ConnectReseller\PriceSyncTask;
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
        $this->tplVar['csrfToken'] = function_exists('generate_token')
            ? generate_token('plain')
            : '';
    }

    /**
     * Verify admin CSRF when WHMCS token helpers are available.
     */
    private function requireAdminToken()
    {
        if (function_exists('check_token')) {
            check_token('WHMCS.admin.default');
        }
    }

    /**
     * @return int
     */
    private function requestDraw()
    {
        global $whmcs;
        $draw = 1;
        if (isset($_POST['draw'])) {
            $draw = (int) $_POST['draw'];
        } elseif ($whmcs && method_exists($whmcs, 'get_req_var')) {
            $draw = (int) $whmcs->get_req_var('draw');
        }

        return $draw > 0 ? $draw : 1;
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
                if ($formaction == 'checkall') {
                    $this->requireAdminToken();
                    Capsule::table('mod_domain_status')->update(['status' => 'on']);
                    $formSubmitMessage = ['status' => 'success', 'message' => $lang['TLDsStatusEnabled']];
                }
                if ($formaction == 'uncheckall') {
                    $this->requireAdminToken();
                    Capsule::table('mod_domain_status')->update(['status' => 'off']);
                    $formSubmitMessage = ['status' => 'success', 'message' => $lang['TLDsStatusDisabled']];
                }
                if ($formaction == 'runPriceSyncNow') {
                    $this->requireAdminToken();
                    $result = PriceSyncTask::run();
                    $resultText = (string) $result;
                    $isError = (stripos($resultText, 'error') !== false
                        || stripos($resultText, 'APIKey empty') !== false
                        || stripos($resultText, 'unavailable') !== false);
                    $formSubmitMessage = [
                        'status' => $isError ? 'error' : 'success',
                        'message' => 'Price sync result: ' . $resultText,
                    ];
                }
                if ($formaction == 'runKycNow') {
                    $this->requireAdminToken();
                    if (!class_exists('\\WHMCS\\Module\\Registrar\\ConnectReseller\\KycCron')) {
                        $cronFile = dirname(__DIR__, 3) . '/registrars/connectreseller/lib/KycCron.php';
                        if (is_readable($cronFile)) {
                            require_once dirname(__DIR__, 3) . '/registrars/connectreseller/lib/CronStateStore.php';
                            require_once dirname(__DIR__, 3) . '/registrars/connectreseller/lib/CapsuleCronStore.php';
                            require_once dirname(__DIR__, 3) . '/registrars/connectreseller/lib/CronGuard.php';
                            require_once $cronFile;
                        }
                    }
                    if (class_exists('\\WHMCS\\Module\\Registrar\\ConnectReseller\\KycCron')) {
                        $result = \WHMCS\Module\Registrar\ConnectReseller\KycCron::run();
                        $resultText = (string) $result;
                        $isError = (stripos($resultText, 'error') !== false
                            || stripos($resultText, 'APIKey empty') !== false
                            || stripos($resultText, 'unavailable') !== false);
                        $formSubmitMessage = [
                            'status' => $isError ? 'error' : 'success',
                            'message' => 'KYC cron result: ' . $resultText,
                        ];
                    } else {
                        $formSubmitMessage = [
                            'status' => 'error',
                            'message' => 'KYC cron unavailable (registrar module missing)',
                        ];
                    }
                }
            }

            if (($whmcs->get_req_var("ajaxaction") == "Enable/Disable TLD List") && ($whmcs->get_req_var("ajaxcall") == "true")) {
                $this->requireAdminToken();
                $data = $helper->tldsList($_POST);
                echo $data;
                exit;
            }

            if (($whmcs->get_req_var("ajaxaction") == "Enable/Disable TLD") && ($whmcs->get_req_var("ajaxcall") == "true")) {
                $this->requireAdminToken();

                if (empty($whmcs->get_req_var("tld"))) {
                    $message = ["status" => false, "message" => 'Something Went Wrong'];
                    echo json_encode($message);
                    exit;
                }

                $data = ['status' => $whmcs->get_req_var("status")];
                $condition = ['domain_id' => $whmcs->get_req_var("tld")];

                $updateReseller = $helper->insertUpdate('mod_domain_status', $condition, $data);
                if (is_string($updateReseller) && strpos($updateReseller, 'Error') !== false) {
                    $message = ["status" => false, "message" => $updateReseller];
                } else {
                    $message = ["status" => true, "message" => $updateReseller];
                }

                echo json_encode($message);
                exit;
            }

            if (($whmcs->get_req_var("ajaxaction") == "manual Sync TLDs") && ($whmcs->get_req_var("ajaxcall") == "true")) {
                $this->requireAdminToken();

                $allDomainList = $helper->fetch_table_record("tbldomainpricing", [], "");
                $params = $helper->CredentialRegistrar();
                if (empty($params['APIKey'])) {
                    $helper->sendResponse(false, 'Registrar API key is not configured.');
                }

                $allApiTld = $helper->get("tldsync?APIKey=" . $params['APIKey'], [], "Get Domain List");

                if ($helper->isTldSyncError($allApiTld['result'])) {
                    $helper->sendResponse(false, $helper->tldSyncErrorMessage($allApiTld['result']));
                }

                $byTld = array();
                foreach ($helper->normalizeTldSyncList($allApiTld['result']) as $products) {
                    $byTld[$products->tld] = $products;
                }

                foreach ($allDomainList as $tld) {
                    $domainId = $tld->id;
                    $whmcsExtension = $tld->extension;
                    $where = ['domain_id' => $domainId, "extension" => $whmcsExtension];
                    $status = $helper->fetch_table_record('mod_domain_status', $where, 'singleValue', 'status');
                    if ($status == "off" || !isset($byTld[$whmcsExtension])) {
                        continue;
                    }
                    $products = $byTld[$whmcsExtension];
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
                $helper->sendResponse(true, $lang['sync_success']);
            }

            // check 
            $offTldStatus = Capsule::table('mod_domain_status')->where('status','off')->count();
            if($offTldStatus == 0){
                $checkboxStatus ='true';
            }

            $tldRowCount = Capsule::table('mod_domain_status')->count();

            $this->tplFileName = $this->tplVar['tab'] = __FUNCTION__;
            $this->tplVar['formSubmitMessage'] = $formSubmitMessage;
            $this->tplVar['checkboxStatus'] = $checkboxStatus;
            $this->tplVar['cronStatus'] = $this->buildCronStatus();
            $this->tplVar['showAutomationEmpty'] = ($tldRowCount === 0);
            $this->output();
        } catch (\Exception $e) {
            $this->tplVar['error'] = $e->getMessage();
        }
    }

    /**
     * @return array<string, string>
     */
    private function buildCronStatus()
    {
        $status = array(
            'price_last_run' => '',
            'price_cursor' => '',
            'kyc_last_run' => '',
            'kyc_cursor' => '',
            'price_lock' => '',
            'kyc_lock' => '',
        );
        try {
            $rows = Capsule::table('tblconfiguration')
                ->whereIn('setting', array(
                    'ConnectResellerPriceSyncLastRun',
                    'ConnectResellerPriceSyncCursor',
                    'ConnectResellerPriceSyncLock',
                    'ConnectResellerKycCronLastRun',
                    'ConnectResellerKycCronCursor',
                    'ConnectResellerKycCronLock',
                ))
                ->get();
            $map = array();
            foreach ($rows as $row) {
                $map[$row->setting] = $row->value;
            }
            if (!empty($map['ConnectResellerPriceSyncLastRun'])) {
                $status['price_last_run'] = date('Y-m-d H:i:s', (int) $map['ConnectResellerPriceSyncLastRun']);
            }
            $status['price_cursor'] = isset($map['ConnectResellerPriceSyncCursor'])
                ? (string) $map['ConnectResellerPriceSyncCursor']
                : '';
            $status['kyc_last_run'] = isset($map['ConnectResellerKycCronLastRun'])
                ? (string) $map['ConnectResellerKycCronLastRun']
                : '';
            $status['kyc_cursor'] = isset($map['ConnectResellerKycCronCursor'])
                ? (string) $map['ConnectResellerKycCronCursor']
                : '';
            $status['price_lock'] = isset($map['ConnectResellerPriceSyncLock'])
                ? (string) $map['ConnectResellerPriceSyncLock']
                : '';
            $status['kyc_lock'] = isset($map['ConnectResellerKycCronLock'])
                ? (string) $map['ConnectResellerKycCronLock']
                : '';
        } catch (\Exception $e) {
            // ignore — admin page still renders
        }

        return $status;
    }
    public function domainsync($vars)
    {
        try {
            global $whmcs;
            $helper = new Helper();
            $lang = $this->tplVar['lang'];

            $params = $helper->CredentialRegistrar();

            if (($whmcs->get_req_var("ajaxaction") == "Get Domain Sync") && ($whmcs->get_req_var("ajaxcall") == "true")) {
                $this->requireAdminToken();
                $draw = $this->requestDraw();

                if (empty($params['APIKey'])) {
                    echo $helper->dataTablesPayload(
                        $draw,
                        array(),
                        false,
                        'Registrar API key is not configured.',
                        0,
                        0
                    );
                    exit;
                }

                $allDomainList = $helper->get("tldsync?APIKey=" . $params['APIKey'], [], "Get Domain List");

                if ($helper->isTldSyncError($allDomainList['result'])) {
                    echo $helper->dataTablesPayload(
                        $draw,
                        array(),
                        false,
                        $helper->tldSyncErrorMessage($allDomainList['result']),
                        0,
                        0
                    );
                    exit;
                }

                $tldRows = $helper->normalizeTldSyncList($allDomainList['result']);
                $data = $helper->domainTable($tldRows, $_POST);
                echo $data;
                exit;
            }

            if (($whmcs->get_req_var("ajaxaction") == "Create Domain") && ($whmcs->get_req_var("ajaxcall") == "true")) {
                $this->requireAdminToken();

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

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
    private const MODULE_VERSION = '3.0.5';

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
        $moduleLink = isset($params['modulelink']) ? (string) $params['modulelink'] : '';
        $moduleBaseLink = $this->stripActionFromModuleLink($moduleLink);
        global $whmcs;
        $action = '';
        if ($whmcs && method_exists($whmcs, 'get_req_var')) {
            $action = (string) $whmcs->get_req_var('action');
        }
        $moduleLink = $this->buildActionLink($moduleBaseLink, $action);
        $this->tplVar['moduleLink'] = $moduleLink;
        $this->tplVar['moduleBaseLink'] = $moduleBaseLink;
        $this->tplVar['domainsyncLink'] = $this->buildActionLink($moduleBaseLink, 'domainsync');
        $this->tplVar['enabledisableLink'] = $this->buildActionLink($moduleBaseLink, 'enabledisable');
        $this->tplVar['module'] = $params['module'];
        $this->tplVar['tplDIR'] = ROOTDIR . "/modules/addons/{$params['module']}/templates/admin/";
        $this->tplVar['header'] = ROOTDIR . "/modules/addons/{$params['module']}/templates/admin/header.tpl";
        $this->tplVar['cssPath'] = $CONFIG["SystemURL"] . "/modules/addons/{$params['module']}/assets/css/";
        $this->tplVar['scriptPath'] = $CONFIG["SystemURL"] . "/modules/addons/{$params['module']}/assets/js/";
        $this->tplVar['csrfToken'] = function_exists('generate_token')
            ? generate_token('plain')
            : '';
        $this->tplVar['moduleVersion'] = self::MODULE_VERSION;
    }

    /**
     * @param string $moduleLink
     * @return string
     */
    private function stripActionFromModuleLink($moduleLink)
    {
        $clean = preg_replace('/([?&])action=[^&]*(&)?/', '$1', (string) $moduleLink);
        $clean = preg_replace('/[?&]$/', '', (string) $clean);
        $clean = str_replace('?&', '?', (string) $clean);

        return (string) $clean;
    }

    /**
     * @param string $moduleBaseLink
     * @param string $action
     * @return string
     */
    private function buildActionLink($moduleBaseLink, $action)
    {
        $moduleBaseLink = (string) $moduleBaseLink;
        $action = (string) $action;
        if ($moduleBaseLink === '' || $action === '' || $action === 'domainsync') {
            return $moduleBaseLink;
        }

        return $moduleBaseLink
            . (strpos($moduleBaseLink, '?') !== false ? '&' : '?')
            . 'action=' . rawurlencode($action);
    }

    /**
     * @return bool
     */
    private function isAjaxCall()
    {
        global $whmcs;
        if (!$whmcs || !method_exists($whmcs, 'get_req_var')) {
            return false;
        }

        return (string) $whmcs->get_req_var('ajaxcall') === 'true';
    }

    /**
     * @param string $body
     * @return void
     */
    private function emitJson($body)
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        if (!headers_sent()) {
            http_response_code(200);
            header('Content-Type: application/json; charset=utf-8');
            header('X-Content-Type-Options: nosniff');
        }
        echo $body;
        exit;
    }

    /**
     * @param bool $status
     * @param string $message
     * @param array $extra
     * @return void
     */
    private function emitJsonResponse($status, $message, array $extra = array())
    {
        $helper = new Helper();
        $payload = array_merge(array(
            'status' => (bool) $status,
            'message' => (string) $message,
        ), $extra);

        $this->emitJson($helper->encodeJson($payload));
    }

    /**
     * @param int $draw
     * @param string $message
     * @param int $recordsTotal
     * @param int $recordsFiltered
     * @return void
     */
    private function emitDataTablesError($draw, $message, $recordsTotal = 0, $recordsFiltered = 0)
    {
        $helper = new Helper();
        $this->emitJson($helper->dataTablesPayload(
            (int) $draw,
            array(),
            false,
            (string) $message,
            (int) $recordsTotal,
            (int) $recordsFiltered
        ));
    }

    /**
     * Verify admin CSRF. AJAX uses non-fatal hash_equals so failures return JSON
     * instead of WHMCS HTML from check_token()/die().
     *
     * @param bool $forAjax
     * @param int|null $draw DataTables draw counter when $forAjax
     * @return void
     */
    private function requireAdminToken($forAjax = false, $draw = null)
    {
        if ($forAjax) {
            $token = '';
            if (isset($_POST['token'])) {
                $token = (string) $_POST['token'];
            } elseif (isset($_REQUEST['token'])) {
                $token = (string) $_REQUEST['token'];
            }
            $expected = function_exists('generate_token') ? (string) generate_token('plain') : '';
            if ($expected === '' || $token === '' || !hash_equals($expected, $token)) {
                if ($draw !== null) {
                    $this->emitDataTablesError(
                        (int) $draw,
                        'Invalid CSRF token. Reload the page and try again.'
                    );
                }

                $this->emitJsonResponse(false, 'Invalid CSRF token. Reload the page and try again.');
            }

            return;
        }

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
                try {
                    $draw = $this->requestDraw();
                    $this->requireAdminToken(true, $draw);
                    $this->emitJson($helper->tldsList($_POST));
                } catch (\Exception $e) {
                    $this->emitDataTablesError(
                        $this->requestDraw(),
                        'Automation list failed: ' . $e->getMessage()
                    );
                }
            }

            if (($whmcs->get_req_var("ajaxaction") == "Enable/Disable TLD") && ($whmcs->get_req_var("ajaxcall") == "true")) {
                try {
                    $this->requireAdminToken(true);
                    if (empty($whmcs->get_req_var("tld"))) {
                        $this->emitJsonResponse(false, 'Missing TLD identifier.');
                    }

                    $data = ['status' => $whmcs->get_req_var("status")];
                    $condition = ['domain_id' => $whmcs->get_req_var("tld")];

                    $updateReseller = $helper->insertUpdate('mod_domain_status', $condition, $data);
                    if (is_string($updateReseller) && strpos($updateReseller, 'Error') !== false) {
                        $this->emitJsonResponse(false, $updateReseller);
                    }
                    $this->emitJsonResponse(true, $updateReseller);
                } catch (\Exception $e) {
                    $this->emitJsonResponse(false, 'Toggle failed: ' . $e->getMessage());
                }
            }

            if (($whmcs->get_req_var("ajaxaction") == "manual Sync TLDs") && ($whmcs->get_req_var("ajaxcall") == "true")) {
                try {
                    $this->requireAdminToken(true);

                    $allDomainList = $helper->fetch_table_record("tbldomainpricing", [], "");
                    $params = $helper->CredentialRegistrar();
                    if (empty($params['APIKey'])) {
                        $this->emitJsonResponse(false, 'Registrar API key is not configured.');
                    }

                    $allApiTld = $helper->get("tldsync?APIKey=" . $params['APIKey'], [], "Get Domain List");

                    if ($helper->isTldSyncError($allApiTld['result'])) {
                        $this->emitJsonResponse(false, $helper->tldSyncErrorMessage($allApiTld['result']));
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
                            $this->emitJsonResponse(false, $lang['sync_error']);
                        }
                    }
                    $this->emitJsonResponse(true, $lang['sync_success']);
                } catch (\Exception $e) {
                    $this->emitJsonResponse(false, 'Manual sync failed: ' . $e->getMessage());
                }
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
            if ($this->isAjaxCall()) {
                $this->emitJsonResponse(false, $e->getMessage());
            }
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
                $draw = $this->requestDraw();
                try {
                    $this->requireAdminToken(true, $draw);

                    if (empty($params['APIKey'])) {
                        $this->emitDataTablesError($draw, 'Registrar API key is not configured.');
                    }

                    $allDomainList = $helper->get("tldsync?APIKey=" . $params['APIKey'], [], "Get Domain List");

                    if ($helper->isTldSyncError($allDomainList['result'])) {
                        $this->emitDataTablesError(
                            $draw,
                            $helper->tldSyncErrorMessage($allDomainList['result'])
                        );
                    }

                    $tldRows = $helper->normalizeTldSyncList($allDomainList['result']);
                    $this->emitJson($helper->domainTable($tldRows, $_POST));
                } catch (\Exception $e) {
                    $this->emitDataTablesError($draw, 'Sync TLDs failed: ' . $e->getMessage());
                }
            }

            if (($whmcs->get_req_var("ajaxaction") == "Create Domain") && ($whmcs->get_req_var("ajaxcall") == "true")) {
                try {
                    $this->requireAdminToken(true);

                    $data = html_entity_decode($whmcs->get_req_var("data"));
                    parse_str($data, $dataArray);

                    $finalDomain = [];

                    if (empty($dataArray['checkbox']) || !is_array($dataArray['checkbox'])) {
                        $this->emitJsonResponse(false, 'TLDs not selected.');
                    }

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
                        $this->emitJsonResponse(false, 'TLDs not selected.');
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
                            $this->emitJsonResponse(false, $lang['sync_error']);
                        }
                    }

                    $this->emitJsonResponse(true, $lang['sync_success']);
                } catch (\Exception $e) {
                    $this->emitJsonResponse(false, 'Import failed: ' . $e->getMessage());
                }
            }

            $this->tplFileName = $this->tplVar['tab'] = __FUNCTION__;
            $this->output();
        } catch (\Exception $e) {
            if ($this->isAjaxCall()) {
                $this->emitJsonResponse(false, $e->getMessage());
            }
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

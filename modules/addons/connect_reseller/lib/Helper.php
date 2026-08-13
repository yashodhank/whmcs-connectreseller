<?php

namespace WHMCS\Module\Addon\ConnectReseller;

use WHMCS\Database\Capsule;

use WHMCS\Module\Addon\ConnectReseller\Schema;


if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

require_once dirname(__DIR__, 3) . '/registrars/connectreseller/lib/Sensitive.php';
require_once dirname(__DIR__, 3) . '/registrars/connectreseller/lib/ApiClient.php';

class Helper
{
    public $baseUrl = "https://api.connectreseller.com/ConnectReseller/ESHOP/";

    public function cerateTable()
    {
        try {
            Schema::CreateDBTable();
        } catch (\Exception $e) {
            return [
                'status' => "error",
                'description' => 'Unable to : create tables in ConnectReseller' . $e->getMessage(),
            ];
        }
    }
    public function dropTbale()
    {
        try {
            Schema::DeleteAllDBTable();
        } catch (\Exception $e) {
            return [
                "status" => "error",
                "description" => "Unable to drop smarttech module DB: {$e->getMessage()}",
            ];
        }
    }

    public function fetch_table_record($tableName, $conditions, $for, $columnValue = null, $order = null, $limit = null)
    {
        try {
            $query = Capsule::table($tableName);
            foreach ($conditions as $column => $value) {
                $query->where($column, $value);
            }
            if ($for == 'groupBy') {
                $query->groupBy(key($conditions));
            } elseif ($for == 'singleRowData') {
                return $query->first();
            } elseif ($for == 'countData') {
                return $query->count();
            } elseif ($for == 'deleteRow') {
                return $query->delete();
            } elseif ($for == 'singleValue') {
                return $query->value($columnValue);
            }
            if ($order) {
                $query->orderBy($order['column'], $order['direction']);
            }
            if ($limit) {
                $query->limit($limit);
            }

            return $query->get();
        } catch (\Exception $e) {
            return [
                'status' => "error",
                'description' => 'systembot wgs_fetch_table_record_systembot function: ' . $e->getMessage(),
            ];
        }
    }
    public function insertUpdate($table_name = '', $where = [], $data = null)
    {
        try {
            // Check if a record already exists
            $row = Capsule::table($table_name)->where($where)->first();
            if (is_null($row)) {
                // Insert data if no record found
                Capsule::table($table_name)->insertGetId($data);
                return "Data has been inserted successfully!";
            } else {
                // Update data if record exists
                Capsule::table($table_name)->where($where)->update($data);
                return "Data has been updated successfully!";
            }
        } catch (\Illuminate\Database\QueryException $e) {
            throw new \Exception('Error in inserting/updating data: ' . $e->getMessage());
        } catch (Exception $e) {
            throw new \Exception('Error in inserting/updating data: ' . $e->getMessage());
        }
    }

    function sendResponse($status, $message)
    {
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
        }
        $response = [
            'status' => $status,
            'message' => $message,
        ];
        echo $this->encodeJson($response);
        exit;
    }

    /**
     * Encode a JSON payload for DataTables AJAX. Never returns false.
     *
     * @param mixed $data
     * @return string
     */
    public function encodeJson($data)
    {
        $flags = JSON_UNESCAPED_UNICODE;
        if (defined('JSON_INVALID_UTF8_SUBSTITUTE')) {
            $flags |= JSON_INVALID_UTF8_SUBSTITUTE;
        }

        $json = json_encode($data, $flags);
        if ($json !== false) {
            return $json;
        }

        return json_encode(array(
            'draw' => 1,
            'recordsTotal' => 0,
            'recordsFiltered' => 0,
            'data' => array(),
            'status' => false,
            'message' => 'Failed to encode TLD table JSON',
        ), $flags);
    }

    /**
     * Escape a value for an HTML attribute.
     *
     * @param mixed $value
     * @return string
     */
    public function htmlAttr($value)
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Build one Sync TLDs DataTables row with well-formed, escaped HTML.
     *
     * @param object $item
     * @param int|string $key
     * @param float $setMargin
     * @param bool $tldExists
     * @return array
     */
    public function formatDomainTableRow($item, $key, $setMargin, $tldExists)
    {
        $tld = isset($item->tld) ? (string) $item->tld : '';
        $registration = isset($item->registrationPrice) ? (float) $item->registrationPrice : 0.0;
        $renewal = isset($item->renewalPrice) ? (float) $item->renewalPrice : 0.0;
        $transfer = isset($item->transferPrice) ? (float) $item->transferPrice : 0.0;
        $currency = isset($item->currencyCode) ? (string) $item->currencyCode : '';
        $minPeriod = isset($item->minPeriod) ? $item->minPeriod : '';
        $maxPeriod = isset($item->maxPeriod) ? $item->maxPeriod : '';
        $margin = is_numeric($setMargin) ? (float) $setMargin : 0.0;

        $registerPrice = number_format($registration + ($registration * $margin / 100), 2, '.', '');
        $renewPrice = number_format($renewal + ($renewal * $margin / 100), 2, '.', '');
        $transferPrice = number_format($transfer + ($transfer * $margin / 100), 2, '.', '');

        $existtld = $tldExists
            ? '<i class="fas fa-check text-success"></i>'
            : '<i class="fas fa-times"></i>';

        $marginLabel = ($margin == 0) ? '-' : $margin . '%';
        $marginHtml = '<span class="tld-margin-heading">' . $this->htmlAttr($marginLabel) . '</span>';

        return array(
            'checkbox' => '<input type="checkbox" name="checkbox[]" value="'
                . $this->htmlAttr($key) . '">',
            'existtld' => $existtld,
            'tld' => '<input type="text" name="tld[]" class="form-control tlds-import" value="'
                . $this->htmlAttr($tld) . '" readonly="readonly" />',
            'registration_price' => $this->formatPriceCell(
                'registration_price[]',
                $registerPrice,
                $registration,
                $margin,
                $marginHtml
            ),
            'renewal_price' => $this->formatPriceCell(
                'renewal_price[]',
                $renewPrice,
                $renewal,
                $margin,
                $marginHtml
            ),
            'transfer_price' => $this->formatPriceCell(
                'transfer_price[]',
                $transferPrice,
                $transfer,
                $margin,
                $marginHtml
            ),
            'currency_code' => '<input type="text" name="currency_code[]" class="form-control tlds-import" value="'
                . $this->htmlAttr($currency) . '" readonly="readonly" />',
            'min_period' => '<input type="hidden" name="min_period[]" value="'
                . $this->htmlAttr($minPeriod) . '" />',
            'max_period' => '<input type="hidden" name="max_period[]" value="'
                . $this->htmlAttr($maxPeriod) . '" />',
        );
    }

    /**
     * @param string $name
     * @param string $displayPrice
     * @param float $cost
     * @param float $margin
     * @param string $marginHtml
     * @return string
     */
    private function formatPriceCell($name, $displayPrice, $cost, $margin, $marginHtml)
    {
        $costHtml = ($margin > 0)
            ? '<span class="remote-pricing">' . $this->htmlAttr($cost) . '</span>'
            : '-';

        return '<span class="tld-pricingg-td">'
            . '<input type="text" name="' . $this->htmlAttr($name) . '" class="form-control" value="'
            . $this->htmlAttr($displayPrice) . '" readonly="readonly" />'
            . $costHtml
            . '</span>'
            . $marginHtml;
    }

    /**
     * DataTables-shaped JSON for Sync / Automation AJAX (errors and empty lists).
     *
     * @param int $draw
     * @param array $rows
     * @param bool $status
     * @param string $message
     * @param int|null $recordsTotal
     * @param int|null $recordsFiltered
     * @return string
     */
    public function dataTablesPayload($draw, array $rows = array(), $status = true, $message = '', $recordsTotal = null, $recordsFiltered = null)
    {
        $total = ($recordsTotal !== null) ? (int) $recordsTotal : count($rows);
        $filtered = ($recordsFiltered !== null) ? (int) $recordsFiltered : $total;

        return $this->encodeJson(array(
            'draw' => (int) $draw,
            'recordsTotal' => $total,
            'recordsFiltered' => $filtered,
            'data' => $rows,
            'status' => (bool) $status,
            'message' => (string) $message,
        ));
    }

    /**
     * tldsync success is a list of TLD objects; failures are error objects with statusCode.
     *
     * @param mixed $result
     * @return bool
     */
    public function isTldSyncError($result)
    {
        if ($result === null || $result === false || $result === '') {
            return true;
        }

        if (is_array($result)) {
            if ($result === array()) {
                return false;
            }
            // Numeric list of TLD rows
            if (isset($result[0]) || array_key_exists(0, $result)) {
                return false;
            }
            if (isset($result['tld'])) {
                return false;
            }
            if (isset($result['statusCode'])) {
                $code = (int) $result['statusCode'];

                return ($code !== 0 && $code !== 200);
            }

            return false;
        }

        if (is_object($result)) {
            if (isset($result->tld)) {
                return false;
            }
            // json_decode can yield ArrayObject-like lists as stdClass with numeric props;
            // a real error object has statusCode and no tld.
            if (isset($result->statusCode)) {
                $code = (int) $result->statusCode;

                return ($code !== 0 && $code !== 200);
            }

            return false;
        }

        return true;
    }

    /**
     * @param mixed $result
     * @return string
     */
    public function tldSyncErrorMessage($result)
    {
        if (is_object($result)) {
            if (!empty($result->responseText)) {
                return (string) $result->responseText;
            }
            if (!empty($result->message)) {
                return (string) $result->message;
            }
        }
        if (is_array($result)) {
            if (!empty($result['responseText'])) {
                return (string) $result['responseText'];
            }
            if (!empty($result['message'])) {
                return (string) $result['message'];
            }
        }

        return 'ConnectReseller API request failed';
    }

    /**
     * Normalize tldsync result to a list of TLD objects for domainTable().
     *
     * @param mixed $result
     * @return array
     */
    public function normalizeTldSyncList($result)
    {
        if (!is_array($result) && !is_object($result)) {
            return array();
        }

        $list = array();
        foreach ($result as $item) {
            if (is_object($item) && isset($item->tld)) {
                $list[] = $item;
            } elseif (is_array($item) && isset($item['tld'])) {
                $list[] = (object) $item;
            }
        }

        return $list;
    }

    public function __curlCall($method, $data = null, $apiEndUrl = null, $action = '')
    {
        $client = new \WHMCS\Module\Registrar\ConnectReseller\ApiClient();
        $arrayResult = $client->requestUrl($method, $this->baseUrl . $apiEndUrl, $data, $action);

        return array(
            'httpcode' => 200,
            'result' => json_decode(json_encode($arrayResult['result'])),
        );
    }

    public function get($url, $data = null, $action = '')
    {
        try {
            $response = $this->__curlCall("GET", $data, $url, $action);
            return $response;
        } catch (\Exception $e) {
            throw new \Exception('Error while getting data for ' . $action . ' : ' . $e->getMessage());
        }
    }

    public function post($url, $data = null, $action = '')
    {
        try {
            $response = $this->__curlCall("POST", $data, $url, $action);
            return $response;
        } catch (\Exception $e) {
            throw new \Exception('Error while creating ' . $action . ' : ' . $e->getMessage());
        }
    }

    public function put($url, $data = null, $action = '')
    {
        try {
            // $response = $this->__curlCall("PUT", $data, $url, $action);
            // return $response;
        } catch (\Throwable $th) {
            //throw $th;
        }
    }


    public function delete($url, $data = null, $action = '')
    {
        try {
            // $response = $this->__curlCall("DELETE", $data, $url, $action);
            // return $response;
        } catch (\Throwable $th) {
            //throw $th;
        }
    }

    public function domainTable($data, $table)
    {
        try {
            $setMargin = 0;

            $setMargin = Capsule::table("tbladdonmodules")
                ->where('module', 'connect_reseller')
                ->where('setting', 'margin')
                ->value('value');

            $setMargin = is_numeric($setMargin) ? (float)$setMargin : 0;

            $searchValue = isset($table['search']['value']) ? $table['search']['value'] : ''; // Search query
            $start = isset($table['start']) ? intval($table['start']) : 0; // Pagination start
            $length = isset($table['length']) ? intval($table['length']) : 10; // Number of records per page
            $draw = isset($table['draw']) ? intval($table['draw']) : 1; // Draw counter for DataTables

            $sourceData = is_array($data) ? $data : array();
            $filteredData = $sourceData;

            if ($searchValue !== '') {
                $filteredData = array_filter($sourceData, function ($item) use ($searchValue) {
                    return stripos($item->tld, $searchValue) !== false ||
                        stripos($item->registrationPrice, $searchValue) !== false ||
                        stripos($item->renewalPrice, $searchValue) !== false ||
                        stripos($item->transferPrice, $searchValue) !== false ||
                        stripos($item->currencyCode, $searchValue) !== false ||
                        stripos($item->minPeriod, $searchValue) !== false ||
                        stripos($item->maxPeriod, $searchValue) !== false;
                });
            }

            if ($length < 0) {
                $length = count($filteredData);
            }

            $paginatedData = array_slice($filteredData, $start, $length);
            $rows = array();

            foreach ($paginatedData as $key => $item) {
                $tldExists = Capsule::table("tbldomainpricing")
                    ->where('extension', $item->tld)
                    ->count() > 0;
                $rows[] = $this->formatDomainTableRow($item, $key, $setMargin, $tldExists);
            }

            $response = [
                'draw' => $draw,
                'recordsTotal' => count($sourceData),
                'recordsFiltered' => count($filteredData),
                'data' => $rows,
                'status' => true,
                'message' => (count($sourceData) === 0) ? 'No TLDs returned from the API' : '',
            ];

            return $this->encodeJson($response);
        } catch (\Exception $e) {
            $draw = isset($table['draw']) ? (int) $table['draw'] : 1;

            return $this->dataTablesPayload(
                $draw,
                array(),
                false,
                'Something went wrong: ' . $e->getMessage(),
                0,
                0
            );
        }
    }

    public function getWhmcsConversionRate()
    {
        try {
            $defaultEuroExchangeRates = [];

            foreach (\WHMCS\Utility\CurrencyExchange::fetchCurrentRates() as $key =>  $exchangeRate) {
                $defaultEuroExchangeRates[$key] = $exchangeRate;
            }

            return $defaultEuroExchangeRates;
        } catch (\Exception $e) {
            throw new \Exception('Error: ' . $e->getMessage());
        }
    }

    public function domainPrice($price, $marginEnable = false)
    {
        $setMargin = 0;

        if ($marginEnable === true) {

            $setMargin = Capsule::table("tbladdonmodules")
                ->where('module', 'connect_reseller')
                ->where('setting', 'margin')
                ->value('value');
        }
        $setMargin = is_numeric($setMargin) ? (float)$setMargin : 0;

        // $registerPrice = $price['domainregister'] + ($price['domainregister'] * $setMargin / 100);  // Register price per year
        // $renewPrice = $price['domainrenew'] + ($price['domainrenew'] * $setMargin / 100);        // Renew price per year
        // $transferPrice = $price['domaintransfer'] + ($price['domaintransfer'] * $setMargin / 100);  // Transfer price per year

        $registerPrice = $price['domainregister'] + ($price['domainregister'] * $setMargin / 100);  // Register price per year
        $renewPrice = $price['domainrenew'] + ($price['domainrenew'] * $setMargin / 100);        // Renew price per year
        $transferPrice = $price['domaintransfer'] + ($price['domaintransfer'] * $setMargin / 100);  // Transfer price per year



        $minPeriod = $price['min_period'];
        $maxPeriod = $price['max_period'];

        $productPrices = [
            'domainregister' => [],
            'domainrenew' => [],
            'domaintransfer' => []
        ];

        for ($year = $minPeriod; $year <= $maxPeriod; $year++) {
            $productPrices['domainregister'][$year . 'Year'] = $registerPrice * $year;
            $productPrices['domainrenew'][$year . 'Year'] = $renewPrice * $year;
            $productPrices['domaintransfer'][$year . 'Year'] = $transferPrice * $year;
        }

        return $productPrices;
    }

    public function CredentialRegistrar()
    {
        $domainregistrarsConfig = Capsule::table("tblregistrars")->where("registrar", "connectreseller")->get();

        $params = [];
        foreach ($domainregistrarsConfig as $setting) {
            $params[$setting->setting] = decrypt($setting->value);
        }
        return $params;
    }

    public function updateprice($productCurrency, $relid, $productPrices = [])
    {
        $currencies = $this->fetch_table_record("tblcurrencies", [], '');
        if (!isset($currencies[0])) {
            throw new \Exception('Error: Please enable at least one currency!');
        }

        $defaultEuroExchangeRates = $this->getWhmcsConversionRate();

        foreach ($currencies as $key => $currency) {

            foreach ($productPrices as $type => $prices) {
                $oneYear = ($productPrices[$type]["1Year"] == 0 ? 0 : round(($productPrices[$type]["1Year"] / $defaultEuroExchangeRates[$productCurrency]) * $defaultEuroExchangeRates[$currency->code], 5));
                $twoYear =  ($productPrices[$type]["2Year"] == 0 ? 0 : round(($productPrices[$type]["2Year"] / $defaultEuroExchangeRates[$productCurrency]) * $defaultEuroExchangeRates[$currency->code], 5));
                $threeYear =  ($productPrices[$type]["3Year"] == 0 ? 0 : round(($productPrices[$type]["3Year"] / $defaultEuroExchangeRates[$productCurrency]) * $defaultEuroExchangeRates[$currency->code], 5));
                $fourYear =  ($productPrices[$type]["4Year"] == 0 ? 0 : round(($productPrices[$type]["4Year"] / $defaultEuroExchangeRates[$productCurrency]) * $defaultEuroExchangeRates[$currency->code], 5));
                $fiveYear =  ($productPrices[$type]["5Year"] == 0 ? 0 : round(($productPrices[$type]["5Year"] / $defaultEuroExchangeRates[$productCurrency]) * $defaultEuroExchangeRates[$currency->code], 5));
                $sixYear =  ($productPrices[$type]["6Year"] == 0 ? 0 : round(($productPrices[$type]["6Year"] / $defaultEuroExchangeRates[$productCurrency]) * $defaultEuroExchangeRates[$currency->code], 5));
                $sevenYear =  ($productPrices[$type]["7Year"] == 0 ? 0 : round(($productPrices[$type]["7Year"] / $defaultEuroExchangeRates[$productCurrency]) * $defaultEuroExchangeRates[$currency->code], 5));
                $eightYear =  ($productPrices[$type]["8Year"] == 0 ? 0 : round(($productPrices[$type]["8Year"] / $defaultEuroExchangeRates[$productCurrency]) * $defaultEuroExchangeRates[$currency->code], 5));
                $nineYear =  ($productPrices[$type]["9Year"] == 0 ? 0 : round(($productPrices[$type]["9Year"] / $defaultEuroExchangeRates[$productCurrency]) * $defaultEuroExchangeRates[$currency->code], 5));
                $tenYear =  ($productPrices[$type]["10Year"] == 0 ? 0 : round(($productPrices[$type]["10Year"] / $defaultEuroExchangeRates[$productCurrency]) * $defaultEuroExchangeRates[$currency->code], 5));

                $oneYear = ($oneYear == 0 || $oneYear == "0.00" ? "-1.00" : $oneYear);
                $twoYear = ($twoYear == 0 || $twoYear == "0.00" ? "-1.00" : $twoYear);
                $threeYear = ($threeYear == 0 || $threeYear == "0.00" ? "-1.00" : $threeYear);
                $fourYear = ($fourYear == 0 || $fourYear == "0.00" ? "-1.00" : $fourYear);
                $fiveYear = ($fiveYear == 0 || $fiveYear == "0.00" ? "-1.00" : $fiveYear);
                $sixYear = ($sixYear == 0 || $sixYear == "0.00" ? "-1.00" : $sixYear);
                $sevenYear = ($sevenYear == 0 || $sevenYear == "0.00" ? "-1.00" : $sevenYear);
                $eightYear = ($eightYear == 0 || $eightYear == "0.00" ? "-1.00" : $eightYear);
                $nineYear = ($nineYear == 0 || $nineYear == "0.00" ? "-1.00" : $nineYear);
                $tenYear = ($tenYear == 0 || $tenYear == "0.00" ? "-1.00" : $tenYear);

                // // Use insertUpdate to insert or update the record for the specific currency
                // $this->insertUpdate('tblpricing', ['type' => $type, 'relid' => $relid, 'currency' => $currency->id], $domainRegisterData);

                // Fetch the pricing data for the current currency
                $select = $this->fetch_table_record("tblpricing", ["type" => $type, "relid" => $relid, 'currency' => $currency->id], "singleRowData");

                // Insert or update the pricing data
                if (empty($select) || !isset($select->id)) {
                    Capsule::table('tblpricing')->insertGetId(array(
                        'type' => $type,
                        'relid' => $relid,
                        'msetupfee' => $oneYear,
                        'qsetupfee' => $twoYear,
                        'ssetupfee' => $threeYear,
                        'asetupfee' => $fourYear,
                        'bsetupfee' => $fiveYear,
                        'monthly' => $sixYear,
                        'quarterly' => $sevenYear,
                        'semiannually' => $eightYear,
                        'annually' => $nineYear,
                        'biennially' => $tenYear,
                        'currency' => $currency->id
                    ));
                } else {
                    Capsule::table('tblpricing')->where("type", $type)->where("relid", $relid)->where('currency', $currency->id)->update([
                        'msetupfee' => $oneYear,
                        'qsetupfee' => $twoYear,
                        'ssetupfee' => $threeYear,
                        'asetupfee' => $fourYear,
                        'bsetupfee' => $fiveYear,
                        'monthly' => $sixYear,
                        'quarterly' => $sevenYear,
                        'semiannually' => $eightYear,
                        'annually' => $nineYear,
                        'biennially' => $tenYear
                    ]);
                }
            }
        }

        return "success";
    }

    public function tldsList($data)
    {
        $draw = isset($data['draw']) ? (int) $data['draw'] : 1;

        try {
            $countTotal = Capsule::table('mod_domain_status')->count();
            if ($countTotal === 0) {
                return $this->dataTablesPayload(
                    $draw,
                    array(),
                    true,
                    'No TLDs configured yet. Import TLDs on the Sync TLDs tab first.',
                    0,
                    0
                );
            }

            $columnNumber = isset($data['order'][0]['column']) ? $data['order'][0]['column'] : 0;
            $ordercolumn = isset($data['order'][0]['dir']) ? $data['order'][0]['dir'] : 'asc';
            $searchValue = isset($data['search']['value']) ? $data['search']['value'] : '';

            $columns = array('domain_id', 'extension', 'status');
            $columnName = isset($columns[$columnNumber]) ? $columns[$columnNumber] : 'extension';

            $applySearch = function ($query) use ($searchValue) {
                if ($searchValue !== '') {
                    $query->where(function ($q) use ($searchValue) {
                        $q->where('extension', 'LIKE', '%' . $searchValue . '%')
                            ->orWhere('status', 'LIKE', '%' . $searchValue . '%');
                    });
                }

                return $query;
            };

            $filteredCount = $applySearch(Capsule::table('mod_domain_status'))->count();

            $start = isset($data['start']) ? (int) $data['start'] : 0;
            $length = isset($data['length']) ? (int) $data['length'] : 10;

            if ($length <= 0) {
                $length = $filteredCount > 0 ? $filteredCount : 10;
            }

            $listPagesData = $applySearch(Capsule::table('mod_domain_status'))
                ->orderBy($columnName, $ordercolumn)
                ->offset($start)
                ->limit($length)
                ->get();

            $dataArray = array();
            foreach ($listPagesData as $log) {
                $dataArray[] = array(
                    'extension' => $log->extension,
                    'status' => '<input type="checkbox" class="toggle-checkbox" name="enable_tld" id="toggle-btn' . $log->domain_id . '" 
                    tld_id="' . $log->domain_id . '" 
                    data-status="' . ($log->status == 'on' ? 'on' : 'off') . '" 
                    ' . ($log->status == 'on' ? 'checked' : '') . '>
                    <label for="toggle-btn' . $log->domain_id . '" class="toggle-label"></label>',
                );
            }

            return $this->dataTablesPayload(
                $draw,
                $dataArray,
                true,
                '',
                $countTotal,
                $filteredCount
            );
        } catch (\Exception $e) {
            return $this->dataTablesPayload(
                $draw,
                array(),
                false,
                'Something went wrong: ' . $e->getMessage(),
                0,
                0
            );
        }
    }
}

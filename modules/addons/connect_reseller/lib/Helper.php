<?php

namespace WHMCS\Module\Addon\ConnectReseller;

use WHMCS\Database\Capsule;

use WHMCS\Module\Addon\ConnectReseller\Schema;


if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

require_once dirname(__DIR__, 3) . '/registrars/connectreseller/lib/Sensitive.php';

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
        $response = [
            'status' => $status,
            'message' => $message,
        ];
        echo json_encode($response);
        exit;
    }

    public function __curlCall($method, $data = null, $apiEndUrl = null, $action = '')
    {
        $url = $this->baseUrl . $apiEndUrl;
        $url = \WHMCS\Module\Registrar\ConnectReseller\Sensitive::normalizeUrl($url);

        $curl = curl_init();
        $payload = (is_array($data) && count($data) > 0) ? json_encode($data) : "";
        switch ($method) {
            case 'POST':
                curl_setopt($curl, CURLOPT_POST, 1);
                curl_setopt($curl, CURLOPT_POSTFIELDS, $payload);
                break;
            case 'PUT':
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PUT');
                curl_setopt($curl, CURLOPT_POSTFIELDS, $payload);
                break;
            case 'DELETE':
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
                curl_setopt($curl, CURLOPT_POSTFIELDS, $payload);
                break;

            default:
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');
        }

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 0);
        curl_setopt($curl, CURLOPT_MAXREDIRS, 10);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($curl, CURLOPT_HTTPHEADER, [
            "Content-Type: application/json"
        ]);
        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        if (curl_errno($curl)) {
            throw new \Exception(curl_error($curl));
        }
        curl_close($curl);
        $decoded = json_decode($response);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Invalid JSON from ConnectReseller: ' . json_last_error_msg());
        }
        logModuleCall(
            "ConnectReseller",
            $action,
            \WHMCS\Module\Registrar\ConnectReseller\Sensitive::redact($data),
            \WHMCS\Module\Registrar\ConnectReseller\Sensitive::redact(json_decode($response, true))
        );

        return ['httpcode' => $httpCode, 'result' => $decoded];
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

            $paginatedData = array_slice($filteredData, $start, $length);
            $rows = array();

            foreach ($paginatedData as $key => $item) {

                $registerPrice = '';
                $renewPrice = '';
                $transferPrice = '';

                // $registerPrice = $item->registrationPrice + ($item->registrationPrice * $setMargin / 100);
                // $renewPrice = $item->renewalPrice + ($item->renewalPrice * $setMargin / 100);
                // $transferPrice = $item->transferPrice + ($item->transferPrice * $setMargin / 100);

                $registerPrice = number_format($item->registrationPrice + ($item->registrationPrice * $setMargin / 100), 2);
                $renewPrice = number_format($item->renewalPrice + ($item->renewalPrice * $setMargin / 100), 2);
                $transferPrice = number_format($item->transferPrice + ($item->transferPrice * $setMargin / 100), 2);

                $existtld = "";
                $checkDomainExist = Capsule::table("tbldomainpricing")
                    ->where('extension', $item->tld)
                    ->count();

                if ($checkDomainExist > 0) {
                    $existtld .= '<i class="fas fa-check text-success"></i>';
                } else {
                    $existtld .= '<i class="fas fa-times"></i>';
                }

                $registerPriceHtml = $renewPriceHtml = $transferPriceHtml = '';

                if (($setMargin > 0)) {
                    $registerPriceHtml .= '<span class="remote-pricing">' . $item->registrationPrice . '</span>';
                    $renewPriceHtml .= '<span class="remote-pricing">' . $item->renewalPrice . '</span>';
                    $transferPriceHtml .= '<span class="remote-pricing">' . $item->transferPrice . '</span>';
                } else {
                    $registerPriceHtml = $renewPriceHtml = $transferPriceHtml = '-';
                }

                $marginValue = '';
                $marginValue .= '<span class="tld-margin-heading">' . (($setMargin == 0) ? '-' : $setMargin . "%") . '</span>';

                $rows[] = [
                    'checkbox' => '<input type="checkbox" name="checkbox[]" value="' . $key . '">',  // Checkbox stays as it is
                    'existtld' => $existtld,  // Checkbox stays as it is
                    'tld' => '<input type="text" name="tld[]" class="form-control tlds-import" value="' . $item->tld . '" / readonly>',  // tld as text input
                    'registration_price' => '<span class="tld-pricingg-td"><input type="text" name="registration_price[]" class="form-control" value="' . $registerPrice  . '" / readonly>' . $registerPriceHtml ."</span>". $marginValue . '',  // registration_price as text input
                    'renewal_price' => '<span class="tld-pricingg-td"><input type="text" name="renewal_price[]" class="form-control" value="' . $renewPrice  . '" / readonly>' . $renewPriceHtml ."</span>". $marginValue . '',  // renewal_price as text input
                    'transfer_price' => '<span class="tld-pricingg-td"><input type="text" name="transfer_price[]" class="form-control" value="' . $transferPrice  . '" / readonly>' . $transferPriceHtml ."</span>" . $marginValue . '',  // transfer_price as text input
                    'currency_code' => '<input type="text" name="currency_code[]" class="form-control tlds-import" value="' . $item->currencyCode . '" / readonly>',  // currency_code as text input
                    'min_period' => '<input type="hidden" name="min_period[]" class="form-control" value="' . $item->minPeriod . '" / readonly>',  // min_period as text input
                    'max_period' => '<input type="hidden" name="max_period[]" class="form-control" value="' . $item->maxPeriod . '" / readonly>',  // max_period as text input
                ];
            }

            $response = [
                'draw' => $draw,
                'recordsTotal' => count($sourceData),
                'recordsFiltered' => count($filteredData),
                'data' => $rows,
            ];

            return json_encode($response);
        } catch (\Exception $e) {
            return json_encode([
                'status' => 'error',
                'description' => 'Something went wrong: ' . $e->getMessage(),
            ], JSON_UNESCAPED_SLASHES);
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
        try {
            date_default_timezone_set('Asia/Qatar');

            $columnNumber = $data['order'][0]['column'];
            $ordercolumn = $data['order'][0]['dir'];
            $searchValue = $data['search']['value'];

            $columns = ['domain_id', 'extension', 'status'];
            $columnName = isset($columns[$columnNumber]) ? $columns[$columnNumber] : 'extension'; // Default to extension if out of range

            $query = Capsule::table('mod_domain_status');

            if ($searchValue !== '') {
                $query->where(function ($q) use ($searchValue) {
                    $q->where('extension', 'LIKE', '%' . $searchValue . '%')
                        ->orWhere('status', 'LIKE', '%' . $searchValue . '%');
                });
            }

            $start = isset($data['start']) ? $data['start'] : 0;
            $length = isset($data['length']) ? $data['length'] : 10; // Default length

            if ($length == "0") {
                $length = Capsule::table("mod_domain_status")->count();
            }

            $listPagesData = $query->orderBy($columnName, $ordercolumn)
                ->offset($start)
                ->limit($length)
                ->get();

            $countTotal = Capsule::table("mod_domain_status")->count();

            $dataArray = [];
            foreach ($listPagesData as $log) {
                $dataArray[] = [
                    'extension' => $log->extension,
                    'status' => '<input type="checkbox" class="toggle-checkbox" name="enable_tld" id="toggle-btn' . $log->domain_id . '" 
                    tld_id="' . $log->domain_id . '" 
                    data-status="' . ($log->status == 'on' ? 'on' : 'off') . '" 
                    ' . ($log->status == 'on' ? 'checked' : '') . '>
                    <label for="toggle-btn' . $log->domain_id . '" class="toggle-label"></label>',
                ];
            }

            $response = [
                'draw' => intval($data['draw']),
                'recordsTotal' => $countTotal,
                'recordsFiltered' => $countTotal,
                'data' => $dataArray,
            ];

            return json_encode($response);
        } catch (\Exception $e) {
            return json_encode([
                'status' => 'error',
                'description' => 'Something went wrong: ' . $e->getMessage(),
            ], JSON_UNESCAPED_SLASHES);
        }
    }
}

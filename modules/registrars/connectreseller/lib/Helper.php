<?php

namespace WHMCS\Module\Registrar\ConnectReseller;

use WHMCS\Database\Capsule;

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

/**
 * Google Cloud Domains Registrar Module Simple API Client.
 */
class Helper
{
    public $baseUrl = "https://api.connectreseller.com/ConnectReseller/ESHOP/";

    /** @var ApiClient */
    private $apiClient;

    /**
     * @param ApiClient|null $apiClient
     */
    public function __construct($apiClient = null)
    {
        $this->apiClient = $apiClient instanceof ApiClient ? $apiClient : new ApiClient();
    }

    public function __curlCall($method, $data = null, $apiEndUrl = null, $action = '')
    {
        $url = $this->baseUrl . $apiEndUrl;

        return $this->apiClient->requestUrl($method, $url, $data, $action);
    }

    function sendResponse($res)
    {
        $values["error"] = "Error: " . $res['responseMsg']['statusCode'] . " " . $res['responseMsg']['message'];
        return $values;
    }

    function nexusCategory($params)
    {
        if (
            $params["additionalfields"]["Nexus Category"] == "C31"
            || $params["additionalfields"]["Nexus Category"] == "C32"
        ) {
            $NexusCategory = $params["additionalfields"]["Nexus Category"] . "/CC";
        } else {
            $NexusCategory = $params["additionalfields"]["Nexus Category"];
        }
        return $NexusCategory;
    }
    function appPurpose($params, $defult)
    {
        if ($params["additionalfields"]["Application Purpose"] == "Non-profit business") {
            $appPurpose = "P2";
        } else if ($params["additionalfields"]["Application Purpose"] == "Club") {
            $appPurpose = "P1";
        } else if ($params["additionalfields"]["Application Purpose"] == "Association") {
            $appPurpose = "P3";
        } else if ($params["additionalfields"]["Application Purpose"] == "Religious Organization") {
            $appPurpose = "P3";
        } else if ($params["additionalfields"]["Application Purpose"] == "Personal Use") {
            $appPurpose = "P3";
        } else if ($params["additionalfields"]["Application Purpose"] == "Educational purposes") {
            $appPurpose = "P4";
        } else if ($params["additionalfields"]["Application Purpose"] == "Government purposes") {
            $appPurpose = "P5";
        } else {
            $appPurpose = $defult;
        }
        return $appPurpose;
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

    public function orderInfo($response)
    {
        try {
            $expirationDate = date('d-m-Y', ($response['result']['responseData']['expirationDate'] / 1000));

            $html = '';
            $html .= '<table width="100%" style="margin-bottom:0;" class="datatable">';
            $html .= '<tbody>';
            $html .= '<tr align="left">';
            $html .= '<th style="text-align: left;">Item</th>';
            $html .= '<th style="text-align: left;">Content</th>';
            $html .= '</tr>';
            $html .= '<tr align="left">';
            $html .= '<td>Domain Name</td>';
            $html .= '<td>' . Sensitive::escapeHtml($response['result']['responseData']['websiteName']) . '</td>';
            $html .= '</tr>';
            $html .= '<tr align="left">';
            $html .= '<td>Domain Status</td>';
            $statusLabel = (($response['result']['responseData']['status'] == "Locked") ? "Inactive" : $response['result']['responseData']['status']);
            $html .= '<td>' . Sensitive::escapeHtml($statusLabel) . '</td>';
            $html .= '</tr>';
            $html .= '<tr align="left">';
            $html .= '<td>Domain Lock Status</td>';
            $lockLabel = (($response['result']['responseData']['isDomainLocked'] == "true") ? "Locked" : "Unlock");
            $html .= '<td>' . Sensitive::escapeHtml($lockLabel) . '</td>';
            $html .= '</tr>';
            $html .= '<tr align="left">';
            $html .= '<td>Expiration Date</td>';
            $html .= '<td>' . Sensitive::escapeHtml($expirationDate) . '</td>';
            $html .= '</tr>';
            $html .= '</tbody>';
            $html .= '</table>';

            return $html;
        } catch (\Illuminate\Database\QueryException $e) {
            throw new \Exception('Error in Order Info data: ' . $e->getMessage());
        } catch (Exception $e) {
            throw new \Exception('Error in Order Info data: ' . $e->getMessage());
        }
    }

    public function provideLangArray()
    {
        $arrayDomain = [
            "Arabic" => [
                'code' => 'ar',
                'tld' => [
                    '.ae.org',
                    '.press',
                    '.fm',
                    '.store',
                    '.art',
                    '.protection',
                    '.fun',
                    '.tech',
                    '.baby',
                    '.pw',
                    '.host',
                    '.theatre',
                    '.cam',
                    '.radio.am',
                    '.in.net',
                    '.us.com',
                    '.ceo',
                    '.radio.fm',
                    '.tickets',
                    '.us.org',
                    '.college',
                    '.rent',
                    '.monster',
                    '.website',
                    '.com.fm',
                    '.sa.com',
                    '.net.fm',
                    '.forum',
                    '.tickets',
                    '.security',
                    '.online',
                    '.org.fm',
                    '.edu.fm',
                    '.site',
                    '.org.fm',
                    '.space',
                    '.eu.com',
                    '.best',
                    '.storage'
                ]
            ],
            "Belarusian" => [
                'code' => 'be',
                'tld' => [
                    '.eu.com',
                    '.ooo'
                ]
            ],
            "Bulgarian" => [
                'code' => 'bg',
                'tld' => [
                    '.cam',
                    '.ooo'
                ]
            ],
            "Bosnian" => [
                'code' => 'bs',
                'tld' => [
                    '.cam'
                ]
            ],
            "Catalan" => [
                'code' => 'ca',
                'tld' => [
                    '.cam'
                ]
            ],
            "Czech" => [
                'code' => 'cs',
                'tld' => [
                    '.cam',
                    '.icu'
                ]
            ],
            "Danish" => [
                'code' => 'da',
                'tld' => [
                    '.art',
                    '.cam',
                    '.ceo',
                    '.eu.com',
                    '.qpon',
                    '.tickets',
                    '.icu',
                    '.best'
                ]
            ],
            "German" => [
                'code' => 'de',
                'tld' => [
                    '.audio',
                    '.auto',
                    '.best',
                    '.cam',
                    '.car',
                    '.cars',
                    '.ceo',
                    '.christmas',
                    '.dealer',
                    '.diet',
                    '.eu.com',
                    '.flowers',
                    '.game',
                    '.guitars',
                    '.help',
                    '.hosting',
                    '.ICU',
                    '.inc',
                    '.lol',
                    '.london',
                    '.mom',
                    '.pics',
                    '.qpon',
                    '.saarland',
                    '.tickets'
                ]
            ],
            "Spanish" => [
                'code' => 'es',
                'tld' => [
                    '.audio',
                    '.auto',
                    '.baby',
                    '.best',
                    '.cam',
                    '.car',
                    '.cars',
                    '.ceo',
                    '.christmas',
                    '.dealer',
                    '.diet',
                    '.lat',
                    '.eu.com',
                    '.flowers',
                    '.game',
                    '.guitars',
                    '.help',
                    '.hosting',
                    '.icu',
                    '.inc',
                    '.lol',
                    '.london',
                    '.mom',
                    '.ooo',
                    '.pics',
                    '.qpon',
                    '.tickets',
                    '.uno'
                ]
            ],
            "Estonian" => [
                'code' => 'et',
                'tld' => [
                    '.cam'
                ]
            ],
            "Finnish" => [
                'code' => 'fi',
                'tld' => [
                    '.best',
                    '.cam',
                    '.ceo',
                    '.qpon'
                ]
            ],
            "Faroese" => [
                'code' => 'fo',
                'tld' => [
                    '.eu.com'
                ]
            ],
            "French" => [
                'code' => 'fr',
                'tld' => [
                    '.cam',
                    '.ceo',
                    '.eu.com',
                    '.icu',
                    '.london',
                    '.ooo',
                    '.cars',
                    '.christmas',
                    '.audio',
                    '.auto',
                    '.best',
                    '.car',
                    '.dealer',
                    '.diet',
                    '.flowers',
                    '.game',
                    '.hosting',
                    '.inc',
                    '.lol',
                    '.london',
                    '.qpon',
                    '.tickets',
                    '.guitars',
                    '.help',
                    '.mom',
                    '.pics'
                ]
            ],
            "Hebrew" => [
                'code' => 'he',
                'tld' => [
                    '.art',
                    '.baby',
                    '.cam',
                    '.college',
                    '.eu.com',
                    '.fm',
                    '.fun',
                    '.host',
                    '.icu',
                    '.in.net',
                    '.monster',
                    '.online',
                    '.ooo',
                    '.press',
                    '.protection',
                    '.radio.am',
                    '.pw',
                    '.radio.fm',
                    '.rent',
                    '.security',
                    '.site',
                    '.space',
                    '.storage',
                    '.store',
                    '.tech',
                    '.theatre',
                    '.tickets',
                    '.uno',
                    '.us.com',
                    '.us.org',
                    '.website',
                    '.xyz'
                ]
            ],
            "Hindi" => [
                'code' => 'hi',
                'tld' => [
                    '.ceo',
                    '.best'
                ]
            ],
            "Croatian" => [
                'code' => 'hr',
                'tld' => [
                    '.cam',
                    '.eu.com',
                    '.ooo'
                ]
            ],
            "Hungarian" => [
                'code' => 'hu',
                'tld' => [
                    '.cam',
                    '.ceo',
                    '.qpon',
                    '.best'
                ]
            ],
            "Icelandic" => [
                'code' => 'is',
                'tld' => [
                    '.best',
                    '.cam',
                    '.ceo',
                    '.icu',
                    '.qpon'
                ]
            ],
            "Italian" => [
                'code' => 'it',
                'tld' => [
                    '.audio',
                    '.auto',
                    '.best',
                    '.cam',
                    '.car',
                    '.cars',
                    '.dealer',
                    '.diet',
                    '.ceo',
                    '.christmas',
                    '.flowers',
                    '.game',
                    '.guitars',
                    '.help',
                    '.hosting',
                    '.icu',
                    '.inc',
                    '.lol',
                    '.london',
                    '.mom',
                    '.pics',
                    '.qpon'
                ]
            ],
            "Japanese" => [
                'code' => 'ja',
                'tld' => [
                    '.art',
                    '.ceo',
                    '.college',
                    '.in.net',
                    '.fm',
                    '.fun',
                    '.host',
                    '.jp.net',
                    '.jpn.com',
                    '.online',
                    '.ooo',
                    '.press',
                    '.protection',
                    '.pw',
                    '.radio.am',
                    '.radio.fm',
                    '.rent',
                    '.security',
                    '.storage',
                    '.store',
                    '.site',
                    '.space',
                    '.tech',
                    '.theatre',
                    '.us.com',
                    '.us.org',
                    '.website',
                    '.xyz',
                    '.baby',
                    '.cam',
                    '.icu',
                    '.monster',
                    '.auto',
                    '.car',
                    '.cars',
                    '.dealer',
                    '.hair',
                    '.makeup',
                    '.quest',
                    '.skin',
                    '.tickets',
                    '.uno',
                    '.inc',
                    '.mom',
                    '.diet',
                    '.flowers',
                    '.pics',
                    '.diet',
                    '.audio',
                    '.beauty',
                    '.best',
                    '.christmas',
                    '.game',
                    '.guitars',
                    '.hosting',
                    '.lol',
                    '.flowers'
                ]
            ],
            "Korean" => [
                'code' => 'ko',
                'tld' => [
                    '.art',
                    '.baby',
                    '.cam',
                    '.ceo',
                    '.edu.fm',
                    '.fm',
                    '.fun',
                    '.hair',
                    '.in.net',
                    '.uno',
                    '.makeup',
                    '.monster',
                    '.ooo',
                    '.org.fm',
                    '.press',
                    '.protection',
                    '.colleg',
                    '.com.fm',
                    '.rent',
                    '.security',
                    '.host',
                    '.icu',
                    '.store',
                    '.tech',
                    '.net.fm',
                    '.online',
                    '.xyz',
                    '.site',
                    '.pw',
                    '.qpon',
                    '.website',
                    '.theatre',
                    '.quest',
                    '.radio.am',
                    '.radio.fm',
                    '.beauty',
                    '.skin',
                    '.space',
                    '.storage',
                    '.best',
                    '.tickets',
                    '.uno'
                ]
            ]
            // Add the rest of the languages in the same pattern
        ];
        return  $arrayDomain;
    }
    public function whmcsLangArray()
    {
        $languageArray = [
            "Afrikaans" => "afr",
            "Albanian" => "alb",
            "Arabic" => "ara",
            "Aragonese" => "arg",
            "Armenian" => "arm",
            "Assamese" => "asm",
            "Asturian" => "ast",
            "Avestan" => "ave",
            "Awadhi" => "awa",
            "Azerbaijani" => "aze",
            "Balinese" => "ban",
            "Baluchi" => "bal",
            "Basa" => "bas",
            "Bashkir" => "bak",
            "Basque" => "baq",
            "Belarusian" => "bel",
            "Bengali" => "ben",
            "Bhojpuri" => "bho",
            "Bosnian" => "bos",
            "Bulgarian" => "bul",
            "Burmese" => "bur",
            "Carib" => "car",
            "Catalan" => "cat",
            "Chechen" => "che",
            "Chinese" => "chi",
            "Chuvash" => "chv",
            "Coptic" => "cop",
            "Corsican" => "cos",
            "Croatian" => "scr",
            "Czech" => "cze",
            "Danish" => "dan",
            "Divehi" => "div",
            "Dogri" => "doi",
            "Dutch" => "dut",
            "English" => "eng",
            "Estonian" => "est",
            "Faroese" => "fao",
            "Fijian" => "fij",
            "Finnish" => "fin",
            "French" => "fre",
            "Frisian" => "fry",
            "Gaelic; Scottish Gaelic" => "gla",
            "Georgian" => "geo",
            "German" => "ger",
            "Gondi" => "gon",
            "Greek" => "gre",
            "Gujarati" => "guj",
            "Hebrew" => "heb",
            "Hindi" => "hin",
            "Hungarian" => "hun",
            "Icelandic" => "ice",
            "Indic" => "inc",
            "Indonesian" => "ind",
            "Ingush" => "inh",
            "Irish" => "gle",
            "Italian" => "ita",
            "Japanese" => "jpn",
            "Javanese" => "jav",
            "Kashmiri" => "kas",
            "Kazakh" => "kaz",
            "Khmer" => "khm",
            "Kirghiz" => "kir",
            "Korean" => "kor",
            "Kurdish" => "kur",
            "Lao" => "lao",
            "Latin" => "lat",
            "Latvian" => "lav",
            "Lithuanian" => "lit",
            "Luxembourgish" => "ltz",
            "Macedonian" => "mac",
            "Malay" => "may",
            "Malayalam" => "mal",
            "Maltese" => "mlt",
            "Maori" => "mao",
            "Moldavian" => "mol",
            "Mongolian" => "mon",
            "Nepali" => "nep",
            "Norwegian" => "nor",
            "Oriya" => "ori",
            "Ossetian" => "oss",
            "Persian" => "per",
            "Polish" => "pol",
            "Portuguese" => "por",
            "Punjabi" => "pan",
            "Pushto" => "pus",
            "Rajasthani" => "raj",
            "Romanian" => "rum",
            "Russian" => "rus",
            "Samoan" => "smo",
            "Sanskrit" => "san",
            "Sardinian" => "srd",
            "Serbian" => "scc",
            "Sindhi" => "snd",
            "Sinhalese" => "sin",
            "Slovak" => "slo",
            "Slovenian" => "slv",
            "Somali" => "som",
            "Spanish" => "spa",
            "Swahili" => "swa",
            "Swedish" => "swe",
            "Syriac" => "syr",
            "Tajik" => "tgk",
            "Tamil" => "tam",
            "Telugu" => "tel",
            "Thai" => "tha",
            "Tibetan" => "tib",
            "Turkish" => "tur",
            "Ukrainian" => "ukr",
            "Urdu" => "urd",
            "Uzbek" => "uzb",
            "Vietnamese" => "vie",
            "Welsh" => "wel",
            "Yiddish" => "yid"
        ];

        return $languageArray;
    }
}

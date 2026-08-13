<?php

use WHMCS\Database\Capsule;
use WHMCS\Module\Registrar\ConnectReseller\Sensitive;
global $whmcs;

define('CONNECTRESELLER_BASE_URL','https://api.connectreseller.com/ConnectReseller/ESHOP');


if(!defined("WHMCS")) {
    die("This file can not be accessed directly!");
}

require_once __DIR__ . '/lib/Sensitive.php';

/**
 * Create KYC custom field and pending-domain table on first use, not at include time.
 */
function connectreseller_ensureKycSchema()
{
    static $ensured = false;
    if ($ensured) {
        return;
    }
    $ensured = true;

    if (Capsule::table('tblcustomfields')->where('fieldname', 'like', 'registrantContactId|%')->where('type', 'client')->count() == 0) {
        Capsule::table('tblcustomfields')->insert([
            'type' => 'client',
            'relid' => 0,
            'fieldname' => 'registrantContactId|Registrant Contact Id',
            'fieldtype' => 'text',
            'description' => '',
            'fieldoptions' => '',
            'regexpr' => '',
            'adminonly' => '',
            'required' => '',
            'showorder' => '',
            'showinvoice' => '',
            'sortorder' => 0,
        ]);
    }

    if (!Capsule::schema()->hasTable('mod_kycpending_domains')) {
        Capsule::schema()->create('mod_kycpending_domains', function ($table) {
            $table->increments('id');
            $table->text('domainid');
            $table->string('domainname');
            $table->string('sld');
            $table->string('tld');
            $table->string('client_id');
            $table->timestamps();
        });
    }
}

/* 
 Send KYC Email — admin session + CSRF required
*/ 
if (isset($whmcs) && is_object($whmcs) && $whmcs->get_req_var('formAction') === 'sendKYCVerificationEmail') {
    header('Content-Type: application/json');
    $adminId = isset($_SESSION['adminid']) ? (int) $_SESSION['adminid'] : 0;
    if ($adminId < 1) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
        exit;
    }
    if (!function_exists('check_token')) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
        exit;
    }
    check_token('WHMCS.admin.default');
    connectreseller_ensureKycSchema();
    try {
        $userid = $whmcs->get_req_var('userId');
        $sendEmail = sendKYCverifyEmail($userid);

        if($sendEmail['status'] === 'emailSend') {
            echo json_encode(['status'  => 'success', 'message' => 'Email has been sent successfully.']);
        } elseif($sendEmail['status'] == 'statusUpdated') {
            echo json_encode(['status'  => 'updated', 'message' => 'KYC Email Verification has been done by the client.']);
        } else {
            echo json_encode(['status'  => 'error', 'message' => 'Failed to send email.']);
        }

    } catch(Exception $e) {
        logActivity("Unable to send KYC Verification Email. Error: ".$e->getMessage());
        echo json_encode(['status'  => 'error', 'message' => 'Failed to send email.']);
    }
    exit;
}

/* 
 Add Reseller client 
 While new WHMCS client added
*/ 
add_hook("ClientAdd", 1, function($vars) 
{
    try {
        connectreseller_ensureKycSchema();

        if(isset($vars['userid'])) {
            $client_exist = viewReseller($vars['email'], $vars['userid']);
            if($client_exist['status'] == "notexist_success") {
                addReseller($vars, 'newclient');
            }
        }

    } catch(Exception $e) {
        logActivity("Error to ClientAdd hook. Error: ".$e->getMessage());
    }
});


// add_hook('ShoppingCartValidateCheckout', 1, function($vars) {
//     try {

//         $domains = $_SESSION['cart']['domains'];
//         $in_domains = [];

//         foreach ($domains as $key => $domain) {
//             $in_domains[$key] = $domain['domain'];
//         }

//         $hasInTLD = !empty(array_filter($in_domains, fn($d) => stripos($d, '.in') !== false));

//         if(isset($_SESSION['uid']) && !empty($_SESSION['uid'])) {

//             $user = Capsule::table('tblclients')->where("id", $_SESSION['uid'])->first();

//             if($user->country == "IN" && $hasInTLD) {
//                 $not_exist = viewReseller($user->email, $user->id);
    
//                 if($not_exist['status'] == "kyc_success") {
//                     return [
//                         $not_exist['message']
//                     ];
//                 }
    
//                 if($not_exist['status'] == "notexist_success" && $user->country == "IN" && $hasInTLD) {
//                     $addSend = addReseller((array) $user);
//                     if($addSend['status'] == "kyc_success") {
//                         return [
//                             $addSend['message']
//                         ];
//                     }
//                 }
//             }
//         }

//     } catch(Exception $e) {
//         logActivity("Error to ClientAdd hook. Error: ".$e->getMessage());
//     }
// });
/* 
 Unable to accept the order untill the KYC is verified
*/ 
add_hook('PreRegistrarRegisterDomain', 1, function($vars) {
    try {
        connectreseller_ensureKycSchema();

        if(isset($vars['params'])) {
            $user = Capsule::table('tblclients')->where('id', (int) $vars['params']['client_id'])->first();

            $hasInTLD = false;
            if(substr($vars['params']['domainname'], -2) === 'in') {
                $hasInTLD = true;
            }

            $domanExists = Capsule::table("mod_kycpending_domains")->where('domainid', $vars['params']['domainid'])->exists();


            if($user->country == "IN" && $hasInTLD) {

                $exists = viewReseller($user->email, $user->id);

                if($exists['status'] == "notexist_success" && $user->country == "IN" && $hasInTLD) {
                    $addSend = addReseller((array) $user);

                    if(!$domanExists) {
                        $insert = Capsule::table('mod_kycpending_domains')->insert([
                            'domainid' => $vars['params']['domainid'],
                            'domainname' => $vars['params']['domainname'],
                            'sld' => $vars['params']['sld'],
                            'tld' => $vars['params']['tld'],
                            'client_id' => $user->id
                        ]);
    
                        if (!$insert) {
                            logActivity("Unable to insert domain data: {$vars['params']['domainname']}");
                        }
                    }
                    
                    if($addSend['status'] == "kyc_success") {
                        return [
                            'abortWithError' => "Client's KYC verification is pending. A verification email has been sent."
                        ];
                    }

                } elseif($exists['status'] === 'kyc_success') {

                    if(!$domanExists) {
                        $insert = Capsule::table('mod_kycpending_domains')->insert([
                            'domainid' => $vars['params']['domainid'],
                            'domainname' => $vars['params']['domainname'],
                            'sld' => $vars['params']['sld'],
                            'tld' => $vars['params']['tld'],
                            'client_id' => $user->id
                        ]);
    
                        if (!$insert) {
                            logActivity("Unable to insert domain data: {$vars['params']['domainname']}");
                        }
                    }

                    return [
                        'abortWithError' => "Client's KYC verification is pending. A verification email has been sent."
                    ];
                }
            }
        }

    } catch(Exception $e) {
        logActivity("Error in PreRegistrarRegisterDomain hook. Error: ".$e->getMessage());
    }
});


/*
 Display client KYC verification status.
 On client summary page at admin side
*/
add_hook('AdminAreaClientSummaryPage', 1, function($vars) {
    try {
        connectreseller_ensureKycSchema();
        if (isset($vars['userid']) && !empty($vars['userid'])) {
            $userid = (int)$vars['userid'];
            $registrantStatus = getRegistrantStatus($userid);

            // Show green badge if Verified
            if (isset($registrantStatus['status']) && $registrantStatus['status'] === "Verified") {
                return '<div class="alert alert-success" style="display:inline-block; padding:6px 12px; border-radius:4px;">KYC Verification Status: <strong>Verified</strong></div>';
            }

            // Show warning with button if not verified
            $status = !empty($registrantStatus['status']) ? $registrantStatus['status'] : "Not Verified";
            $statusEscaped = Sensitive::escapeHtml($status);
            $useridEscaped = (int) $userid;

            return <<<HTML
                <div class="alert alert-warning" id="kyc-status-wrapper" style="display:inline-block; padding:6px 12px; border-radius:4px;">
                    KYC Verification Status: 
                    <strong>{$statusEscaped}</strong> 
                    <a href="#" id="sendKYCVerificationEmail" data-userid="{$useridEscaped}" class="btn btn-primary btn-sm" style="margin-left:10px;">Send Email</a>
                </div>

                <script type="text/javascript">
                jQuery(document).ready(function($) {
                    $(document).on('click', '#sendKYCVerificationEmail', function(e) {
                        e.preventDefault();

                        var \$btn = $(this);
                        var userId = \$btn.data('userid');
                        var \$parent = $('#kyc-status-wrapper');
                        var token = (typeof csrfToken !== 'undefined') ? csrfToken : '';

                        \$btn.html('<span class="spinner-border spinner-border-sm"></span> Sending...')
                             .prop('disabled', true);

                        $.ajax({
                            type: 'POST',
                            dataType: 'json',
                            data: {
                                userId: userId,
                                formAction: 'sendKYCVerificationEmail',
                                token: token
                            },
                            success: function(response) {
                                var message = (response && response.message) ? response.message : '';
                                var alertClass = (response && (response.status === 'success' || response.status === 'updated')) ? 'alert-success' : 'alert-error';
                                var \$msg = $('<div class="alert" style="display:inline-block; padding:6px 12px; border-radius:4px; margin-top:10px;"></div>');
                                \$msg.addClass(alertClass).text(message);

                                \$parent.append(\$msg);

                                setTimeout(function() {
                                    \$msg.fadeOut(500, function() { $(this).remove(); });
                                }, 3000);
                            },
                            error: function() {
                                var \$msg = $('<div class="alert alert-danger" style="display:inline-block; padding:6px 12px; border-radius:4px; margin-top:10px;">Failed to send email</div>');
                                \$parent.append(\$msg);

                                setTimeout(function() {
                                    \$msg.fadeOut(500, function() { $(this).remove(); });
                                }, 3000);
                            },
                            complete: function() {
                                \$btn.html('Send Email').prop('disabled', false);
                            }
                        });
                    });
                });
                </script>
            HTML;
        }

    } catch(Exception $e) {
        logActivity("Error in AdminAreaClientSummaryPage hook (KYC). Exception: " . $e->getMessage());
    }
});

// show verification msg
add_hook('ShoppingCartCheckoutCompletePage', 1, function($vars) {
    if(isset($vars['orderid']) && !empty($vars['orderid'])) {
        $domains = Capsule::table("tbldomains")->where("orderid", $vars['orderid'])->get();

        $domains_data = [];

        if($domains) {
            foreach($domains as $domain) {
                $domains_data[] = $domain->domain;
            }
        }

        $hasInTLD = !empty(array_filter($domains_data, fn($d) => stripos($d, '.in') !== false));

        if(isset($vars['clientdetails']['userid']) && !empty($vars['clientdetails']['userid'])) {
            $user = Capsule::table('tblclients')->where("id", $_SESSION['uid'])->first();

            if($user->country == "IN" && $hasInTLD) {
                $not_exist = viewReseller($user->email, $user->id);
    
                if($not_exist['status'] == "kyc_success") {
                    return '<div class="alert alert-danger"> ' . Sensitive::escapeHtml($not_exist['message']) . '</div>';
                }
    
                if($not_exist['status'] == "notexist_success" && $user->country == "IN" && $hasInTLD) {
                    $addSend = addReseller((array) $user);
                    if($addSend['status'] == "kyc_success") {
                        return '<div class="alert alert-danger"> ' . Sensitive::escapeHtml($not_exist['message']) . '</div>';
                    }
                }
            }
        }
    }
});



/** ******************************************************* FUNCTIONS ******************************************************* */

/**
 * View Reseller client
 ** Send Email verification Email 
*/
function viewReseller($email, $userid = null) {
    try {

        $data = [
            "UserName" => $email
        ];

        // Curl Call for ViewClient
        $response = callCurl("GET",  $data, "ViewClient");

        if($response['status_code'] == 200) {
            $response_data = json_decode($response['response'], true);
            $status = $response_data['responseMsg']['statusCode'];
            if($status == 200) {

                $userData = Capsule::table('tblclients')->where("id", $userid)->first();
                if($userid && $userData->country == 'IN') {
                    // Send KYC verification Email
                    $sendKYCverifyEmail = sendKYCverifyEmail($userid);
                    if($sendKYCverifyEmail['status'] == "emailSend") {
                        return [
                            "status" => "kyc_success",
                            "message" => "Your KYC status is unverified. We have sent you a KYC verification email — please check your inbox and follow the instructions to complete the KYC verification."
                        ];
                    }
                }

                return [
                    "status" => "exist_success",
                    "message" => "User Exist"
                ];

            } else {
                return [
                    "status" => "notexist_success",
                    "message" => "User Doesn't Exist"
                ];
            }

        } 

    } catch(Exception $e) {
        logActivity("Error in Add Reseller Client. Error".$e->getMessage());
    }
}

/**
 * Add new Reseller client
 ** Send Email verification Email 
*/
function addReseller($vars, $newclient = null) {
    try {

        $phone_num = $vars['phonenumber'];
        $parts = explode('.', ltrim($phone_num, '+'));
        $country_code = isset($parts[0]) ? trim($parts[0]) : ''; // Country code
        $ph_no = isset($parts[1]) ? str_replace(' ', '', trim($parts[1])) : ''; // phone number

        $data = [
            'FirstName' => $vars['firstname'],
            'LastName' => $vars['lastname'],
            'UserName' => $vars['email'],
            'Password' => Sensitive::randomPassword(),
            'CompanyName' => $vars['companyname'],
            'Address1' => $vars['address1'],
            'City' => $vars['city'],
            'StateName' => $vars['state'],
            'CountryName' => $vars['country'],
            'Zip' => $vars['postcode'],
            'PhoneNo_cc' => $country_code,
            'PhoneNo' => $ph_no,
        ];

        // Curl Call for AddClient
        $response = callCurl("GET", $data, "AddClient");
        if($response['status_code'] == 200) {
            $response_data = json_decode($response['response'], true);
            $client_id = $response_data['responseData']['clientId'];

            $registrant_sendData = [
                'Id' => $client_id
            ];

            // Curl Call for DefaultRegistrantContact
            $registrantContactId = callCurl("GET", $registrant_sendData, "DefaultRegistrantContact");
            if($registrantContactId['status_code'] == 200) {
                $userID = $vars['id'] ?? $vars['userid'] ?? null;
                $registrant = json_decode($registrantContactId['response'], true);
                $registrant_id = $registrant['responseData']['registrantContactId'];

                $field_id = Capsule::table('tblcustomfields')->where('fieldname', 'like', 'registrantContactId|%')->where('type', 'client')->value('id');

                // Insert Registrant ID
                Capsule::table('tblcustomfieldsvalues')->updateOrInsert(
                    [
                        'fieldid' => $field_id,
                        'relid'   => $userID
                    ],
                    [
                        'value'   => $registrant_id
                    ]
                );
            }
            // Send KYC Email
            if($vars['country'] == "IN" && !$newclient) {
                $sendKYCverifyEmail = sendKYCverifyEmail($userID);
                if($sendKYCverifyEmail['status'] == "emailSend") {
                    return [
                        "status" => "kyc_success",
                        "message" => "Your KYC status is unverified. We have sent you a KYC verification email — please check your inbox and follow the instructions to complete the KYC verification."
                    ];
                }
            }

        }

    } catch(Exception $e) {
        logActivity("Error in Add Reseller Client. Error".$e->getMessage());
    }
}

/**
 * Send Email verification Email 
 */
function sendKYCverifyEmail($uid) {
    try {

        // retrive the registrant KYC verification status
        $viewRegistrantStatus = getRegistrantStatus($uid);

        if($viewRegistrantStatus['status'] == "Verified") {
            return ["status" => "statusUpdated", "message" => "The KYC verification had beed verified by the user."];
        } else {
            $kyc_sendData = [
                'registrantContactId' => $viewRegistrantStatus['registrant_id']
            ];
            // Curl Call for sendKYCMail
            $sendEmail = callCurl("GET", $kyc_sendData, "sendKYCMail");
            if(!empty($sendEmail['status_code']) && $sendEmail['status_code'] === 200) {
                return ["status" => "emailSend", "message" => "The KYC verification email has beed sent to client: #{$uid}."];
            }
        }

    } catch(Exception $e) {
        logActivity("Unable to send the KYC Email for clientId #{$uid}". $e->getMessage());
    }
}

/**
 * Get Reseller client KYC Email verification status
 */
function getRegistrantStatus($uid) {
    try {

        $field_id = Capsule::table('tblcustomfields')->where('fieldname', 'like', 'registrantContactId|%')->where('type', 'client')->value('id');
        $registrantID =  Capsule::table('tblcustomfieldsvalues')->where("fieldid", $field_id)->where("relid", $uid)->value("value");

        if(!$registrantID) {
            logActivity("No registrantID found for clientId {$uid}");
            return [
                'error' => 'No registrantID found for this client'
            ];
        }

        $status_data = [
            'RegistrantContactId' => $registrantID
        ];
        // Curl Call for ViewRegistrant
        $viewRegistrantStatus = callCurl("GET", $status_data, "ViewRegistrant");

        if($viewRegistrantStatus['status_code'] == 200) {
            $registrantData = json_decode($viewRegistrantStatus['response'], true);
            $rawStatus = $registrantData['responseData']['kycStatus'] ?? null;
            if ($rawStatus === true) {
                $registrant_status = "Verified"; 
            } elseif (empty($rawStatus) || $rawStatus === false) { 
                $registrant_status = "Not Verified"; 
            } else {
                $registrant_status = (string) $rawStatus; 
            }

            return [
                "status" => $registrant_status,
                "registrant_id" => $registrantID
            ];
        }


    } catch(Exception $e) {
        logActivity("Error to get client registrant KYC status. Error: ".$e->getMessage());
    }
}



/**
 * ******************************************************** CURL Call ********************************************************
 */
function callCurl($method, $data, $action)
{
    try {

        $apiKey = decrypt(Capsule::table('tblregistrars')->where('registrar', 'connectreseller')->where('setting', 'APIKey')->value('value'));
        $header = [];

        $query = array('APIKey' => $apiKey);
        if (is_array($data)) {
            $query = array_merge($query, $data);
        }

        $url = rtrim(CONNECTRESELLER_BASE_URL, '/') . '/' . $action . '?' . http_build_query($query);

        // Initialize cURL
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

        // Execute request
        $responseBody = curl_exec($ch);
        $httpCode     = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch)) {
            throw new \Exception('Curl error: ' . curl_error($ch));
        }

        curl_close($ch);

        logModuleCall('domainsignup', $action, Sensitive::redact($url), Sensitive::redact($responseBody));

        return [
            'status_code' => $httpCode,
            'response'    => $responseBody
        ];

    } catch (Exception $e) {
        return [
            'status_code' => 500,
            'error'       => $e->getMessage()
        ];
    }
}


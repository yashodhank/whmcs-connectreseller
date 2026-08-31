<?php

use WHMCS\Database\Capsule;
use WHMCS\Module\Registrar\ConnectReseller\Sensitive;
use WHMCS\Module\Registrar\ConnectReseller\ApiClient;
use WHMCS\Module\Registrar\ConnectReseller\DomainMapper;
use WHMCS\Module\Registrar\ConnectReseller\KycCron;
global $whmcs;

define('CONNECTRESELLER_BASE_URL','https://api.connectreseller.com/ConnectReseller/ESHOP');


if(!defined("WHMCS")) {
    die("This file can not be accessed directly!");
}

require_once __DIR__ . '/lib/Sensitive.php';
require_once __DIR__ . '/lib/ApiClient.php';
require_once __DIR__ . '/lib/DomainMapper.php';
require_once __DIR__ . '/lib/CronStateStore.php';
require_once __DIR__ . '/lib/CapsuleCronStore.php';
require_once __DIR__ . '/lib/CronGuard.php';
require_once __DIR__ . '/lib/KycCron.php';

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

        // sendKYCverifyEmail always returns ['status','message']; surface the real
        // reason so the admin sees "not provisioned"/"API rejected" not a blanket error.
        $resultStatus = is_array($sendEmail) && isset($sendEmail['status']) ? $sendEmail['status'] : 'error';
        $resultMessage = is_array($sendEmail) && !empty($sendEmail['message']) ? $sendEmail['message'] : '';

        if ($resultStatus === 'emailSend') {
            echo json_encode(['status' => 'success', 'message' => $resultMessage !== '' ? $resultMessage : 'Email has been sent successfully.']);
        } elseif ($resultStatus === 'statusUpdated') {
            echo json_encode(['status' => 'updated', 'message' => $resultMessage !== '' ? $resultMessage : 'KYC Email Verification has been done by the client.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => $resultMessage !== '' ? $resultMessage : 'Failed to send email.']);
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
add_hook('DailyCronJob', 1, function () {
    try {
        KycCron::run();
    } catch (\Exception $e) {
        logActivity('ConnectReseller KYC cron via WHMCS daily cron failed: ' . $e->getMessage());
    }
});

// Continue in-progress KYC chunks on every system cron (not only once/day).
add_hook('AfterCronJob', 1, function () {
    try {
        KycCron::run(null, true);
    } catch (\Exception $e) {
        logActivity('ConnectReseller KYC continue via WHMCS cron failed: ' . $e->getMessage());
    }
});

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

            $hasInTLD = DomainMapper::isInDomain($vars['params']['domainname']);

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

        $hasInTLD = DomainMapper::listHasInDomain($domains_data);

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
 * Send the KYC verification email for a client.
 *
 * Always returns a structured array: ['status' => <emailSend|statusUpdated|error>,
 * 'message' => <human-readable reason>]. Never returns null, so callers can rely
 * on the shape and surface a precise reason instead of a blanket failure.
 */
function sendKYCverifyEmail($uid) {
    try {
        $uid = (int) $uid;

        // Retrieve the registrant KYC verification status (normalized shape).
        $viewRegistrantStatus = getRegistrantStatus($uid);

        // Could not even resolve the registrant contact — do not attempt a send
        // with an empty registrantContactId (that produces a broken CR request).
        if (empty($viewRegistrantStatus['registrant_id'])) {
            $reason = !empty($viewRegistrantStatus['error'])
                ? $viewRegistrantStatus['error']
                : 'Registrant contact is not provisioned for this client in ConnectReseller.';

            return ["status" => "error", "message" => $reason];
        }

        if (isset($viewRegistrantStatus['status']) && $viewRegistrantStatus['status'] === "Verified") {
            return ["status" => "statusUpdated", "message" => "The KYC verification has already been completed by the user."];
        }

        $kyc_sendData = [
            'registrantContactId' => $viewRegistrantStatus['registrant_id']
        ];
        // Curl Call for sendKYCMail
        $sendEmail = callCurl("GET", $kyc_sendData, "sendKYCMail");

        if (empty($sendEmail['status_code']) || (int) $sendEmail['status_code'] !== 200) {
            $reason = !empty($sendEmail['error'])
                ? 'ConnectReseller did not accept the KYC email request: ' . $sendEmail['error']
                : 'ConnectReseller did not accept the KYC email request.';

            return ["status" => "error", "message" => $reason];
        }

        // HTTP 200 can still carry a body-level (logical) error. Check it.
        $sendData = !empty($sendEmail['response']) ? json_decode($sendEmail['response'], true) : null;
        $apiStatus = isset($sendData['responseMsg']['statusCode']) ? (int) $sendData['responseMsg']['statusCode'] : 200;
        if ($apiStatus !== 200) {
            $apiMsg = isset($sendData['responseMsg']['message']) ? $sendData['responseMsg']['message'] : 'unknown error';

            return ["status" => "error", "message" => 'ConnectReseller rejected the KYC email request: ' . $apiMsg];
        }

        return ["status" => "emailSend", "message" => "The KYC verification email has been sent to client #{$uid}."];

    } catch(Exception $e) {
        logActivity("Unable to send the KYC Email for clientId #{$uid}: ". $e->getMessage());

        return ["status" => "error", "message" => 'Failed to send email: ' . $e->getMessage()];
    }
}

/**
 * Local registrantContactId for a WHMCS client (custom field value), or null.
 */
function connectreseller_registrantContactId($uid) {
    $field_id = Capsule::table('tblcustomfields')
        ->where('fieldname', 'like', 'registrantContactId|%')
        ->where('type', 'client')
        ->value('id');
    if (!$field_id) {
        return null;
    }

    return Capsule::table('tblcustomfieldsvalues')
        ->where("fieldid", $field_id)
        ->where("relid", (int) $uid)
        ->value("value");
}

/**
 * Resolve a client's ConnectReseller registrantContactId when it was never
 * stored locally (client pre-existed in CR or was imported), and persist it.
 *
 * Uses the same lookups addReseller() relies on: ViewClient (by email) to find
 * the CR customer, then DefaultRegistrantContact to read its registrant id.
 * Best-effort and defensive — returns null (not an exception) on any mismatch.
 */
function connectreseller_backfillRegistrantId($uid) {
    try {
        $uid = (int) $uid;
        $client = Capsule::table('tblclients')->where('id', $uid)->first();
        if ($client === null || empty($client->email)) {
            return null;
        }

        // 1) Find the ConnectReseller customer by email.
        $view = callCurl("GET", ['UserName' => $client->email], "ViewClient");
        if (empty($view['status_code']) || (int) $view['status_code'] !== 200 || empty($view['response'])) {
            return null;
        }
        $viewData = json_decode($view['response'], true);
        if (!isset($viewData['responseMsg']['statusCode']) || (int) $viewData['responseMsg']['statusCode'] !== 200) {
            return null; // client does not exist in ConnectReseller
        }
        $responseData = isset($viewData['responseData']) && is_array($viewData['responseData']) ? $viewData['responseData'] : [];
        $crClientId = null;
        foreach (['clientId', 'clientID', 'ClientId', 'id', 'Id'] as $key) {
            if (!empty($responseData[$key])) {
                $crClientId = $responseData[$key];
                break;
            }
        }
        if (!$crClientId) {
            return null;
        }

        // 2) Read the default registrant contact id for that customer.
        $default = callCurl("GET", ['Id' => $crClientId], "DefaultRegistrantContact");
        if (empty($default['status_code']) || (int) $default['status_code'] !== 200 || empty($default['response'])) {
            return null;
        }
        $defaultData = json_decode($default['response'], true);
        $registrantId = $defaultData['responseData']['registrantContactId'] ?? null;
        if (!$registrantId) {
            return null;
        }

        // 3) Persist locally so future lookups are cheap and consistent.
        connectreseller_ensureKycSchema();
        $field_id = Capsule::table('tblcustomfields')
            ->where('fieldname', 'like', 'registrantContactId|%')
            ->where('type', 'client')
            ->value('id');
        if ($field_id) {
            Capsule::table('tblcustomfieldsvalues')->updateOrInsert(
                ['fieldid' => $field_id, 'relid' => $uid],
                ['value' => $registrantId]
            );
        }

        return $registrantId;
    } catch (Exception $e) {
        logActivity("ConnectReseller KYC backfill failed for clientId #{$uid}: " . $e->getMessage());

        return null;
    }
}

/**
 * Get a Reseller client's KYC verification status.
 *
 * Always returns a normalized array with 'status', 'registrant_id' and (on
 * failure) 'error' keys, so callers never dereference undefined offsets or null.
 */
function getRegistrantStatus($uid) {
    try {
        $uid = (int) $uid;
        $registrantID = connectreseller_registrantContactId($uid);

        // Self-heal: registrantContactId is only written when addReseller() runs.
        // Clients that already existed in ConnectReseller never got it, so resolve
        // it from CR now (this is what makes the manual "Send Email" button work
        // for pre-existing/imported clients such as the ones on the summary page).
        if (!$registrantID) {
            $registrantID = connectreseller_backfillRegistrantId($uid);
        }

        if (!$registrantID) {
            logActivity("ConnectReseller KYC: no registrantContactId for clientId {$uid} (not provisioned in ConnectReseller)");

            return [
                'status' => null,
                'registrant_id' => null,
                'error' => 'Registrant contact is not provisioned for this client in ConnectReseller.',
            ];
        }

        $status_data = [
            'RegistrantContactId' => $registrantID
        ];
        // Curl Call for ViewRegistrant
        $viewRegistrantStatus = callCurl("GET", $status_data, "ViewRegistrant");

        if (empty($viewRegistrantStatus['status_code']) || (int) $viewRegistrantStatus['status_code'] !== 200) {
            $reason = !empty($viewRegistrantStatus['error'])
                ? 'Unable to reach ConnectReseller to read KYC status: ' . $viewRegistrantStatus['error']
                : 'Unable to reach ConnectReseller to read KYC status.';

            return [
                'status' => null,
                'registrant_id' => $registrantID,
                'error' => $reason,
            ];
        }

        $registrantData = json_decode($viewRegistrantStatus['response'], true);
        $apiStatus = isset($registrantData['responseMsg']['statusCode']) ? (int) $registrantData['responseMsg']['statusCode'] : 200;
        if ($apiStatus !== 200) {
            return [
                'status' => null,
                'registrant_id' => $registrantID,
                'error' => 'ConnectReseller rejected the KYC status lookup (code ' . $apiStatus . ').',
            ];
        }

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

    } catch(Exception $e) {
        logActivity("Error to get client registrant KYC status. Error: ".$e->getMessage());

        return [
            'status' => null,
            'registrant_id' => null,
            'error' => $e->getMessage(),
        ];
    }
}



/**
 * ******************************************************** CURL Call ********************************************************
 */
function callCurl($method, $data, $action)
{
    try {
        $apiKey = decrypt(Capsule::table('tblregistrars')->where('registrar', 'connectreseller')->where('setting', 'APIKey')->value('value'));
        $query = array('APIKey' => $apiKey);
        if (is_array($data)) {
            $query = array_merge($query, $data);
        }

        $client = new ApiClient();
        $result = $client->get($action, $query, $action);
        $httpCode = $client->getLastHttpCode();
        if ($httpCode < 100) {
            $httpCode = 200;
        }

        return array(
            'status_code' => $httpCode,
            'response' => json_encode($result['result']),
        );
    } catch (\Exception $e) {
        return array(
            'status_code' => 500,
            'response' => '',
            'error' => $e->getMessage(),
        );
    }
}


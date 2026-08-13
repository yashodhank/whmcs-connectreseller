<?php

$whmcspath = "";
 
 
if (file_exists(dirname(__FILE__) . "/config.php")) {
    require_once dirname(__FILE__) . "/config.php";
}
 
 
if (!empty($whmcspath)) {
    require_once $whmcspath . "/init.php";
    if (file_exists($whmcspath . '/modules/registrars/connectreseller/hooks.php')) {
        require_once($whmcspath . '/modules/registrars/connectreseller/hooks.php');
    } else {
        logActivity('KYC Cron error, File (/modules/registrars/connectreseller/hooks.php) not found');
    }
} else {
    require(__DIR__ . "/../init.php");
     if (file_exists(__DIR__ . '/../modules/registrars/connectreseller/hooks.php')) {
        require_once(__DIR__ . '/../modules/registrars/connectreseller/hooks.php');
    } else {
        logActivity('KYC Cron error, File (/modules/registrars/connectreseller/hooks.php) not found');
    }
}
use WHMCS\Database\Capsule;

// Send KYC verification Email
try {
    logActivity("KYC Verification Email Cron started on " . date('Y-m-d H:i:s'));

    $clients = Capsule::table("tblclients")->get();

    $field_id = Capsule::table('tblcustomfields')->where('fieldname', 'like', 'registrantContactId|%')->where('type', 'client')->value('id');

    foreach ($clients as $client) {
        try {
            $registrantID = Capsule::table('tblcustomfieldsvalues')->where("fieldid", $field_id)->where("relid", $client->id)->value("value");

            if ($registrantID && $client->country == 'IN') {
                $result = sendKYCverifyEmail($client->id);
                if (!$result) {
                    logActivity("KYC email not sent for clientId {$client->id} (conditions not met or failed send)");
                }
            }
        } catch (\Exception $e) {
            logActivity("KYC send failed for clientId {$client->id}. Error: " . $e->getMessage());
            continue;
        }
    }

    logActivity("KYC Verification Email Cron completed on " . date('Y-m-d H:i:s'));

} catch (\Exception $e) {
    logActivity("Exception in KYC Verification Email Cron: " . $e->getMessage());
}


// Register Domain when status is verified
try {
    logActivity("Register domain on KYC verified, Cron started on " . date('Y-m-d H:i:s'));

    $domainOrders = Capsule::table("mod_kycpending_domains")->get();

    foreach ($domainOrders as $order) {
        try {
            $userid = $order->client_id;
            $kycStatus = getRegistrantStatus($userid);

            if (!empty($kycStatus['status']) && $kycStatus['status'] === 'Verified') {
                $command = 'DomainRegister';
                $postData = ['domainid' => $order->domainid];
                $results = localAPI($command, $postData);

                if (!isset($results['error'])) {
                    Capsule::table("mod_kycpending_domains")->where("id", $order->id)->delete();
                    logActivity("Domain successfully registered after KYC: {$order->domainname}");
                } else {
                    logActivity("Domain registration API error for {$order->domainname}: " . $results['error']);
                }
            }
        } catch (\Exception $e) {
            logActivity("Domain Registration failed, Domain: {$order->domainname}, ClientId: {$order->client_id}. Error: " . $e->getMessage());
            continue;
        }
    }

    logActivity("Register domain on KYC verified, Cron completed on " . date('Y-m-d H:i:s'));
} catch (\Exception $e) {
    logActivity("Exception in Register domain on KYC verified, Cron: " . $e->getMessage());
}



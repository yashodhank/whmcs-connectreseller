<?php

namespace WHMCS\Module\Registrar\ConnectReseller;

use WHMCS\Database\Capsule;

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

class KycCron
{
    /**
     * Send .in KYC mail and register pending domains after verification.
     * Gated to once per calendar day so DailyCronJob and the standalone
     * cron script do not double-send.
     *
     * @return string
     */
    public static function run()
    {
        if (!\function_exists('sendKYCverifyEmail') || !\function_exists('getRegistrantStatus')) {
            return 'skipped';
        }

        if (\function_exists('connectreseller_ensureKycSchema')) {
            \connectreseller_ensureKycSchema();
        }

        $today = date('Y-m-d');
        $last = Capsule::table('tblconfiguration')
            ->where('setting', 'ConnectResellerKycCronLastRun')
            ->value('value');
        if ($last === $today) {
            return 'skipped';
        }

        \logActivity('KYC Verification Email Cron started on ' . date('Y-m-d H:i:s'));

        $clients = Capsule::table('tblclients')->get();
        $field_id = Capsule::table('tblcustomfields')
            ->where('fieldname', 'like', 'registrantContactId|%')
            ->where('type', 'client')
            ->value('id');

        foreach ($clients as $client) {
            try {
                $registrantID = Capsule::table('tblcustomfieldsvalues')
                    ->where('fieldid', $field_id)
                    ->where('relid', $client->id)
                    ->value('value');

                if ($registrantID && $client->country == 'IN') {
                    $result = \sendKYCverifyEmail($client->id);
                    if (!$result) {
                        \logActivity(
                            "KYC email not sent for clientId {$client->id} (conditions not met or failed send)"
                        );
                    }
                }
            } catch (\Exception $e) {
                \logActivity("KYC send failed for clientId {$client->id}. Error: " . $e->getMessage());
                continue;
            }
        }

        \logActivity('KYC Verification Email Cron completed on ' . date('Y-m-d H:i:s'));
        \logActivity('Register domain on KYC verified, Cron started on ' . date('Y-m-d H:i:s'));

        $domainOrders = Capsule::table('mod_kycpending_domains')->get();
        foreach ($domainOrders as $order) {
            try {
                $userid = $order->client_id;
                $kycStatus = \getRegistrantStatus($userid);

                if (!empty($kycStatus['status']) && $kycStatus['status'] === 'Verified') {
                    $results = \localAPI('DomainRegister', array('domainid' => $order->domainid));
                    if (!isset($results['error'])) {
                        Capsule::table('mod_kycpending_domains')->where('id', $order->id)->delete();
                        \logActivity('Domain successfully registered after KYC: ' . $order->domainname);
                    } else {
                        \logActivity(
                            'Domain registration API error for ' . $order->domainname . ': ' . $results['error']
                        );
                    }
                }
            } catch (\Exception $e) {
                \logActivity(
                    'Domain Registration failed, Domain: ' . $order->domainname
                    . ', ClientId: ' . $order->client_id . '. Error: ' . $e->getMessage()
                );
                continue;
            }
        }

        \logActivity('Register domain on KYC verified, Cron completed on ' . date('Y-m-d H:i:s'));

        if ($last === null) {
            Capsule::table('tblconfiguration')->insert(array(
                'setting' => 'ConnectResellerKycCronLastRun',
                'value' => $today,
            ));
        } else {
            Capsule::table('tblconfiguration')
                ->where('setting', 'ConnectResellerKycCronLastRun')
                ->update(array('value' => $today));
        }

        return 'completed';
    }
}

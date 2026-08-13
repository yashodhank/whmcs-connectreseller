<?php

declare(strict_types=1);

namespace ConnectReseller\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class ImportSmokeTest extends TestCase
{
    public function testRegistrarModuleFileExists(): void
    {
        $path = dirname(__DIR__, 2) . '/modules/registrars/connectreseller/connectreseller.php';
        self::assertFileExists($path);
    }

    public function testKycCronWasRenamed(): void
    {
        $legacy = dirname(__DIR__, 2) . '/crons/kycVerfication.php';
        $current = dirname(__DIR__, 2) . '/crons/kycVerification.php';
        self::assertFileDoesNotExist($legacy);
        self::assertFileExists($current);
    }

    public function testRegistrarActionsWereSplitIntoServices(): void
    {
        $lib = dirname(__DIR__, 2) . '/modules/registrars/connectreseller/lib';
        $files = array(
            'Dns.php',
            'Contacts.php',
            'Transfers.php',
            'Pricing.php',
            'Nameservers.php',
            'DomainLifecycle.php',
        );
        foreach ($files as $file) {
            self::assertFileExists($lib . '/' . $file);
        }
        $source = (string) file_get_contents(
            dirname(__DIR__, 2) . '/modules/registrars/connectreseller/connectreseller.php'
        );
        self::assertStringContainsString('return Dns::get($params);', $source);
        self::assertStringContainsString('return Contacts::get($params);', $source);
        $addonHooks = dirname(__DIR__, 2) . '/modules/addons/connect_reseller/hooks.php';
        $priceSync = dirname(__DIR__, 2) . '/modules/addons/connect_reseller/lib/PriceSyncTask.php';
        self::assertFileExists($addonHooks);
        self::assertFileExists($priceSync);
    }

    public function testCronIntelligenceUsesGuardNotFullClientScan(): void
    {
        $kyc = (string) file_get_contents(
            dirname(__DIR__, 2) . '/modules/registrars/connectreseller/lib/KycCron.php'
        );
        $price = (string) file_get_contents(
            dirname(__DIR__, 2) . '/modules/addons/connect_reseller/lib/PriceSyncTask.php'
        );
        $addon = (string) file_get_contents(
            dirname(__DIR__, 2) . '/modules/addons/connect_reseller/connect_reseller.php'
        );
        self::assertFileExists(
            dirname(__DIR__, 2) . '/modules/registrars/connectreseller/lib/CronGuard.php'
        );
        self::assertStringNotContainsString("tblclients')->get()", $kyc);
        self::assertStringContainsString('mod_kycpending_domains', $kyc);
        self::assertStringContainsString('KEY_PRICE_LAST_RUN', $price);
        self::assertStringNotContainsString("strtotime(date('H:i:s'))", $price);
        self::assertStringContainsString("'Default' => '24'", $addon);
    }
}

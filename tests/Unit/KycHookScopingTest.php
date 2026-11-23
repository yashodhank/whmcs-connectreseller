<?php

declare(strict_types=1);

namespace ConnectReseller\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Prod #111 fold: WHMCS loads every active registrar's hooks.php, so the KYC gate in
 * PreRegistrarRegisterDomain must only act on domains registered through ConnectReseller.
 */
final class KycHookScopingTest extends TestCase
{
    /**
     * @param array<string, mixed> $scenario
     * @return array<string, mixed>
     */
    private static function runHarness(array $scenario): array
    {
        $cmd = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(dirname(__DIR__) . '/Support/kyc_hook_harness.php')
            . ' ' . escapeshellarg((string) json_encode($scenario));
        $out = (string) shell_exec($cmd . ' 2>&1');
        $data = json_decode($out, true);
        self::assertIsArray($data, $out);
        return $data;
    }

    public function testDomainUsesRegistrarHelper(): void
    {
        $r = self::runHarness(array(
            'domains' => array(5 => array('registrar' => 'ConnectReseller'), 6 => array('registrar' => 'resellerclubrcm')),
            'hook' => 'none',
            'vars' => array(),
            'probe' => array(5, 6, 0, -1, null, 999),
        ));
        self::assertSame(array(true, false, false, false, false, false), $r['usesRegistrar']);
    }

    public function testOtherRegistrarDomainIsNotTouched(): void
    {
        $r = self::runHarness(array(
            'domains' => array(6 => array('registrar' => 'resellerclubrcm', 'userid' => 9)),
            'clients' => array(9 => array('country' => 'IN', 'email' => 'x@example.invalid')),
            'hook' => 'PreRegistrarRegisterDomain',
            'vars' => array('params' => array('domainid' => 6, 'domainname' => 'example.in', 'userid' => 9, 'client_id' => 9)),
        ));
        self::assertNull($r['return']);
        foreach ($r['log'] as $entry) {
            self::assertStringStartsWith('tbldomains:', $entry, 'no schema, client lookup or insert for a non-CR domain');
        }
    }

    public function testMissingDomainIdIsNotGated(): void
    {
        $r = self::runHarness(array(
            'hook' => 'PreRegistrarRegisterDomain',
            'vars' => array('params' => array('domainname' => 'example.in')),
        ));
        self::assertNull($r['return']);
        self::assertSame(array(), $r['log']);
    }

    public function testCheckoutNoticeOnlyLooksAtConnectResellerDomains(): void
    {
        $r = self::runHarness(array(
            'hook' => 'ShoppingCartCheckoutCompletePage',
            'vars' => array('orderid' => 77),
        ));
        self::assertContains('tbldomains:[["orderid","=",77],["registrar","=","connectreseller"]]', $r['log']);
    }
}

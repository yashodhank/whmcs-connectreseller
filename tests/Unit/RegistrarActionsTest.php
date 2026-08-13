<?php

declare(strict_types=1);

namespace ConnectReseller\Tests\Unit;

use PHPUnit\Framework\TestCase;
use WHMCS\Module\Registrar\ConnectReseller\ApiClient;
use WHMCS\Module\Registrar\ConnectReseller\DomainMapper;
use WHMCS\Module\Registrar\ConnectReseller\Helper;

final class RegistrarActionsTest extends TestCase
{
    public function testWebsiteNameEncodesIdn(): void
    {
        $ascii = DomainMapper::websiteName('example', 'com');
        self::assertSame('example.com', $ascii);

        $idn = DomainMapper::websiteName('münchen', 'de');
        self::assertNotSame('münchen.de', $idn);
        self::assertSame(urlencode('münchen.de'), $idn);
    }

    public function testNameserverMapping(): void
    {
        $fixture = json_decode(
            (string) file_get_contents(dirname(__DIR__) . '/fixtures/api/view-domain-success.json'),
            true
        );
        $ns = DomainMapper::nameservers($fixture['responseData']);

        self::assertSame('ns1.example.net', $ns['ns1']);
        self::assertSame('ns2.example.net', $ns['ns2']);
        self::assertSame('', $ns['ns13']);
    }

    public function testContactMappingIncludesAddress1(): void
    {
        $fixture = json_decode(
            (string) file_get_contents(dirname(__DIR__) . '/fixtures/api/view-registrant-success.json'),
            true
        );
        $contact = DomainMapper::contactFields($fixture['responseData']);

        self::assertSame('1 Main St', $contact['Address 1']);
        self::assertSame('Suite 2', $contact['Address 2']);
        self::assertSame('Floor 3', $contact['Address 3']);
        self::assertSame('jane@example.com', $contact['Email']);
    }

    public function testInDomainDetectionIsNotBroad(): void
    {
        self::assertTrue(DomainMapper::isInDomain('example.in'));
        self::assertTrue(DomainMapper::isInDomain('example.co.in'));
        self::assertFalse(DomainMapper::isInDomain('example.berlin'));
        self::assertFalse(DomainMapper::isInDomain('example.international'));
        self::assertTrue(DomainMapper::listHasInDomain(array('foo.com', 'bar.co.in')));
        self::assertFalse(DomainMapper::listHasInDomain(array('foo.com', 'bar.berlin')));
    }

    public function testNameserverUpdateQueryAlignsGetAndSave(): void
    {
        $query = DomainMapper::nameserverUpdateQuery(
            array('ns1' => 'ns1.example.net', 'ns5' => 'ns5.example.net', 'ns13' => 'ns13.example.net'),
            'key',
            'example.com',
            99
        );
        self::assertStringContainsString('nameServer1=ns1.example.net', $query);
        self::assertStringContainsString('nameServer5=ns5.example.net', $query);
        self::assertStringContainsString('nameServer13=ns13.example.net', $query);
    }

    public function testIdnLanguageFallsBackToWhmcsCode(): void
    {
        $code = DomainMapper::idnLanguageCode(
            'pol',
            'com',
            array('Polish' => 'pol'),
            array()
        );
        self::assertSame('pol', $code);
    }

    public function testGetEppCodeUsesIdnWebsiteNameWithoutNetwork(): void
    {
        $fixture = json_decode(
            (string) file_get_contents(dirname(__DIR__) . '/fixtures/api/view-domain-success.json'),
            true
        );
        $fixture['responseData']['authCode'] = 'EPP-IDN';

        $seenUrl = '';
        $client = new ApiClient(function ($method, $url, $payload) use (&$seenUrl, $fixture) {
            $seenUrl = $url;

            return json_encode($fixture);
        });
        $helper = new Helper($client);

        $idn = DomainMapper::websiteName('münchen', 'de');
        $response = $helper->get('ViewDomain?APIKey=test-key&websiteName=' . $idn, array(), 'GetEPPCode');

        self::assertSame('EPP-IDN', $response['result']['responseData']['authCode']);
        self::assertStringContainsString('websiteName=', $seenUrl);
        self::assertStringNotContainsString('münchen', $seenUrl);
    }
}

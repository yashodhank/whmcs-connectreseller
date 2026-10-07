<?php

declare(strict_types=1);

namespace ConnectReseller\Tests\Unit;

use PHPUnit\Framework\TestCase;
use WHMCS\Module\Registrar\ConnectReseller\ApiClient;
use WHMCS\Module\Registrar\ConnectReseller\Helper;
use WHMCS\Module\Registrar\ConnectReseller\Nameservers;
use WHMCS\Module\Registrar\ConnectReseller\Pricing;
use WHMCS\Module\Registrar\ConnectReseller\Sensitive;
use WHMCS\Module\Registrar\ConnectReseller\Transfers;

final class ApiUpgradesTest extends TestCase
{
    public function testTransferOrderQueryIncludesWhoisBeforeCoupon(): void
    {
        $query = Transfers::buildTransferOrderQuery(
            array(
                'Id' => 42,
                'OrderType' => 4,
                'APIKey' => 'key',
                'Websitename' => 'example.com',
                'AuthCode' => 'secret',
            ),
            true,
            'SAVE10',
            array()
        );

        self::assertStringContainsString('IsWhoisProtection=1', $query);
        self::assertStringContainsString('couponCode=SAVE10', $query);
        self::assertLessThan(
            strpos($query, 'couponCode='),
            strpos($query, 'IsWhoisProtection=')
        );
    }

    public function testTransferOrderQueryAppendsUsExtrasAfterWhois(): void
    {
        $query = Transfers::buildTransferOrderQuery(
            array(
                'Id' => 1,
                'OrderType' => 4,
                'APIKey' => 'k',
                'Websitename' => 'example.us',
                'AuthCode' => 'a',
            ),
            'false',
            '',
            array(
                'appPurpose' => 'P1',
                'nexusCategory' => 'C11',
                'isUs' => true,
            )
        );

        self::assertStringContainsString('IsWhoisProtection=false', $query);
        self::assertStringContainsString('appPurpose=P1', $query);
        self::assertStringContainsString('nexusCategory=C11', $query);
        self::assertStringContainsString('isUs=1', $query);
        self::assertLessThan(
            strpos($query, 'appPurpose='),
            strpos($query, 'IsWhoisProtection=')
        );
    }

    public function testExistingClientTransferPathNoLongerDropsWhois(): void
    {
        $source = (string) file_get_contents(
            dirname(__DIR__, 2) . '/modules/registrars/connectreseller/lib/Transfers.php'
        );
        // Regression: WHOIS must not be appended after $orderUrl is built.
        self::assertDoesNotMatchRegularExpression(
            '/\$orderUrl\s*=\s*"TransferOrder\/\?"\s*\.\s*\$query;[\s\S]{0,200}\$query\s*=\s*\$query\s*\.\s*[\'"]\&IsWhoisProtection=/',
            $source
        );
        self::assertStringContainsString('buildTransferOrderQuery', $source);
    }

    public function testExtractSuggestionListHandlesVendorShapes(): void
    {
        $nested = Pricing::extractSuggestionList(array(
            'responseData' => array(
                'registryDomainSuggestionList' => array(
                    array('domainName' => 'a.com', 'price' => 1),
                ),
            ),
        ));
        self::assertCount(1, $nested);
        self::assertSame('a.com', $nested[0]['domainName']);

        $top = Pricing::extractSuggestionList(array(
            'registryDomainSuggestionList' => array(
                array('domainName' => 'b.net'),
            ),
        ));
        self::assertSame('b.net', $top[0]['domainName']);
    }

    public function testBulkUpdateRequiresTwoNameservers(): void
    {
        $result = Nameservers::bulkUpdate('key', array('example.com'), array(
            'nameserver1' => 'ns1.example.net',
        ));
        self::assertSame('At least two nameservers are required', $result['error']);
    }

    public function testBulkUpdatePostsJsonBody(): void
    {
        $seenMethod = '';
        $seenUrl = '';
        $seenPayload = '';
        $client = new ApiClient(function ($method, $url, $payload) use (&$seenMethod, &$seenUrl, &$seenPayload) {
            $seenMethod = $method;
            $seenUrl = $url;
            $seenPayload = (string) $payload;

            return json_encode(array(
                'statusCode' => 200,
                'message' => 'ok',
            ));
        });
        $helper = new Helper($client);

        $result = Nameservers::bulkUpdate(
            'test-key',
            array('one.com', 'two.com'),
            array(
                'nameserver1' => 'ns1.example.net',
                'nameserver2' => 'ns2.example.net',
            ),
            $helper
        );

        self::assertTrue($result['success']);
        self::assertSame('POST', $seenMethod);
        self::assertStringContainsString('nameserverbulkaction', $seenUrl);
        self::assertStringContainsString('APIKey=test-key', $seenUrl);
        $body = json_decode($seenPayload, true);
        self::assertSame(array('one.com', 'two.com'), $body['DomainNames']);
        self::assertSame('ns1.example.net', $body['nameserver1']);
        self::assertSame('ns2.example.net', $body['nameserver2']);
    }

    public function testRandomAuthCodeLengthAndCharset(): void
    {
        $code = Sensitive::randomAuthCode();
        self::assertSame(16, strlen($code));
        self::assertMatchesRegularExpression('/^[A-Za-z0-9]+$/', $code);
    }

    public function testRegistrarExposesNewContractHooks(): void
    {
        $source = (string) file_get_contents(
            dirname(__DIR__, 2) . '/modules/registrars/connectreseller/connectreseller.php'
        );
        self::assertStringContainsString("define('CONNECTRESELLER_MODULE_VERSION', '3.0.6')", $source);
        self::assertStringContainsString('function connectreseller_GetDomainSuggestions', $source);
        self::assertStringContainsString('function connectreseller_SuspendDomain', $source);
        self::assertStringContainsString('function connectreseller_UnsuspendDomain', $source);
        self::assertStringContainsString('function connectreseller_canceltransfer', $source);
        self::assertStringContainsString('function connectreseller_regenerateauthcode', $source);
        self::assertStringContainsString("'Cancel Transfer' => 'canceltransfer'", $source);
        self::assertStringContainsString("'Regenerate Auth Code' => 'regenerateauthcode'", $source);
    }
}

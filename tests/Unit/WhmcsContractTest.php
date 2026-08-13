<?php

declare(strict_types=1);

namespace ConnectReseller\Tests\Unit;

use PHPUnit\Framework\TestCase;
use WHMCS\Module\Registrar\ConnectReseller\ContractSupport;

final class WhmcsContractTest extends TestCase
{
    public function testMetaDataUsesRegistrarApiVersion11(): void
    {
        $source = (string) file_get_contents(
            dirname(__DIR__, 2) . '/modules/registrars/connectreseller/connectreseller.php'
        );
        self::assertStringContainsString("'APIVersion' => '1.1'", $source);
        self::assertStringContainsString("define('CONNECTRESELLER_MODULE_VERSION', '3.0.3')", $source);
        self::assertStringContainsString('function connectreseller_TestConnection', $source);
        self::assertStringContainsString('function connectreseller_GetDomainInformation', $source);
        self::assertStringNotContainsString("'APIVersion' => '2.5.1'", $source);
    }

    public function testFundsResponseAcceptsStatusZero(): void
    {
        $fixture = json_decode(
            (string) file_get_contents(dirname(__DIR__) . '/fixtures/api/available-fund-success.json'),
            true
        );
        $result = ContractSupport::interpretFundsResponse($fixture);
        self::assertTrue($result['success']);
    }

    public function testFundsResponseSurfacesErrors(): void
    {
        $result = ContractSupport::interpretFundsResponse(array(
            'responseMsg' => array(
                'statusCode' => 400,
                'message' => 'Invalid API Key',
            ),
        ));
        self::assertSame('Invalid API Key', $result['error']);
    }

    public function testDomainInformationFromViewDomainFixture(): void
    {
        $fixture = json_decode(
            (string) file_get_contents(dirname(__DIR__) . '/fixtures/api/view-domain-success.json'),
            true
        );
        $info = ContractSupport::domainInformationFromView($fixture['responseData'], 'example', 'com');
        self::assertSame('example.com', $info['domain']);
        self::assertSame('ns1.example.net', $info['nameservers']['ns1']);
        self::assertFalse($info['locked']);
        self::assertGreaterThan(0, $info['expiry_timestamp']);
        $asArray = ContractSupport::toWhmcsDomain($info);
        self::assertSame($info, $asArray);
    }
}

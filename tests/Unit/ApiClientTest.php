<?php

declare(strict_types=1);

namespace ConnectReseller\Tests\Unit;

use PHPUnit\Framework\TestCase;
use WHMCS\Module\Registrar\ConnectReseller\ApiClient;
use WHMCS\Module\Registrar\ConnectReseller\Sensitive;

final class ApiClientTest extends TestCase
{
    public function testBuildUrlUsesHttpBuildQuery(): void
    {
        $client = new ApiClient();
        $url = $client->buildUrl('ViewDomain', array(
            'APIKey' => 'secret-key',
            'websiteName' => 'ex ample.com',
        ));

        self::assertStringContainsString('ViewDomain?', $url);
        self::assertStringContainsString('APIKey=secret-key', $url);
        self::assertStringContainsString('websiteName=ex+ample.com', $url);
    }

    public function testRedactRemovesApiKeyFromUrls(): void
    {
        $url = 'https://api.example.test/ViewDomain?APIKey=live-secret&websiteName=example.com';
        $redacted = Sensitive::redact($url);

        self::assertStringContainsString('APIKey=***', $redacted);
        self::assertStringNotContainsString('live-secret', $redacted);
    }

    public function testHasPayloadIsNullSafe(): void
    {
        self::assertFalse(ApiClient::hasPayload(null));
        self::assertFalse(ApiClient::hasPayload(array()));
        self::assertTrue(ApiClient::hasPayload(array('a' => 1)));
    }

    public function testDecodeJsonReportsErrors(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid JSON');
        ApiClient::decodeJson('{not-json');
    }

    public function testRequestUrlUsesInjectedTransportAndRedactsLogs(): void
    {
        $fixture = file_get_contents(dirname(__DIR__) . '/fixtures/api/view-domain-success.json');
        self::assertIsString($fixture);

        $client = new ApiClient(function ($method, $url, $payload) use ($fixture) {
            self::assertSame('GET', $method);
            self::assertStringContainsString('APIKey=live-secret', $url);
            self::assertSame('', $payload);

            return $fixture;
        });

        $result = $client->requestUrl(
            'GET',
            'https://api.example.test/ViewDomain?APIKey=live-secret&websiteName=example.com',
            null,
            'ViewDomain'
        );

        self::assertSame(200, $result['result']['responseMsg']['statusCode']);
        self::assertSame('example.com', $result['result']['responseData']['websiteName']);
        self::assertStringContainsString('APIKey=***', $GLOBALS['connectreseller_last_log']['request']['url']);
        self::assertStringNotContainsString('live-secret', $GLOBALS['connectreseller_last_log']['request']['url']);
        self::assertSame('***', $GLOBALS['connectreseller_last_log']['response']['responseData']['authCode']);
    }
}

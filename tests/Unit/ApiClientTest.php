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

    public function testRedactRemovesCredentialsInsideJsonStrings(): void
    {
        $json = '{"APIKey":"live-secret","Password":"p@ss","authCode":"epp-1","ok":true}';
        $redacted = Sensitive::redact($json);

        self::assertStringContainsString('"APIKey":"***"', $redacted);
        self::assertStringContainsString('"Password":"***"', $redacted);
        self::assertStringContainsString('"authCode":"***"', $redacted);
        self::assertStringNotContainsString('live-secret', $redacted);
        self::assertStringNotContainsString('p@ss', $redacted);
        self::assertStringNotContainsString('epp-1', $redacted);
    }

    public function testLastHttpCodeDefaultsTo200ForInjectedTransport(): void
    {
        $client = new ApiClient(function () {
            return '{"responseMsg":{"statusCode":200}}';
        });
        $client->requestUrl('GET', 'https://api.example.test/x', null, 't');
        self::assertSame(200, $client->getLastHttpCode());
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

    public function testAcceptResponseChunkAllowsExactly32MiBAndRejectsTheNextByte(): void
    {
        self::assertSame(32 * 1024 * 1024, ApiClient::MAX_RESPONSE_BYTES);

        $buffer = '';
        $chunk = str_repeat('a', 1024 * 1024);
        $written = 0;
        for ($i = 0; $i < 32; $i++) {
            $written += ApiClient::acceptResponseChunk($buffer, $chunk);
        }

        self::assertSame(ApiClient::MAX_RESPONSE_BYTES, $written);
        self::assertSame(ApiClient::MAX_RESPONSE_BYTES, strlen($buffer));

        try {
            ApiClient::acceptResponseChunk($buffer, 'b');
            self::fail('Expected the byte past 32 MiB to throw');
        } catch (\Exception $exception) {
            self::assertSame('ConnectReseller response exceeded 32 MiB', $exception->getMessage());
        }

        self::assertSame(ApiClient::MAX_RESPONSE_BYTES, strlen($buffer));
        self::assertStringNotContainsString('b', $buffer);
    }

    public function testCurlDispatchUsesTheBoundedWriteCallback(): void
    {
        $source = (string) file_get_contents(
            dirname(__DIR__, 2) . '/modules/registrars/connectreseller/lib/ApiClient.php'
        );
        $dispatch = substr($source, (int) strpos($source, 'function curlDispatch'));

        self::assertStringContainsString('CURLOPT_WRITEFUNCTION', $dispatch);
        self::assertStringContainsString('acceptResponseChunk', $dispatch);
        self::assertStringNotContainsString('CURLOPT_MAXFILESIZE', $dispatch);
        self::assertStringNotContainsString('strlen($response)', $dispatch);
    }
}

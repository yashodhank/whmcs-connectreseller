<?php

declare(strict_types=1);

namespace ConnectReseller\Tests\Unit;

use PHPUnit\Framework\TestCase;
use WHMCS\Module\Addon\ConnectReseller\Helper;

final class AddonDataTablesResponseTest extends TestCase
{
    /** @var Helper */
    private $helper;

    protected function setUp(): void
    {
        require_once dirname(__DIR__, 2) . '/modules/addons/connect_reseller/lib/Helper.php';
        $this->helper = new Helper();
    }

    public function testTldSyncListIsNotError(): void
    {
        $list = array(
            (object) array('tld' => '.com', 'registrationPrice' => 10),
            (object) array('tld' => '.net', 'registrationPrice' => 12),
        );
        self::assertFalse($this->helper->isTldSyncError($list));
        self::assertCount(2, $this->helper->normalizeTldSyncList($list));
    }

    public function testTldSyncEmptyListIsNotError(): void
    {
        self::assertFalse($this->helper->isTldSyncError(array()));
    }

    public function testTldSyncErrorObjectIsDetected(): void
    {
        $error = (object) array(
            'statusCode' => 401,
            'responseText' => 'Invalid API Key',
        );
        self::assertTrue($this->helper->isTldSyncError($error));
        self::assertSame('Invalid API Key', $this->helper->tldSyncErrorMessage($error));
    }

    public function testTldSyncStatus200ObjectIsNotError(): void
    {
        $ok = (object) array('statusCode' => 200, 'responseText' => 'OK');
        self::assertFalse($this->helper->isTldSyncError($ok));
    }

    public function testDataTablesPayloadShape(): void
    {
        $json = $this->helper->dataTablesPayload(3, array(), false, 'Registrar API key is not configured.', 0, 0);
        $decoded = json_decode($json, true);
        self::assertSame(3, $decoded['draw']);
        self::assertSame(0, $decoded['recordsTotal']);
        self::assertSame(array(), $decoded['data']);
        self::assertFalse($decoded['status']);
        self::assertStringContainsString('API key', $decoded['message']);
    }

    public function testAjaxErrorMessageDoesNotTreatEmbeddedHtmlAsApiFailure(): void
    {
        $js = (string) file_get_contents(
            dirname(__DIR__, 2) . '/modules/addons/connect_reseller/assets/js/script.js'
        );
        self::assertStringNotContainsString(
            'text.indexOf("<") !== -1',
            $js
        );
        self::assertStringContainsString('Admin returned HTML instead of JSON', $js);
    }

    public function testAjaxHandlersEmitJsonOnException(): void
    {
        $src = (string) file_get_contents(
            dirname(__DIR__, 2) . '/modules/addons/connect_reseller/lib/Admin/Controller.php'
        );
        self::assertStringContainsString('private function emitJson($body)', $src);
        self::assertStringContainsString('requireAdminToken(true, $draw)', $src);
        self::assertStringContainsString('Sync TLDs failed:', $src);
        self::assertStringContainsString('hash_equals($expected, $token)', $src);
        self::assertStringContainsString("action=' . rawurlencode(\$action)", $src);
    }
}

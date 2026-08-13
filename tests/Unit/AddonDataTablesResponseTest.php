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
        self::assertStringNotContainsString('return text || "Request failed"', $js);
        self::assertStringContainsString('dataType: "text"', $js);
        self::assertStringContainsString('parseAjaxPayload', $js);
        self::assertStringContainsString('$.isArray(json.data)', $js);
    }

    public function testDomainTableRowJsonIsDecodableAndCellsAreWellFormed(): void
    {
        $item = (object) array(
            'tld' => '".com"',
            'registrationPrice' => 1199,
            'renewalPrice' => 1099,
            'transferPrice' => 999,
            'currencyCode' => 'INR',
            'minPeriod' => 1,
            'maxPeriod' => 10,
        );

        $row = $this->helper->formatDomainTableRow($item, 0, 35, true);
        $json = $this->helper->encodeJson(array(
            'draw' => 1,
            'recordsTotal' => 1,
            'recordsFiltered' => 1,
            'data' => array($row),
            'status' => true,
            'message' => '',
        ));

        $decoded = json_decode($json, true);
        self::assertSame(JSON_ERROR_NONE, json_last_error(), json_last_error_msg());
        self::assertIsArray($decoded);
        self::assertTrue($decoded['status']);
        self::assertCount(1, $decoded['data']);

        $cells = $decoded['data'][0];
        self::assertSame('<i class="fas fa-check text-success"></i>', $cells['existtld']);
        self::assertStringNotContainsString('value="".com""', $cells['tld']);
        self::assertStringContainsString('value="&quot;.com&quot;"', $cells['tld']);
        self::assertStringNotContainsString(' / readonly', $cells['tld']);
        self::assertSame(
            substr_count($cells['registration_price'], '<span'),
            substr_count($cells['registration_price'], '</span>')
        );
        self::assertStringContainsString('<span class="remote-pricing">1199</span>', $cells['registration_price']);
        self::assertStringContainsString('<span class="tld-margin-heading">35%</span>', $cells['registration_price']);
    }

    public function testMissingTldIconIsCompleteHtml(): void
    {
        $item = (object) array(
            'tld' => '.net',
            'registrationPrice' => 10,
            'renewalPrice' => 10,
            'transferPrice' => 10,
            'currencyCode' => 'USD',
            'minPeriod' => 1,
            'maxPeriod' => 1,
        );
        $row = $this->helper->formatDomainTableRow($item, 3, 0, false);
        self::assertSame('<i class="fas fa-times"></i>', $row['existtld']);
        self::assertStringContainsString('value=".net"', $row['tld']);
        $price = $row['registration_price'];
        self::assertSame(substr_count($price, '<span'), substr_count($price, '</span>'));
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
        self::assertStringContainsString('ob_end_clean()', $src);
        self::assertStringContainsString("header('Content-Type: application/json; charset=utf-8')", $src);
    }
}

<?php

declare(strict_types=1);

namespace ConnectReseller\Tests\Unit;

use PHPUnit\Framework\TestCase;
use WHMCS\Module\Registrar\ConnectReseller\ApiClient;

final class PhpCompatTest extends TestCase
{
    public function testCountNullIsNotFatal(): void
    {
        $data = null;
        $payload = ApiClient::hasPayload($data) ? json_encode($data) : '';
        self::assertSame('', $payload);
    }

    public function testShippedCodeDoesNotUsePhp80Apis(): void
    {
        $root = dirname(__DIR__, 2);
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root . '/modules', \FilesystemIterator::SKIP_DOTS)
        );
        $banned = array(
            'str_contains(',
            'str_starts_with(',
            'str_ends_with(',
            '?->',
            'match (',
        );

        foreach ($iterator as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }
            $contents = (string) file_get_contents($file->getPathname());
            foreach ($banned as $token) {
                self::assertFalse(
                    strpos($contents, $token) !== false,
                    $file->getPathname() . ' uses ' . $token
                );
            }
        }
    }

    public function testUndefinedEppVariablePatternIsGone(): void
    {
        $source = (string) file_get_contents(
            dirname(__DIR__, 2) . '/modules/registrars/connectreseller/connectreseller.php'
        );
        self::assertStringContainsString('function connectreseller_GetEPPCode', $source);
        self::assertStringContainsString('DomainMapper::websiteName', $source);
        self::assertStringNotContainsString('switchepp_logoutepp', $source);
        self::assertStringNotContainsString('ini_set("display_errors", "1")', $source);
    }
}

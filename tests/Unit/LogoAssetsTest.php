<?php

declare(strict_types=1);

namespace ConnectReseller\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Guard against CRLF/text-mode corruption of registrar logo binaries.
 * Corrupt PNG magic looks like \x89PNG\r\r\n\x1a\r\n instead of \x89PNG\r\n\x1a\n.
 */
final class LogoAssetsTest extends TestCase
{
    private const PNG_MAGIC = "\x89PNG\r\n\x1a\n";
    private const GIF87_MAGIC = 'GIF87a';
    private const GIF89_MAGIC = 'GIF89a';

    public function testRegistrarLogoPngHasValidMagic(): void
    {
        $path = dirname(__DIR__, 2) . '/modules/registrars/connectreseller/logo.png';
        self::assertFileExists($path);
        $bytes = (string) file_get_contents($path);
        self::assertNotSame('', $bytes, 'logo.png must not be empty');
        self::assertSame(
            self::PNG_MAGIC,
            substr($bytes, 0, 8),
            'logo.png must start with PNG magic (not CRLF-mangled)'
        );
        self::assertFalse(
            strpos($bytes, "PNG\r\r\n") !== false,
            'logo.png must not contain CRLF-mangled PNG signature'
        );
    }

    public function testRegistrarLogoGifHasValidMagic(): void
    {
        $path = dirname(__DIR__, 2) . '/modules/registrars/connectreseller/logo.gif';
        self::assertFileExists($path);
        $bytes = (string) file_get_contents($path);
        self::assertNotSame('', $bytes, 'logo.gif must not be empty');
        $prefix = substr($bytes, 0, 6);
        self::assertTrue(
            $prefix === self::GIF87_MAGIC || $prefix === self::GIF89_MAGIC,
            'logo.gif must start with GIF87a/GIF89a (got ' . bin2hex($prefix) . ')'
        );
    }
}

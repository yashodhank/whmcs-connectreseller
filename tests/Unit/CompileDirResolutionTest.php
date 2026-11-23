<?php

declare(strict_types=1);

namespace ConnectReseller\Tests\Unit;

use PHPUnit\Framework\TestCase;
use WHMCS\Module\Addon\ConnectReseller\Admin\Controller;

/** Prod #127: admin Smarty must compile into WHMCS's templates_c, resolved against ROOTDIR. */
final class CompileDirResolutionTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir() . '/cr-root-' . bin2hex(random_bytes(4));
        mkdir($this->root . '/templates_c', 0777, true);
    }

    public function testEmptyKeepsSmartyDefault(): void
    {
        self::assertSame('', Controller::resolveCompileDir('', $this->root));
        self::assertSame('', Controller::resolveCompileDir(null, $this->root));
    }

    public function testRelativeResolvesAgainstRoot(): void
    {
        self::assertSame($this->root . '/templates_c', Controller::resolveCompileDir('templates_c', $this->root . '/'));
    }

    public function testAbsoluteWritableIsKept(): void
    {
        $abs = $this->root . '/templates_c';
        self::assertSame($abs, Controller::resolveCompileDir($abs, '/nowhere'));
    }

    public function testMissingDirFallsBack(): void
    {
        self::assertSame('', Controller::resolveCompileDir($this->root . '/absent', '/'));
        self::assertSame('', Controller::resolveCompileDir('absent', $this->root));
    }

    public function testOutputUsesResolver(): void
    {
        $src = (string) file_get_contents(
            dirname(__DIR__, 2) . '/modules/addons/connect_reseller/lib/Admin/Controller.php'
        );
        self::assertStringContainsString('self::resolveCompileDir($templates_compiledir ?? \'\', ROOTDIR)', $src);
    }
}

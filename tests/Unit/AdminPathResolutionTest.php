<?php

declare(strict_types=1);

namespace ConnectReseller\Tests\Unit;

use PHPUnit\Framework\TestCase;
use WHMCS\Module\Addon\ConnectReseller\Admin\Controller;

/**
 * 3.0.8: the addon admin Controller lives in modules/addons/connect_reseller/lib/Admin,
 * so the registrar lib is dirname(__DIR__, 4) . '/registrars/...'. With 3 levels
 * the path pointed at modules/addons/registrars and the bulk-nameserver and
 * KYC-cron actions reported "unavailable".
 */
final class AdminPathResolutionTest extends TestCase
{
    private const CONTROLLER = '/modules/addons/connect_reseller/lib/Admin/Controller.php';

    public function testRegistrarLibPathResolvesInRepoLayout(): void
    {
        $files = array(
            'KycCron.php', 'CronStateStore.php', 'CapsuleCronStore.php', 'CronGuard.php',
            'Nameservers.php', 'ApiClient.php', 'Helper.php', 'DomainMapper.php', 'Sensitive.php',
        );
        foreach ($files as $file) {
            $path = Controller::registrarLibPath($file);
            self::assertFileExists($path, $file);
            self::assertStringContainsString('/modules/registrars/connectreseller/lib/', str_replace('\\', '/', $path));
        }
    }

    public function testRegistrarLibPathResolvesInMockWhmcsTree(): void
    {
        $root = sys_get_temp_dir() . '/cr-whmcs-' . bin2hex(random_bytes(4));
        $adminDir = $root . '/modules/addons/connect_reseller/lib/Admin';
        $regLib = $root . '/modules/registrars/connectreseller/lib';
        self::assertTrue(mkdir($adminDir, 0777, true));
        self::assertTrue(mkdir($regLib, 0777, true));
        copy(dirname(__DIR__, 2) . self::CONTROLLER, $adminDir . '/Controller.php');
        file_put_contents($regLib . '/KycCron.php', "<?php\n");
        file_put_contents($regLib . '/Nameservers.php', "<?php\n");
        $realRoot = realpath($root);
        self::assertIsString($realRoot);

        // Fresh process so the copied class does not clash with the autoloaded one.
        $script = 'define("WHMCS", true); require ' . var_export($adminDir . '/Controller.php', true) . ';'
            . ' echo \\WHMCS\\Module\\Addon\\ConnectReseller\\Admin\\Controller::registrarLibPath("KycCron.php"), "|",'
            . ' \\WHMCS\\Module\\Addon\\ConnectReseller\\Admin\\Controller::registrarLibPath("Nameservers.php");';
        $out = (string) shell_exec(escapeshellarg(PHP_BINARY) . ' -r ' . escapeshellarg($script) . ' 2>&1');

        $this->removeTree($root);

        self::assertSame(
            $realRoot . '/modules/registrars/connectreseller/lib/KycCron.php|'
            . $realRoot . '/modules/registrars/connectreseller/lib/Nameservers.php',
            $out
        );
    }

    public function testControllerHasNoThreeLevelRegistrarPaths(): void
    {
        $src = (string) file_get_contents(dirname(__DIR__, 2) . self::CONTROLLER);
        self::assertStringNotContainsString('dirname(__DIR__, 3)', $src);
        self::assertSame(1, preg_match_all('/dirname\(__DIR__, 4\)/', $src));
        preg_match_all("/registrarLibPath\('([A-Za-z]+\\.php)'\)/", $src, $m);
        self::assertNotEmpty($m[1]);
        foreach (array_unique($m[1]) as $file) {
            self::assertFileExists(
                dirname(__DIR__, 2) . '/modules/registrars/connectreseller/lib/' . $file,
                $file
            );
        }
    }

    private function removeTree(string $dir): void
    {
        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($it as $f) {
            $f->isDir() ? rmdir($f->getPathname()) : unlink($f->getPathname());
        }
        rmdir($dir);
    }
}

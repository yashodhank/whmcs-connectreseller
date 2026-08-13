<?php

declare(strict_types=1);

namespace ConnectReseller\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class ImportSmokeTest extends TestCase
{
    public function testRegistrarModuleFileExists(): void
    {
        $path = dirname(__DIR__, 2) . '/modules/registrars/connectreseller/connectreseller.php';
        self::assertFileExists($path);
    }

    public function testKycCronWasRenamed(): void
    {
        $legacy = dirname(__DIR__, 2) . '/crons/kycVerfication.php';
        $current = dirname(__DIR__, 2) . '/crons/kycVerification.php';
        self::assertFileDoesNotExist($legacy);
        self::assertFileExists($current);
    }
}

<?php

declare(strict_types=1);

namespace ConnectReseller\Tests\Unit;

use ConnectReseller\Tests\Support\InMemoryCronStore;
use PHPUnit\Framework\TestCase;
use WHMCS\Module\Registrar\ConnectReseller\CronGuard;

final class CronGuardTest extends TestCase
{
    public function testSkipWhenRegistrarInactiveOrApiKeyEmpty(): void
    {
        self::assertFalse(CronGuard::registrarIsConfigured(null));
        self::assertFalse(CronGuard::registrarIsConfigured(''));
        self::assertFalse(CronGuard::registrarIsConfigured('encrypted', function () {
            return '';
        }));
        self::assertFalse(CronGuard::registrarIsConfigured('encrypted', function () {
            return '   ';
        }));
        self::assertTrue(CronGuard::registrarIsConfigured('encrypted', function ($value) {
            self::assertSame('encrypted', $value);

            return 'live-key';
        }));
    }

    public function testSkipWhenAddonInactiveOrCredentialsMissing(): void
    {
        self::assertFalse(CronGuard::addonIsConfigured(0, 'key'));
        self::assertFalse(CronGuard::addonIsConfigured(3, ''));
        self::assertFalse(CronGuard::addonIsConfigured(3, null));
        self::assertTrue(CronGuard::addonIsConfigured(2, 'live-key'));
    }

    public function testLockBlocksOverlapAndExpiredLockIsStealable(): void
    {
        $store = new InMemoryCronStore();
        $now = 1700000000;
        $logs = array();
        $guard = new CronGuard($store, function () use (&$now) {
            return $now;
        }, function ($message) use (&$logs) {
            $logs[] = $message;
        });

        self::assertTrue($guard->acquireLock(CronGuard::LOCK_KYC, 3600));
        self::assertFalse($guard->acquireLock(CronGuard::LOCK_KYC, 3600));
        self::assertSame((string) ($now + 3600), $store->get(CronGuard::LOCK_KYC));

        $now += 3601;
        self::assertTrue($guard->acquireLock(CronGuard::LOCK_KYC, 3600));
        $guard->releaseLock(CronGuard::LOCK_KYC);
        self::assertNull($store->get(CronGuard::LOCK_KYC));
        self::assertTrue($guard->acquireLock(CronGuard::LOCK_PRICE));
    }

    public function testLockCompareAndSetRejectsStaleExpectedValue(): void
    {
        $store = new InMemoryCronStore();
        self::assertTrue($store->compareAndSet(CronGuard::LOCK_PRICE, null, '100'));
        self::assertFalse($store->compareAndSet(CronGuard::LOCK_PRICE, null, '200'));
        self::assertFalse($store->compareAndSet(CronGuard::LOCK_PRICE, '99', '200'));
        self::assertTrue($store->compareAndSet(CronGuard::LOCK_PRICE, '100', '200'));
        self::assertSame('200', $store->get(CronGuard::LOCK_PRICE));
    }

    public function testUnixLastRunTwentyFourHourFrequencyAndMidnightWrap(): void
    {
        $now = strtotime('2026-08-13 00:30:00');
        $yesterdayMorning = strtotime('2026-08-12 00:00:00');
        $lastNight = strtotime('2026-08-12 23:00:00');
        $oneHourAgo = $now - 3600;

        self::assertTrue(CronGuard::frequencyElapsed($yesterdayMorning, $now, 24));
        self::assertFalse(CronGuard::frequencyElapsed($lastNight, $now, 24));
        self::assertFalse(CronGuard::frequencyElapsed($oneHourAgo, $now, 24));
        self::assertTrue(CronGuard::frequencyElapsed(0, $now, 24));
        self::assertTrue(CronGuard::frequencyElapsed($now - 86400, $now, 24));

        $timeOfDayDelta = strtotime('00:30:00') - strtotime('00:30:00');
        self::assertSame(0, $timeOfDayDelta);
        self::assertFalse(CronGuard::frequencyElapsed($now, $now, 24));
    }

    public function testEmptyFrequencyDefaultsToTwentyFourHours(): void
    {
        self::assertSame(24.0, CronGuard::normalizeFrequencyHours(null));
        self::assertSame(24.0, CronGuard::normalizeFrequencyHours(''));
        self::assertSame(24.0, CronGuard::normalizeFrequencyHours('abc'));
        self::assertSame(24.0, CronGuard::normalizeFrequencyHours(0));
        self::assertSame(24.0, CronGuard::normalizeFrequencyHours('-1'));
        self::assertSame(6.0, CronGuard::normalizeFrequencyHours('6'));
        self::assertTrue(CronGuard::frequencyElapsed(1700000000, 1700000000 + 86400, ''));
        self::assertFalse(CronGuard::frequencyElapsed(1700000000, 1700000000 + 3600, ''));
    }

    public function testKycScopingUsesPendingDomainsNotAllClients(): void
    {
        $pending = array(
            array('id' => 1, 'client_id' => 10, 'domainname' => 'a.in'),
            (object) array('id' => 2, 'client_id' => 10, 'domainname' => 'b.in'),
            (object) array('id' => 3, 'client_id' => 22, 'domainname' => 'c.in'),
            array('id' => 4, 'client_id' => 0),
        );
        self::assertSame(array(10, 22), CronGuard::kycClientIdsFromPending($pending));
        self::assertSame(
            array($pending[1], $pending[2], $pending[3]),
            CronGuard::pendingAfterCursor($pending, 1)
        );
        self::assertSame(array($pending[2], $pending[3]), CronGuard::pendingAfterCursor($pending, 2));
        self::assertTrue(CronGuard::clientNeedsKyc('IN'));
        self::assertTrue(CronGuard::clientNeedsKyc('in'));
        self::assertFalse(CronGuard::clientNeedsKyc('US'));
        self::assertTrue(CronGuard::kycCompletedToday('2026-08-13', '2026-08-13', null));
        self::assertFalse(CronGuard::kycCompletedToday('2026-08-13', '2026-08-13', '5'));
        self::assertTrue(CronGuard::kycCompletedToday('2026-08-13', '2026-08-13', ''));
        self::assertFalse(CronGuard::kycCompletedToday('2026-08-12', '2026-08-13', null));
    }

    public function testSkipLogsReasonAndChunkingRespectsCursor(): void
    {
        $logs = array();
        $guard = new CronGuard(new InMemoryCronStore(), function () {
            return 100.0;
        }, function ($message) use (&$logs) {
            $logs[] = $message;
        });

        self::assertSame('skipped', $guard->skip('KYC cron', 'APIKey empty'));
        self::assertSame(array('ConnectReseller KYC cron skipped: APIKey empty'), $logs);

        $items = range(1, 60);
        $chunk = CronGuard::sliceChunk($items, 50, CronGuard::PRICE_CHUNK_SIZE);
        self::assertSame(range(51, 60), $chunk);
        $kycChunk = CronGuard::sliceChunk($items, 0, CronGuard::KYC_CHUNK_SIZE);
        self::assertCount(25, $kycChunk);
    }

    public function testTimeBudgetAbortsAfterWallClockOrLowRemainingPhpTime(): void
    {
        $now = 100.0;
        $guard = new CronGuard(new InMemoryCronStore(), function () use (&$now) {
            return $now;
        });

        self::assertFalse($guard->shouldAbort(100.0, 100.0, 0));
        $now = 120.0;
        self::assertTrue($guard->shouldAbort(100.0, 100.0, 0));

        $now = 110.0;
        self::assertTrue($guard->shouldAbort(100.0, 100.0, 20));
        self::assertFalse($guard->shouldAbort(100.0, 100.0, 120));
    }

    public function testWorkAfterDomainIdIsStableWhenListShifts(): void
    {
        $work = array(
            array('domain_id' => 10, 'tld' => 'a'),
            array('domain_id' => 20, 'tld' => 'b'),
            array('domain_id' => 30, 'tld' => 'c'),
        );
        $after = CronGuard::workAfterDomainId($work, 20);
        self::assertCount(1, $after);
        self::assertSame(30, $after[0]['domain_id']);

        // Removing id 10 (already done / disabled) must not skip 30 when cursor is 20.
        $shifted = array(
            array('domain_id' => 20, 'tld' => 'b'),
            array('domain_id' => 30, 'tld' => 'c'),
        );
        $afterShift = CronGuard::workAfterDomainId($shifted, 20);
        self::assertSame(30, $afterShift[0]['domain_id']);
    }

    public function testKycContinueOnlySemantics(): void
    {
        self::assertTrue(CronGuard::kycCompletedToday('2026-08-13', '2026-08-13', null));
        self::assertFalse(CronGuard::kycCompletedToday('2026-08-13', '2026-08-13', '9'));
    }
}

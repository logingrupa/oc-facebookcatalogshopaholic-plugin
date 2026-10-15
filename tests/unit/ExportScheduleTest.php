<?php

require_once __DIR__ . '/../FeedExportPluginTestCase.php';

use Carbon\Carbon;
use Illuminate\Console\Scheduling\Schedule;
use LoginGrupa\FacebookCatalogShopaholic\Plugin;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Each feed is generated once a day in Riga time, and the price comparison feeds before
 * their crawler's earliest fetch seen in the .lv nginx log, in winter and in summer time.
 */
class ExportScheduleTest extends FeedExportPluginTestCase
{
    /** Reads the schedule only, no table */
    protected $autoMigrate = false;

    #[DataProvider('scheduleProvider')]
    public function testFeedRunsDailyInRigaTimeWithoutOverlapping(string $sCommand, string $sExpression)
    {
        $obEvent = $this->findEvent($sCommand);

        $this->assertSame($sExpression, $obEvent->expression);
        $this->assertSame('Europe/Riga', $obEvent->timezone);
        $this->assertTrue($obEvent->withoutOverlapping);
    }

    public static function scheduleProvider(): array
    {
        return [
            'facebook'  => ['shopaholic:catalog_export.facebook_catalog', '30 2 * * *'],
            'salidzini' => ['shopaholic:catalog_export.salidzini_catalog', '40 6 * * *'],
            'kurpirkt'  => ['shopaholic:catalog_export.kurpirkt_catalog', '40 19 * * *'],
        ];
    }

    #[DataProvider('crawlerProvider')]
    public function testFeedRunsBeforeItsCrawlerAllYear(string $sCommand, string $sEarliestFetchUtc)
    {
        $sRunAt = Plugin::EXPORT_SCHEDULE[$sCommand];

        foreach (['2026-01-15', '2026-07-15'] as $sDate) {
            $obRunAtUtc = Carbon::parse($sDate . ' ' . $sRunAt, 'Europe/Riga')->utc();
            $obEarliestFetch = Carbon::parse($sDate . ' ' . $sEarliestFetchUtc, 'UTC');

            $this->assertTrue(
                $obRunAtUtc->lt($obEarliestFetch),
                $sCommand . ' runs at ' . $obRunAtUtc->format('H:i') . ' UTC on ' . $sDate . ', after the ' . $sEarliestFetchUtc . ' UTC fetch'
            );
        }
    }

    public static function crawlerProvider(): array
    {
        return [
            'salidzini' => ['shopaholic:catalog_export.salidzini_catalog', '05:30'],
            'kurpirkt'  => ['shopaholic:catalog_export.kurpirkt_catalog', '18:50'],
        ];
    }

    private function findEvent(string $sCommand): \Illuminate\Console\Scheduling\Event
    {
        $obSchedule = new Schedule();
        (new Plugin($this->app))->registerSchedule($obSchedule);

        foreach ($obSchedule->events() as $obEvent) {
            if (str_contains($obEvent->command, $sCommand)) {
                return $obEvent;
            }
        }

        $this->fail($sCommand . ' is not scheduled');
    }
}

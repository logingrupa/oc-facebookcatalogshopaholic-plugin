<?php

require_once __DIR__ . '/../FeedExportPluginTestCase.php';

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use LoginGrupa\FacebookCatalogShopaholic\Classes\Console\CatalogExportForFacebookCatalog;
use LoginGrupa\FacebookCatalogShopaholic\Classes\Console\CatalogExportForKurPirktCatalog;
use LoginGrupa\FacebookCatalogShopaholic\Classes\Console\CatalogExportForSalidziniCatalog;
use LoginGrupa\FacebookCatalogShopaholic\Classes\Helper\GenerateXMLForKurPirktCatalog;
use LoginGrupa\FacebookCatalogShopaholic\Models\XMLExportSettings;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * The scheduler runs every feed command on every install. A feed whose export switch is
 * off writes nothing, and a run without offers keeps the previous file and fails.
 */
class ExportCommandGateTest extends FeedExportPluginTestCase
{
    /** Feed => [command, export switch, file name] */
    const FEED_LIST = [
        'facebook'  => ['shopaholic:catalog_export.facebook_catalog', 'facebook_export_is_active', 'facebook_catalogv2.xml'],
        'kurpirkt'  => ['shopaholic:catalog_export.kurpirkt_catalog', 'kurpirkt_export_is_active', 'kurpirkt_catalog.xml'],
        'salidzini' => ['shopaholic:catalog_export.salidzini_catalog', 'salidzini_export_is_active', 'salidzini_catalog.xml'],
    ];

    /** Core schema comes from createApplication(), the catalog is a stub table */
    protected $autoMigrate = false;

    /** @var string temp storage root, so the real storage/app/media is never touched */
    protected $sStoragePath;

    /** @var string */
    protected $sMediaPath;

    public function setUp(): void
    {
        parent::setUp();

        $this->defineSites([['lv', true, true]]);

        Schema::create('lovata_shopaholic_products', function (Blueprint $obTable) {
            $obTable->increments('id');
            $obTable->boolean('active')->default(true);
            $obTable->timestamp('deleted_at')->nullable();
            $obTable->timestamps();
        });

        $this->sStoragePath = sys_get_temp_dir() . '/feedexport-' . uniqid();
        $this->sMediaPath = $this->sStoragePath . '/app/media/';
        mkdir($this->sMediaPath, 0777, true);
        $this->app->useStoragePath($this->sStoragePath);

        $obKernel = $this->app->make(Kernel::class);
        foreach ([CatalogExportForFacebookCatalog::class, CatalogExportForKurPirktCatalog::class, CatalogExportForSalidziniCatalog::class] as $sCommandClass) {
            $obKernel->registerCommand($this->app->make($sCommandClass));
        }

        XMLExportSettings::clearInternalCache();
    }

    public function tearDown(): void
    {
        XMLExportSettings::clearInternalCache();
        File::deleteDirectory($this->sStoragePath);

        parent::tearDown();
    }

    #[DataProvider('switchedOffProvider')]
    public function testSwitchedOffExportWritesNothing(string $sCommand, string $sSettingCode, string $sFileName, ?string $sSwitch)
    {
        if ($sSwitch !== null) {
            XMLExportSettings::set($sSettingCode, $sSwitch);
        }

        $this->assertSame(0, Artisan::call($sCommand));
        $this->assertStringContainsString($sSettingCode . ' is off', Artisan::output());
        $this->assertFileDoesNotExist($this->sMediaPath . $sFileName);
    }

    public static function switchedOffProvider(): array
    {
        $arCaseList = [];
        foreach (self::FEED_LIST as $sFeed => $arFeed) {
            $arCaseList[$sFeed . ' unset'] = array_merge($arFeed, [null]);
            $arCaseList[$sFeed . ' off'] = array_merge($arFeed, ['0']);
        }

        return $arCaseList;
    }

    #[DataProvider('feedProvider')]
    public function testSwitchedOnExportWithoutOffersKeepsTheOldFile(string $sCommand, string $sSettingCode, string $sFileName)
    {
        $sFilePath = $this->sMediaPath . $sFileName;
        file_put_contents($sFilePath, '<root>yesterday</root>');
        touch($sFilePath, time() - 3600);
        XMLExportSettings::set($sSettingCode, '1');

        $this->assertSame(1, Artisan::call($sCommand));
        $this->assertStringContainsString('not written', Artisan::output());
        $this->assertSame('<root>yesterday</root>', file_get_contents($sFilePath));
    }

    public function testGeneratedFeedLeavesOtherWritersTempFilesAlone()
    {
        $sFilePath = $this->sMediaPath . 'kurpirkt_catalog.xml';
        file_put_contents($sFilePath, '<root>yesterday</root>');
        file_put_contents($sFilePath . '.tmp', 'in-flight');
        file_put_contents($sFilePath . '.other.tmp', 'other in-flight');

        $this->generateKurPirktFeed();

        $this->assertStringContainsString('<name>Gel polish 12 ml</name>', file_get_contents($sFilePath));
        $this->assertSame('in-flight', file_get_contents($sFilePath . '.tmp'));
        $this->assertSame('other in-flight', file_get_contents($sFilePath . '.other.tmp'));
        $this->assertSame(
            ['kurpirkt_catalog.xml', 'kurpirkt_catalog.xml.other.tmp', 'kurpirkt_catalog.xml.tmp'],
            $this->getMediaFileList()
        );
    }

    public function testGeneratedFeedSweepsTempFilesOlderThanAnHour()
    {
        $sFilePath = $this->sMediaPath . 'kurpirkt_catalog.xml';
        file_put_contents($sFilePath . '.dead.tmp', 'orphan');
        touch($sFilePath . '.dead.tmp', time() - 7200);
        file_put_contents($sFilePath . '.live.tmp', 'in-flight');

        $this->generateKurPirktFeed();

        $this->assertFileDoesNotExist($sFilePath . '.dead.tmp');
        $this->assertSame('in-flight', file_get_contents($sFilePath . '.live.tmp'));
        $this->assertSame(['kurpirkt_catalog.xml', 'kurpirkt_catalog.xml.live.tmp'], $this->getMediaFileList());
    }

    public function testFailedMoveThrowsAndLeavesNoTempFile()
    {
        mkdir($this->sMediaPath . 'kurpirkt_catalog.xml');

        $obCaught = null;
        try {
            $this->generateKurPirktFeed();
        } catch (\Throwable $obException) {
            $obCaught = $obException;
        }

        $this->assertNotNull($obCaught, 'save() must throw when the move fails');
        $this->assertSame([], array_values(array_filter($this->getMediaFileList(), fn ($sName) => str_ends_with($sName, '.tmp'))));
    }

    public function testGeneratedFeedKeepsThePlainWriteMode()
    {
        $sControlPath = $this->sMediaPath . 'control.xml';
        File::put($sControlPath, '<root/>');

        $this->generateKurPirktFeed();

        clearstatcache();
        $this->assertSame(fileperms($sControlPath) & 0777, fileperms($this->sMediaPath . 'kurpirkt_catalog.xml') & 0777);
    }

    protected function generateKurPirktFeed(): void
    {
        (new GenerateXMLForKurPirktCatalog())->generate(['offers' => [['name' => 'Gel polish 12 ml', 'inventory' => 0]]]);
    }

    protected function getMediaFileList(): array
    {
        $arFileList = array_values(array_diff(scandir($this->sMediaPath), ['.', '..']));
        sort($arFileList);

        return $arFileList;
    }

    /** Price comparison feeds only: the Facebook export reads shop and currency tables first */
    public static function feedProvider(): array
    {
        return array_diff_key(self::FEED_LIST, ['facebook' => true]);
    }
}

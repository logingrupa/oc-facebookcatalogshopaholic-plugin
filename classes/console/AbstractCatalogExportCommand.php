<?php namespace Logingrupa\FacebookCatalogShopaholic\Classes\Console;

use Illuminate\Console\Command;
use Logingrupa\FacebookCatalogShopaholic\Classes\Helper\FeedSiteContext;
use LoginGrupa\FacebookCatalogShopaholic\Models\XMLExportSettings;

/**
 * Class AbstractCatalogExportCommand
 *
 * Applies the shop site, skips when the feed's export switch is off, runs the export and
 * reports from the file mtime whether the feed file was written, failing when it was not.
 *
 * @package Logingrupa\FacebookCatalogShopaholic\Classes\Console
 */
abstract class AbstractCatalogExportCommand extends Command
{
    /**
     * Execute the console command.
     *
     * A switched-on feed that writes no file exits 1: schedule:run sends command output to
     * /dev/null and logs only a non-zero exit code.
     * @return int
     * @throws \Throwable
     */
    public function handle(): int
    {
        $this->applyShopSite();

        $sSettingCode = $this->getActiveSettingCode();
        if (!XMLExportSettings::getValue($sSettingCode)) {
            $this->warn($sSettingCode . ' is off in the XML export settings, nothing written');
            return self::SUCCESS;
        }

        $iStartedAt = time();
        $this->runExport();

        $sFilePath = storage_path($this->getFilePath());
        clearstatcache(true, $sFilePath);
        if (!file_exists($sFilePath) || filemtime($sFilePath) < $iStartedAt) {
            $this->warn('No offers to export, ' . $sFilePath . ' not written');
            return self::FAILURE;
        }

        $this->info(sprintf(
            'Written %s (%.1f MB), peak memory %d MB',
            $sFilePath,
            filesize($sFilePath) / 1048576,
            memory_get_peak_usage(true) / 1048576
        ));

        return self::SUCCESS;
    }

    /**
     * XMLExportSettings switch that enables this feed
     * @return string
     */
    abstract protected function getActiveSettingCode(): string;

    /**
     * Gather the feed data and write the feed file
     * @return void
     */
    abstract protected function runExport(): void;

    /**
     * Feed file path relative to the storage folder
     * @return string
     */
    abstract protected function getFilePath(): string;

    /**
     * The console resolves the primary site but never applies it: translated names use
     * the app locale and site-scoped settings read null. On .no the primary site is the
     * disabled one, the shop runs on the first enabled site.
     *
     * The shop site's route prefix IS applied so feed links land directly on the canonical
     * localized URL (/lv/p/... on .lv, /lt/p/... on .lt, /p/... on .no) without a redirect
     * hop or Accept-Language misrouting.
     * @return void
     */
    protected function applyShopSite(): void
    {
        FeedSiteContext::apply();
    }
}

<?php namespace Logingrupa\FacebookCatalogShopaholic\Classes\Console;

use App;
use Site;
use RuntimeException;
use Illuminate\Console\Command;
use RainLab\Translate\Classes\Translator;
use LoginGrupa\FacebookCatalogShopaholic\Models\XMLExportSettings;

/**
 * Class AbstractCatalogExportCommand
 *
 * Applies the shop site, skips when the feed's export switch is off, runs the export and
 * reports from the file mtime whether the feed file was written.
 *
 * @package Logingrupa\FacebookCatalogShopaholic\Classes\Console
 */
abstract class AbstractCatalogExportCommand extends Command
{
    /**
     * Execute the console command.
     * @throws \Throwable
     */
    public function handle()
    {
        $this->applyShopSite();

        $sSettingCode = $this->getActiveSettingCode();
        if (!XMLExportSettings::getValue($sSettingCode)) {
            $this->warn($sSettingCode . ' is off in the XML export settings, nothing written');
            return;
        }

        $iStartedAt = time();
        $this->runExport();

        $sFilePath = storage_path($this->getFilePath());
        clearstatcache(true, $sFilePath);
        if (!file_exists($sFilePath) || filemtime($sFilePath) < $iStartedAt) {
            $this->warn('No offers to export, ' . $sFilePath . ' not written');
            return;
        }

        $this->info(sprintf(
            'Written %s (%.1f MB), peak memory %d MB',
            $sFilePath,
            filesize($sFilePath) / 1048576,
            memory_get_peak_usage(true) / 1048576
        ));
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
     * The route prefix is NOT applied on purpose: feed links stay bare /p/... so October
     * redirects each visitor to their own language.
     * @return void
     */
    protected function applyShopSite(): void
    {
        $obSite = Site::getPrimarySite();
        if (empty($obSite) || !$obSite->is_enabled) {
            $obSite = Site::listEnabled()->first();
        }

        if (empty($obSite)) {
            throw new RuntimeException('No enabled site definition, cannot resolve the shop site');
        }

        Site::setActiveSite($obSite);
        App::setLocale($obSite->locale);

        if (class_exists(Translator::class)) {
            Translator::instance()->setLocale($obSite->locale, false);
        }
    }
}

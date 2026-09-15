<?php namespace LoginGrupa\FacebookCatalogShopaholic\Classes\Console;

use Site;
use RuntimeException;
use Illuminate\Console\Command;
use RainLab\Translate\Classes\Translator;
use LoginGrupa\FacebookCatalogShopaholic\Models\XMLExportSettings;
use LoginGrupa\FacebookCatalogShopaholic\Classes\Helper\ExportCatalogFacebookHelper;
use LoginGrupa\FacebookCatalogShopaholic\Classes\Helper\GenerateXMLForFacebookCatalog;

/**
 * Class CatalogExportForFacebookCatalog
 *
 * @package LoginGrupa\FacebookCatalogShopaholic\Classes\Console
 * @author  Sergey Zakharevich, s.zakharevich@lovata.com, LOVATA Group
 */
class CatalogExportForFacebookCatalog extends Command
{
    /**
     * @var string command name.
     */
    protected $name = 'shopaholic:catalog_export.facebook_catalog';

    /**
     * @var string The console command description.
     */
    protected $description = 'Generate xml file for Facebook.Catalog in the shop site language';

    /**
     * Execute the console command.
     * @throws \Throwable
     */
    public function handle()
    {
        $this->applyShopSite();

        if (!XMLExportSettings::getValue('facebook_export_is_active')) {
            $this->warn('Facebook export is switched off in the XML export settings, nothing written');
            return;
        }

        $obDataCollection = new ExportCatalogFacebookHelper();
        $obDataCollection->run();

        $sFilePath = storage_path(GenerateXMLForFacebookCatalog::getFilePath());
        if (!file_exists($sFilePath)) {
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
     * The console resolves the primary site but never applies it: page URLs lose the
     * route prefix, translated names use the app locale and site-scoped settings read
     * null. On .no the primary site is the disabled one, the shop runs on the first
     * enabled site.
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

        Site::applyActiveSite($obSite);

        if (class_exists(Translator::class)) {
            Translator::instance()->setLocale($obSite->locale, false);
        }
    }
}

<?php namespace LoginGrupa\FacebookCatalogShopaholic\Classes\Console;

use Illuminate\Console\Command;
use LoginGrupa\FacebookCatalogShopaholic\Classes\Helper\ExportCatalogSalidziniHelper;

/**
 * Class CatalogExportForSalidziniCatalog
 *
 * @package LoginGrupa\FacebookCatalogShopaholic\Classes\Console
 * @author  Sergey Zakharevich, s.zakharevich@lovata.com, LOVATA Group
 */
class CatalogExportForSalidziniCatalog extends Command
{
    /**
     * @var string command name.
     */
    protected $name = 'shopaholic:catalog_export.salidzini_catalog';

    /**
     * @var string The console command description.
     */
    protected $description = 'Generate xml file for Salidzini.Catalog in sites default language';

    /**
     * Execute the console command.
     * @throws \Throwable
     */
    public function handle()
    {
        $obDataCollection = new ExportCatalogSalidziniHelper();
        $obDataCollection->run();
    }
}

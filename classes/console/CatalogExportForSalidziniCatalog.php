<?php namespace LoginGrupa\FacebookCatalogShopaholic\Classes\Console;

use LoginGrupa\FacebookCatalogShopaholic\Classes\Helper\ExportCatalogSalidziniHelper;
use LoginGrupa\FacebookCatalogShopaholic\Classes\Helper\GenerateXMLForSalidziniCatalog;

/**
 * Class CatalogExportForSalidziniCatalog
 *
 * @package LoginGrupa\FacebookCatalogShopaholic\Classes\Console
 * @author  Sergey Zakharevich, s.zakharevich@lovata.com, LOVATA Group
 */
class CatalogExportForSalidziniCatalog extends AbstractCatalogExportCommand
{
    /**
     * @var string command name.
     */
    protected $name = 'shopaholic:catalog_export.salidzini_catalog';

    /**
     * @var string The console command description.
     */
    protected $description = 'Generate xml file for Salidzini.lv in the shop site language';

    /**
     * @return string
     */
    protected function getActiveSettingCode(): string
    {
        return 'salidzini_export_is_active';
    }

    /**
     * @return void
     */
    protected function runExport(): void
    {
        (new ExportCatalogSalidziniHelper())->run();
    }

    /**
     * @return string
     */
    protected function getFilePath(): string
    {
        return GenerateXMLForSalidziniCatalog::getFilePath();
    }
}

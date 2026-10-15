<?php namespace LoginGrupa\FacebookCatalogShopaholic\Classes\Console;

use LoginGrupa\FacebookCatalogShopaholic\Classes\Helper\ExportCatalogKurPirktHelper;
use LoginGrupa\FacebookCatalogShopaholic\Classes\Helper\GenerateXMLForKurPirktCatalog;

/**
 * Class CatalogExportForKurPirktCatalog
 *
 * @package LoginGrupa\FacebookCatalogShopaholic\Classes\Console
 * @author  Sergey Zakharevich, s.zakharevich@lovata.com, LOVATA Group
 */
class CatalogExportForKurPirktCatalog extends AbstractCatalogExportCommand
{
    /**
     * @var string command name.
     */
    protected $name = 'shopaholic:catalog_export.kurpirkt_catalog';

    /**
     * @var string The console command description.
     */
    protected $description = 'Generate xml file for KurPirkt.lv in the shop site language';

    /**
     * @return string
     */
    protected function getActiveSettingCode(): string
    {
        return 'kurpirkt_export_is_active';
    }

    /**
     * @return void
     */
    protected function runExport(): void
    {
        (new ExportCatalogKurPirktHelper())->run();
    }

    /**
     * @return string
     */
    protected function getFilePath(): string
    {
        return GenerateXMLForKurPirktCatalog::getFilePath();
    }
}

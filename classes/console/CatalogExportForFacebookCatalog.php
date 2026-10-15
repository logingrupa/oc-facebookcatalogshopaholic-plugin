<?php namespace LoginGrupa\FacebookCatalogShopaholic\Classes\Console;

use LoginGrupa\FacebookCatalogShopaholic\Classes\Helper\ExportCatalogFacebookHelper;
use LoginGrupa\FacebookCatalogShopaholic\Classes\Helper\GenerateXMLForFacebookCatalog;

/**
 * Class CatalogExportForFacebookCatalog
 *
 * @package LoginGrupa\FacebookCatalogShopaholic\Classes\Console
 * @author  Sergey Zakharevich, s.zakharevich@lovata.com, LOVATA Group
 */
class CatalogExportForFacebookCatalog extends AbstractCatalogExportCommand
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
     * @return string
     */
    protected function getActiveSettingCode(): string
    {
        return 'facebook_export_is_active';
    }

    /**
     * @return void
     */
    protected function runExport(): void
    {
        (new ExportCatalogFacebookHelper())->run();
    }

    /**
     * @return string
     */
    protected function getFilePath(): string
    {
        return GenerateXMLForFacebookCatalog::getFilePath();
    }
}

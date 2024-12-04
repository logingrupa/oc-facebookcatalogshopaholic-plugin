<?php namespace LoginGrupa\FacebookCatalogShopaholic\Widgets;

use Flash;
use Storage;
use Backend\Classes\ReportWidgetBase;
use LoginGrupa\FacebookCatalogShopaholic\Models\XMLExportSettings;
use LoginGrupa\FacebookCatalogShopaholic\Classes\Helper\ExportCatalogHelper;
use LoginGrupa\FacebookCatalogShopaholic\Classes\Helper\ExportCatalogFacebookHelper;
use LoginGrupa\FacebookCatalogShopaholic\Classes\Helper\ExportCatalogKurPirktHelper;
use LoginGrupa\FacebookCatalogShopaholic\Classes\Helper\ExportCatalogSalidziniHelper;
use LoginGrupa\FacebookCatalogShopaholic\Classes\Helper\GenerateXML;
use LoginGrupa\FacebookCatalogShopaholic\Classes\Helper\GenerateXMLForFacebookCatalog;
use LoginGrupa\FacebookCatalogShopaholic\Classes\Helper\GenerateXMLForKurPirktCatalog;
use LoginGrupa\FacebookCatalogShopaholic\Classes\Helper\GenerateXMLForSalidziniCatalog;

/**
 * Class ExportToXML
 *
 * @package LoginGrupa\FacebookCatalogShopaholic\Widgets
 * @author  Sergey Zakharevich, s.zakharevich@lovata.com, LOVATA Group
 */
class ExportToXML extends ReportWidgetBase
{
    /**
     * Render method
     * @return mixed|string
     * @throws \SystemException
     */
    public function render()
    {
        // $this->vars['sFileUrl'] = $this->getFileUrl();
        $this->vars['bYandexExportIsActive'] = $this->getXMLExportSettingsValue('yandex_export_is_active');
        $this->vars['bKurPirktExportIsActive'] = $this->getXMLExportSettingsValue('kurpirkt_export_is_active');
        $this->vars['bFacebookExportIsActive'] = $this->getXMLExportSettingsValue('facebook_export_is_active');
        $this->vars['bSalidziniExportIsActive'] = $this->getXMLExportSettingsValue('salidzini_export_is_active');
        return $this->makePartial('widget');
    }

    /**
     * Check if XML export is active
     *
     * @return string
     */
    public function getXMLExportSettingsValue($XMLExportSettingsValue)
    {
        return (XMLExportSettings::getValue($XMLExportSettingsValue) == null || XMLExportSettings::getValue($XMLExportSettingsValue) == false) ? false : true;
    }

    /**
     * Generate xml for yandex market
     */
    public function onGenerateXMLFileYandexMarket()
    {
        $obDataCollection = new ExportCatalogHelper();
        $obDataCollection->run();
        // \Artisan::call('shopaholic:catalog_export.yandex_market');
        Flash::info(trans('logingrupa.facebookcatalogshopaholic::lang.message.export_is_completed', ['name' => 'Yandex.Market']));

        $this->vars['sFileUrl'] = url('/') . '/storage/' . GenerateXML::getFilePath();
    }

    /**
     * Generate xml for yandex market
     */
    public function onGenerateXMLFileFacebookCatalog()
    {
        $obDataCollection = new ExportCatalogFacebookHelper();
        $obDataCollection->run();
        // \Artisan::call('shopaholic:catalog_export.facebook_catalog');
        Flash::info(trans('logingrupa.facebookcatalogshopaholic::lang.message.export_is_completed', ['name' => 'Facebook.Catalog']));

        $this->vars['sFileUrl'] = url('/') . '/storage/' . GenerateXMLForFacebookCatalog::getFilePath();
    }

    public function onGenerateXMLFileKurPirktCatalog()
    {
        $obDataCollection = new ExportCatalogKurPirktHelper();
        $obDataCollection->run();
        // \Artisan::call('shopaholic:catalog_export.facebook_catalog');
        Flash::info(trans('logingrupa.facebookcatalogshopaholic::lang.message.export_is_completed', ['name' => 'KurPirkt.Catalog']));

        $this->vars['sFileUrl'] = url('/') . '/storage/' . GenerateXMLForKurPirktCatalog::getFilePath();
    }

    public function onGenerateXMLFileSalidziniCatalog()
    {
        $obDataCollection = new ExportCatalogSalidziniHelper();
        $obDataCollection->run();
        // \Artisan::call('shopaholic:catalog_export.facebook_catalog');
        Flash::info(trans('logingrupa.facebookcatalogshopaholic::lang.message.export_is_completed', ['name' => 'Salidzini.Catalog']));
        $this->vars['sFileUrl'] = url('/') . '/storage/' . GenerateXMLForSalidziniCatalog::getFilePath();
    }
}

<?php namespace LoginGrupa\FacebookCatalogShopaholic\Classes\Helper;

use File;
use XMLWriter;
use October\Rain\Argon\Argon;
use LoginGrupa\FacebookCatalogShopaholic\Models\XMLExportSettings;

/**
 * Class GenerateXMLForSalidziniCatalog
 *
 * @package LoginGrupa\FacebookCatalogShopaholic\Classes\Helper
 * @author  Sergey Zakharevich, s.zakharevich@lovata.com, LOVATA Group
 */
class GenerateXMLForSalidziniCatalog
{
    const FILE_NAME = 'salidzini_catalog.xml';
    const DEFAULT_DIRECTORY = 'app/media/';


    /**
     * @var array
     */
    protected $arOffersData = [];

    /**
     * Generated content
     */
    protected $sContent;

    /**
     * @var XMLWriter
     */
    protected $obXMLWriter;

    /**
     * Generate
     *
     * @param array $arData
     */
    public function generate($arData)
    {
        $this->arOffersData = (array)array_get($arData, 'offers', []);
        if (empty($this->arOffersData)) {
            return;
        }

        $this->start();
        $this->setContent();
        $this->stop();

        $this->save();
    }

    /**
     * Start xml content generation
     */
    protected function start()
    {
        $this->obXMLWriter = new XMLWriter();
        $this->obXMLWriter->openMemory();
        $this->obXMLWriter->setIndent(1);
        $this->obXMLWriter->startDocument('1.0', 'UTF-8');
    }

    /**
     * Set content
     */
    protected function setContent()
    {
        // <shop>
        $this->obXMLWriter->startElement('root');
        $this->setShopElement();
        $this->setOffersElement();
        // </shop>
        $this->obXMLWriter->endElement();
    }

    /**
     * Set shop element
     */
    protected function setShopElement()
    {
        $this->obXMLWriter->writeElement('date', Argon::now()->format('Y-m-d h:i:s'));
    }

    /**
     * Set offers element
     */
    protected function setOffersElement()
    {
        // <offers>
        // $this->obXMLWriter->startElement('offers');
        foreach ($this->arOffersData as $arOffer) {
            $this->setOfferElement($arOffer);
        }
        // </offers>
        $this->obXMLWriter->endElement();
    }

    /**
     * Set offer element
     *
     * @param array $arOffer
     */
    protected function setOfferElement($arOffer)
    {
        // <item>
        $this->obXMLWriter->startElement('item');
        // <name>
        $this->obXMLWriter->writeElement('name', array_get($arOffer, 'name'));
        // </name>

        // <link>
        $this->obXMLWriter->writeElement('link', array_get($arOffer, 'link'));
        // </link>

        // <price>
        $this->obXMLWriter->writeElement('price', array_get($arOffer, 'price'));
        // </price>
        // <image>
        $this->obXMLWriter->writeElement('image', !is_null(array_get($arOffer, 'offerImage')) ? array_get($arOffer, 'offerImage') : array_get($arOffer, 'productImage'));
        // </image>

        // <category_full>
        $this->obXMLWriter->writeElement('category_full', array_get($arOffer, 'category_full'));
        // </category_full>

        // <category_link>
        $this->obXMLWriter->writeElement('category_link', array_get($arOffer, 'category_link'));
        // </category_link>

        if (!is_null(array_get($arOffer, 'brand'))) {
            // <brand>
            $this->obXMLWriter->writeElement('brand', array_get($arOffer, 'brand'));
            // </brand>
        } elseif (XMLExportSettings::getValue('salidzini_brand_is_active')) {
            // <brand>
            $this->obXMLWriter->writeElement('brand', XMLExportSettings::getValue('salidzini_brand'));
            // </brand>
        } else {

        }

        // <in_stock>
        $this->obXMLWriter->writeElement('in_stock', array_get($arOffer, 'in_stock'));
        // </in_stock>

        if (XMLExportSettings::getValue('salidzini_delivery_cost_riga_is_active')) {
            // <delivery_cost_riga>
            $this->obXMLWriter->writeElement('delivery_cost_riga', array_get($arOffer, 'delivery_cost_riga'));
            // </delivery_cost_riga>
        }

        if (XMLExportSettings::getValue('salidzini_delivery_latvija_is_active')) {
            // <delivery_latvija>
            $this->obXMLWriter->writeElement('delivery_latvija', array_get($arOffer, 'delivery_latvija'));
            // </delivery_latvija>
        }

        if (XMLExportSettings::getValue('salidzini_delivery_latvijas_pasts_is_active')) {
            // <delivery_latvijas_pasts>
            $this->obXMLWriter->writeElement('delivery_latvijas_pasts', array_get($arOffer, 'delivery_latvijas_pasts'));
            // </delivery_latvijas_pasts>
        }

        if (XMLExportSettings::getValue('salidzini_delivery_dpd_paku_bode_is_active')) {
            // <delivery_dpd_paku_bode>
            $this->obXMLWriter->writeElement('delivery_dpd_paku_bode', array_get($arOffer, 'delivery_dpd_paku_bode'));
            // </delivery_dpd_paku_bode>
        }

        if (XMLExportSettings::getValue('salidzini_delivery_pasta_stacija_is_active')) {
            // <delivery_pasta_stacija>
            $this->obXMLWriter->writeElement('delivery_pasta_stacija', array_get($arOffer, 'delivery_pasta_stacija'));
            // </delivery_pasta_stacija>
        }

        if (XMLExportSettings::getValue('salidzini_delivery_omniva_is_active')) {
            // <delivery_omniva>
            $this->obXMLWriter->writeElement('delivery_omniva', array_get($arOffer, 'delivery_omniva'));
            // </delivery_omniva>
        }

        if (XMLExportSettings::getValue('salidzini_delivery_circlek_is_active')) {
            // <delivery_circlek>
            $this->obXMLWriter->writeElement('delivery_circlek', array_get($arOffer, 'delivery_circlek'));
            // </delivery_circlek>
        }

        if (XMLExportSettings::getValue('salidzini_delivery_venipak_is_active')) {
            // <delivery_venipak>
            $this->obXMLWriter->writeElement('delivery_venipak', array_get($arOffer, 'delivery_venipak'));
            // </delivery_venipak>
        }

        // <delivery_days_riga>
        $this->obXMLWriter->writeElement('delivery_days_riga', array_get($arOffer, 'delivery_days_riga'));
        // </delivery_days_riga>

        // <delivery_days_latvija>
        $this->obXMLWriter->writeElement('delivery_days_latvija', array_get($arOffer, 'delivery_days_latvija'));
        // </delivery_days_latvija>

        // <used>
        $this->obXMLWriter->writeElement('used', array_get($arOffer, 'used'));
        // </used>

        // <adult>
        $this->obXMLWriter->writeElement('adult', array_get($arOffer, 'adult'));
        // </adult>


        // </item>
        $this->obXMLWriter->endElement();
    }

    /**
     * End xml content generation
     */
    protected function stop()
    {
        $this->obXMLWriter->endElement();
        $this->obXMLWriter->endDocument();
        $this->sContent = $this->obXMLWriter->outputMemory();
    }

    /**
     * Save generated content
     */
    protected function save()
    {
        $sMediaPath = self::getFilePath();
        $sFilePath = storage_path($sMediaPath);

        if (file_exists($sFilePath)) {
            unlink($sFilePath);
        }

        File::put($sFilePath, $this->sContent);
    }

    /**
     * Get path to file relative to storage folder
     * @return string
     */
    public static function getFilePath()
    {
        $sResult = self::DEFAULT_DIRECTORY . self::FILE_NAME;

        return $sResult;
    }
}

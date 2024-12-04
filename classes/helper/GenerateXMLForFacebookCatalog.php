<?php namespace LoginGrupa\FacebookCatalogShopaholic\Classes\Helper;

use File;
use XMLWriter;
use October\Rain\Argon\Argon;
use LoginGrupa\FacebookCatalogShopaholic\Models\XMLExportSettings;

/**
 * Class GenerateXMLForFacebookCatalog
 *
 * @package LoginGrupa\FacebookCatalogShopaholic\Classes\Helper
 * @author  Sergey Zakharevich, s.zakharevich@lovata.com, LOVATA Group
 */
class GenerateXMLForFacebookCatalog
{
    const FILE_NAME = 'facebook_catalogv2.xml';
    const DEFAULT_DIRECTORY = 'app/media/';

    /**
     * @var array
     */
    protected $arShopData = [];

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
        $this->arShopData = (array)array_get($arData, 'shop', []);
        $this->arOffersData = (array)array_get($arData, 'offers', []);
        $this->arProductsData = (array)array_get($arData, 'products', []);
        // dd(empty($this->arShopData) || empty($this->arOffersData));
        if (empty($this->arShopData) || empty($this->arOffersData) || empty($this->arProductsData)) {
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
        $this->obXMLWriter->setIndent(3);
        $this->obXMLWriter->setIndentString('   ');
        $this->obXMLWriter->startDocument('1.0', 'UTF-8');
        $this->obXMLWriter->startElement('rss');
        $this->obXMLWriter->writeAttribute('version', '2.0');
        $this->obXMLWriter->writeAttribute('xmlns:g', 'http://base.google.com/ns/1.0');
        $this->obXMLWriter->writeAttribute('xmlns:atom', 'http://www.w3.org/2005/Atom');
    }

    /**
     * Set content
     */
    protected function setContent()
    {
        // <shop>
        $this->obXMLWriter->startElement('channel');
        $this->setShopElement();
        $this->setOffersElement();
        $this->setProductsElement();
        // </shop>
        $this->obXMLWriter->endElement();
    }

    /**
     * Set shop element
     */
    protected function setShopElement()
    {

        $this->obXMLWriter->startElement('atom:link');
        $this->obXMLWriter->writeAttribute('href', url("/") . '/storage/' . self::DEFAULT_DIRECTORY . self::FILE_NAME);
        $this->obXMLWriter->writeAttribute('rel', 'self');
        $this->obXMLWriter->writeAttribute('type', 'application/rss+xml');
        $this->obXMLWriter->endElement();
        $this->obXMLWriter->writeElement('date', Argon::now('Europe/Riga')->format('Y-m-d h:i:s'));
        // <title>
        $this->obXMLWriter->writeElement('title', array_get($this->arShopData, 'name'));
        // </title>
        // <link>
        $this->obXMLWriter->writeElement('link', array_get($this->arShopData, 'url'));
        // </link>
        // <support>
        $this->obXMLWriter->writeElement('support', array_get($this->arShopData, 'agency') . " - " . array_get($this->arShopData, 'email_agency'));
        // </support>
    }

    /**
     * Set Offers elements
     */
    protected function setOffersElement()
    {
        // <item>
        
        foreach ($this->arOffersData as $arOffer) {
            $this->obXMLWriter->startElement('item');
                $this->setOfferElement($arOffer);
            $this->obXMLWriter->endElement();
        }
        // </item>
    }
    /**
     * Set Products elements
     */
    protected function setProductsElement()
    {
        // <item>        
        foreach ($this->arProductsData as $arProduct) {
            $this->obXMLWriter->startElement('item');
                $this->setProductElement($arProduct);
            $this->obXMLWriter->endElement();
        }
        // </item>
    }

    /**
     * Set each Offer element
     *
     * @param array $arOffer
     */
    protected function setProductElement($arProduct)
    {
        
        // <g:item_group_id>
        $this->obXMLWriter->writeElement('g:item_group_id', array_get($arProduct, 'product_id'));
        // </g:item_group_id>

        // <g:ean>
        $this->obXMLWriter->writeElement('g:gtin', array_get($arProduct, 'ean'));
        // </g:ean>

        // <g:google_product_category>
        $this->obXMLWriter->writeElement('g:google_product_category', XMLExportSettings::getValue('facebook_google_product_category'));
        // </g:google_product_category>
        
        // <g:fb_product_category>
        $this->obXMLWriter->writeElement('g:fb_product_category', XMLExportSettings::getValue('facebook_google_product_category'));
        // </g:fb_product_category>
        
        // <g:id>
        $this->obXMLWriter->writeElement('g:id', array_get($arProduct, 'offer_id'));
        // </g:id>

        // <g:title>
        $this->obXMLWriter->writeElement('g:title', array_get($arProduct, 'name'));
        // </g:title>

        // <g:description>
        $this->obXMLWriter->writeElement('g:description',  str_replace(array("\r", "\n", "\t\n"), '', array_get($arProduct, 'description')));
        // </g:description>
        // // <g:rich_text_description>
        // $this->obXMLWriter->writeElement('g:rich_text_description', array_get($arProduct, 'description'));
        // // </g:rich_text_description>

        // <g:link>
        $this->obXMLWriter->writeElement('g:link', array_get($arProduct, 'url'));
        // </g:link>

        // <g:image_link>
        $this->obXMLWriter->writeElement('g:image_link', !is_null(array_get($arProduct, 'offer_image')) ? array_get($arProduct, 'offer_image') : array_get($arProduct, 'product_image'));
        // <g:image_link>
        
        $arImageList = array_get($arProduct, 'images', []);

        if (!empty($arImageList)) {
            foreach ($arImageList as $sImageUrl) {
                // <additional_image_link>
                $this->obXMLWriter->writeElement('additional_image_link', $sImageUrl);
                // </additional_image_link>
            }
        }

        if (!empty(array_get($arProduct, 'color'))) {
            // <additional_variant_attribute>
            $this->obXMLWriter->startElement('additional_variant_attribute');
                // <label>
                $this->obXMLWriter->writeElement('label', 'Select');
                // </label>
                // <value>
                $this->obXMLWriter->writeElement('value', array_get($arProduct, 'color'));
                // </value>
            $this->obXMLWriter->endElement();
            // </additional_variant_attribute>
        }

        // <g:brand>
        $this->obXMLWriter->writeElement('g:brand', array_get($this->arShopData, 'name'));
        // </g:brand>

        // <g:condition>
        $this->obXMLWriter->writeElement('g:condition', 'new');
        // </g:condition>

        // <g:availability>
        $this->obXMLWriter->writeElement('g:availability', array_get($arProduct, 'availability'));
        // </g:availability>

        // <g:inventory>
        $this->obXMLWriter->writeElement('g:inventory', array_get($arProduct, 'inventory'));
        // </g:inventory>

        // <price>
        $this->obXMLWriter->writeElement('g:price', array_get($arProduct, 'price') . ' ' . array_get($arProduct, 'currency_id'));
        // </price>

        // <sale_price>
        if (!empty(array_get($arProduct, 'sale_price'))) {
            $this->obXMLWriter->writeElement('g:sale_price', array_get($arProduct, 'sale_price') . ' ' . array_get($arProduct, 'currency_id'));
        }        
        
        // <g:shipping>
        $this->obXMLWriter->startElement('g:shipping');
        $this->obXMLWriter->writeElement('g:country', XMLExportSettings::getValue('facebook_shipping_country'));
        $this->obXMLWriter->writeElement('g:service', XMLExportSettings::getValue('facebook_shipping_service'));
        $this->obXMLWriter->writeElement('g:price', XMLExportSettings::getValue('facebook_shipping_service_price') . array_get($arProduct, 'currency_id'));
        $this->obXMLWriter->endElement();
        // </g:shipping>

        // <g:custom_label_0>
        $this->obXMLWriter->writeElement('g:custom_label_0', XMLExportSettings::getValue('facebook_google_custom_label'));
        // </g:custom_label_0>

        // <g:video>
        if (!empty(array_get($arProduct, 'video'))) {
            $this->obXMLWriter->startElement('video');
                $this->obXMLWriter->writeElement('url', array_get($arProduct, 'video'));
                $this->obXMLWriter->writeElement('tag', array_get($arProduct, 'color'));
            $this->obXMLWriter->endElement();
        }          
        // </g:video>
        
        // <g:origin_country>
        $this->obXMLWriter->writeElement('g:origin_country', 'LV');
        // </g:origin_country>
        

        // if (!empty($arPropertyList)) {
        //     foreach ($arPropertyList as $arProperty) {
        //         // <param name='' unit=''>
        //         $this->obXMLWriter->startElement('param');
        //         $this->obXMLWriter->writeAttribute('name', array_get($arProperty, 'name'));
        //         $this->obXMLWriter->writeAttribute('unit', array_get($arProperty, 'measure'));
        //         $this->obXMLWriter->text(array_get($arProperty, 'value'));
        //         $this->obXMLWriter->endElement();
        //         // </param>
        //     }
        // }
    }

    /**
     * Set each Offer element
     *
     * @param array $arOffer
     */
    protected function setOfferElement($arOffer)
    {
        
        // <g:item_group_id>
        $this->obXMLWriter->writeElement('g:item_group_id', array_get($arOffer, 'product_id'));
        // </g:item_group_id>

        // <g:ean>
        $this->obXMLWriter->writeElement('g:gtin', array_get($arOffer, 'ean'));
        // </g:ean>

         // <g:google_product_category>
         $this->obXMLWriter->writeElement('g:google_product_category', XMLExportSettings::getValue('facebook_google_product_category'));
         // </g:google_product_category>

         // <g:fb_product_category >
         $this->obXMLWriter->writeElement('g:fb_product_category', XMLExportSettings::getValue('facebook_google_product_category'));
         // </g:fb_product_category >
        
        // <g:id>
        $this->obXMLWriter->writeElement('g:id', array_get($arOffer, 'offer_id'));
        // </g:id>

        // <g:title>
        $this->obXMLWriter->writeElement('g:title', array_get($arOffer, 'name'));
        // </g:title>

        // <g:description>
        $this->obXMLWriter->writeElement('g:description',  str_replace(array("\r", "\n", "\t\n"), '', array_get($arOffer, 'description')));
        // </g:description>
        // <g:link>
        $this->obXMLWriter->writeElement('g:link', array_get($arOffer, 'url'));
        // </g:link>

        // <g:image_link>
        $this->obXMLWriter->writeElement('g:image_link', is_null(array_get($arOffer, 'offer_image')) ? array_get($arOffer, 'product_image') : array_get($arOffer, 'offer_image'));
        // <g:image_link>
        
        $arImageList = array_get($arOffer, 'images', []);

        if (!empty($arImageList)) {
            foreach ($arImageList as $sImageUrl) {
                // <additional_image_link>
                $this->obXMLWriter->writeElement('additional_image_link', $sImageUrl);
                // </additional_image_link>
            }
        }

        if (!empty(array_get($arOffer, 'color'))) {
            // <additional_variant_attribute>
            $this->obXMLWriter->startElement('additional_variant_attribute');
                // <label>
                $this->obXMLWriter->writeElement('label', 'Select');
                // </label>
                // <value>
                $this->obXMLWriter->writeElement('value', array_get($arOffer, 'color'));
                // </value>
            $this->obXMLWriter->endElement();
            // </additional_variant_attribute>
        }

        // <g:brand>
        $this->obXMLWriter->writeElement('g:brand', array_get($this->arShopData, 'name'));
        // </g:brand>

        // <g:condition>
        $this->obXMLWriter->writeElement('g:condition', 'new');
        // </g:condition>

        // <g:availability>
        $this->obXMLWriter->writeElement('g:availability', array_get($arOffer, 'availability'));
        // </g:availability>

        // <g:inventory>
        $this->obXMLWriter->writeElement('g:inventory', array_get($arOffer, 'inventory'));
        // </g:inventory>

        // <price>
        $this->obXMLWriter->writeElement('g:price', array_get($arOffer, 'price') . ' ' . array_get($arOffer, 'currency_id'));
        // </price>

        // <sale_price>
        if (!empty(array_get($arOffer, 'sale_price'))) {
            $this->obXMLWriter->writeElement('g:sale_price', array_get($arOffer, 'sale_price') . ' ' . array_get($arOffer, 'currency_id'));
        }        
        
        // <g:shipping>
        $this->obXMLWriter->startElement('g:shipping');
        $this->obXMLWriter->writeElement('g:country', XMLExportSettings::getValue('facebook_shipping_country'));
        $this->obXMLWriter->writeElement('g:service', XMLExportSettings::getValue('facebook_shipping_service'));
        $this->obXMLWriter->writeElement('g:price', XMLExportSettings::getValue('facebook_shipping_service_price') . array_get($arOffer, 'currency_id'));
        $this->obXMLWriter->endElement();
        // </g:shipping>

        // <g:custom_label_0>
        $this->obXMLWriter->writeElement('g:custom_label_0', XMLExportSettings::getValue('facebook_google_custom_label'));
        // </g:custom_label_0>

        // <g:video>
        if (!empty(array_get($arOffer, 'video'))) {
            $this->obXMLWriter->startElement('video');
                $this->obXMLWriter->writeElement('url', array_get($arOffer, 'video'));
                $this->obXMLWriter->writeElement('tag', array_get($arOffer, 'color'));
            $this->obXMLWriter->endElement();
        }          
        // </g:video>
        
        // <g:origin_country>
        $this->obXMLWriter->writeElement('g:origin_country', 'LV');
        // </g:origin_country>
        

        // if (!empty($arPropertyList)) {
        //     foreach ($arPropertyList as $arProperty) {
        //         // <param name='' unit=''>
        //         $this->obXMLWriter->startElement('param');
        //         $this->obXMLWriter->writeAttribute('name', array_get($arProperty, 'name'));
        //         $this->obXMLWriter->writeAttribute('unit', array_get($arProperty, 'measure'));
        //         $this->obXMLWriter->text(array_get($arProperty, 'value'));
        //         $this->obXMLWriter->endElement();
        //         // </param>
        //     }
        // }
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

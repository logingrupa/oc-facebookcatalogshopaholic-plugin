<?php namespace LoginGrupa\FacebookCatalogShopaholic\Classes\Helper;

use File;
use XMLWriter;
use October\Rain\Argon\Argon;

/**
 * Class GenerateXMLForKurPirktCatalog
 *
 * @package LoginGrupa\FacebookCatalogShopaholic\Classes\Helper
 * @author  Sergey Zakharevich, s.zakharevich@lovata.com, LOVATA Group
 */
class GenerateXMLForKurPirktCatalog
{
    const FILE_NAME = 'kurpirkt_catalog.xml';
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
        // <g:title>
        $this->obXMLWriter->writeElement('name', array_get($arOffer, 'name'));
        // </g:title>

        // <link>
        $this->obXMLWriter->writeElement('link', array_get($arOffer, 'url'));
        // </link>

        // <price>
        $this->obXMLWriter->writeElement('price', array_get($arOffer, 'price'));
        // </price>
        // <image>
        $this->obXMLWriter->writeElement('image', !is_null(array_get($arOffer, 'offerImage')) ? array_get($arOffer, 'offerImage') : array_get($arOffer, 'productImage'));
        // </image>
        // <manufacturer>
        $this->obXMLWriter->writeElement('manufacturer', array_get($arOffer, 'manufacturer'));
        // </manufacturer>
        // <category>
        $this->obXMLWriter->writeElement('category', array_get($arOffer, 'category'));
        // </category>

        // <category_full>
        $this->obXMLWriter->writeElement('category_full', array_get($arOffer, 'category_full'));
        // </category_full>

        // <category_link>
        $this->obXMLWriter->writeElement('category_link', array_get($arOffer, 'category_link'));
        // </category_link>

        // <in_stock>
        $this->obXMLWriter->writeElement('in_stock', array_get($arOffer, 'inventory'));
        // </in_stock>

        // <used>
        $this->obXMLWriter->writeElement('used', '0');
        // </used>

        // <delivery_cost_riga>
        $this->obXMLWriter->writeElement('delivery_cost_riga', array_get($arOffer, 'delivery_cost_riga'));
        // </delivery_cost_riga>


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

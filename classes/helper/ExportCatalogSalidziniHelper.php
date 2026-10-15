<?php namespace LoginGrupa\FacebookCatalogShopaholic\Classes\Helper;

use Event;

use Lovata\Shopaholic\Classes\Item\OfferItem;
use Lovata\Shopaholic\Classes\Item\ProductItem;
use LoginGrupa\FacebookCatalogShopaholic\Models\XMLExportSettings;

/**
 * Class ExportCatalogSalidziniHelper
 *
 * @package LoginGrupa\FacebookCatalogShopaholic\Classes\Helper
 * @author  Sergey Zakharevich, s.zakharevich@lovata.com, LOVATA Group
 */
class ExportCatalogSalidziniHelper extends AbstractPriceComparisonHelper
{
    const EVENT_SALIDZINI_CATALOG_SHOP_DATA = 'shopaholic.salidzini.catalog.shop.data';
    const EVENT_SALIDZINI_CATALOG_OFFER_DATA = 'shopaholic.salidzini.catalog.offer.data';
    const EVENT_SALIDZINI_CATALOG_PRODUCT_DATA = 'shopaholic.salidzini.catalog.product.data';

    /**
     * Generate XML file
     */
    public function run()
    {
        //Prepare data
        $this->initProductListData();

        //Generate XML file
        $obGenerateXMLSalidziniCatalog = new GenerateXMLForSalidziniCatalog();
        $obGenerateXMLSalidziniCatalog->generate($this->arData);
    }

    /**
     * Init offer
     *
     * @param OfferItem $obOffer
     * @param ProductItem $obProduct
     */
    protected function initOffer($obOffer, $obProduct)
    {
        $arOfferData = [
            'name' => $obOffer->name,
            'link' => $this->getOfferUrl($obOffer, $obProduct),
            'price' => $obOffer->price_value,
            'offerImage' => !is_null($obOffer->preview_image) ? $obOffer->preview_image->path : null,
            'productImage' => !is_null($obProduct->preview_image) ? $obProduct->preview_image->path : null,
            'category_full' => $this->getBreadcrumbsNames($obProduct->category),
            'category_link' => $this->getLastParentUrl($obProduct->category),
            'category' => $obProduct->category->name,
            'in_stock' => $this->getStockQuantity($obOffer),
            'brand' => $obProduct->brand->name ? $obProduct->brand->name : null,
            'salidzini_brand' => XMLExportSettings::getValue('salidzini_brand') ? XMLExportSettings::getValue('salidzini_brand') : null,
            'mpn' => $obOffer->external_id,
            'gtin' => $obOffer->code,
            'delivery_cost_riga' => XMLExportSettings::getValue('salidzini_delivery_cost_riga'),
            'delivery_latvija' => XMLExportSettings::getValue('salidzini_delivery_latvija'),
            'delivery_latvijas_pasts' => XMLExportSettings::getValue('salidzini_delivery_latvijas_pasts'),
            'delivery_dpd_paku_bode' => XMLExportSettings::getValue('salidzini_delivery_dpd_paku_bode'),
            'delivery_pasta_stacija' => XMLExportSettings::getValue('salidzini_delivery_pasta_stacija'),
            'delivery_omniva' => XMLExportSettings::getValue('salidzini_delivery_omniva'),
            'delivery_circlek' => XMLExportSettings::getValue('salidzini_delivery_circlek'),
            'delivery_venipak' => XMLExportSettings::getValue('salidzini_delivery_venipak'),
            'delivery_days_riga' => XMLExportSettings::getValue('salidzini_delivery_days_riga'),
            'delivery_days_latvija' => XMLExportSettings::getValue('salidzini_delivery_days_latvija'),
            'used' => XMLExportSettings::getValue('salidzini_used'),
            'adult' => XMLExportSettings::getValue('salidzini_adult'),
        ];

        $arEventData = Event::fire(self::EVENT_SALIDZINI_CATALOG_OFFER_DATA, [$arOfferData]);
        if (!empty($arEventData)) {
            foreach ($arEventData as $arEventOfferData) {
                if (empty($arEventOfferData) || !is_array($arEventOfferData)) {
                    continue;
                }

                $arOfferData = array_merge($arOfferData, $arEventOfferData);
            }
        }

        $this->arData['offers'][] = $arOfferData;
    }
}

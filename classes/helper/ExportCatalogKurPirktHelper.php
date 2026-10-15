<?php namespace LoginGrupa\FacebookCatalogShopaholic\Classes\Helper;

use Event;

use Lovata\Shopaholic\Classes\Item\OfferItem;
use Lovata\Shopaholic\Classes\Item\ProductItem;
use LoginGrupa\FacebookCatalogShopaholic\Models\XMLExportSettings;

/**
 * Class ExportCatalogKurPirktHelper
 *
 * @package LoginGrupa\FacebookCatalogShopaholic\Classes\Helper
 * @author  Sergey Zakharevich, s.zakharevich@lovata.com, LOVATA Group
 */
class ExportCatalogKurPirktHelper extends AbstractPriceComparisonHelper
{
    const EVENT_KURPIRKT_CATALOG_SHOP_DATA = 'shopaholic.kurpirkt.catalog.shop.data';
    const EVENT_KURPIRKT_CATALOG_OFFER_DATA = 'shopaholic.kurpirkt.catalog.offer.data';
    const EVENT_KURPIRKT_CATALOG_PRODUCT_DATA = 'shopaholic.kurpirkt.catalog.product.data';

    /**
     * Generate XML file
     */
    public function run()
    {
        //Prepare data
        $this->initProductListData();

        //Generate XML file
        $obGenerateXMLForKurPirktCatalog = new GenerateXMLForKurPirktCatalog();
        $obGenerateXMLForKurPirktCatalog->generate($this->arData);
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
            'url' => $this->getOfferUrl($obOffer, $obProduct),
            'price' => $obOffer->price_value,
            'offerImage' => !is_null($obOffer->preview_image) ? $obOffer->preview_image->path : null,
            'productImage' => !is_null($obProduct->preview_image) ? $obProduct->preview_image->path : null,
            'manufacturer' => XMLExportSettings::getValue('kurpirkt_manufacturer'),
            'category' => $obProduct->category->name,
            'category_full' => $this->getBreadcrumbsNames($obProduct->category),
            'category_link' => $this->getLastParentUrl($obProduct->category),
            'inventory' => $this->getStockQuantity($obOffer),
            'delivery_cost_riga' => XMLExportSettings::getValue('delivery_cost_riga'),
        ];

        $arEventData = Event::fire(self::EVENT_KURPIRKT_CATALOG_OFFER_DATA, [$arOfferData]);
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

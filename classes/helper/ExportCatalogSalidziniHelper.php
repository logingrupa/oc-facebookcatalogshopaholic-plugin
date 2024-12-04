<?php namespace LoginGrupa\FacebookCatalogShopaholic\Classes\Helper;

use Event;
use Cms\Classes\Page as CmsPage;
use System\Classes\PluginManager;

use Lovata\Shopaholic\Models\Currency;
use Lovata\Shopaholic\Classes\Item\CategoryItem;
use Lovata\Shopaholic\Classes\Item\OfferItem;
use Lovata\Shopaholic\Classes\Item\ProductItem;
use Lovata\Shopaholic\Classes\Collection\ProductCollection;
use Lovata\Shopaholic\Classes\Collection\CategoryCollection;

use Lovata\PropertiesShopaholic\Classes\Item\PropertyItem;
use Lovata\PropertiesShopaholic\Classes\Item\PropertyValueItem;
use LoginGrupa\FacebookCatalogShopaholic\Models\XMLExportSettings;

/**
 * Class ExportCatalogFacebookHelper
 *
 * @package LoginGrupa\FacebookCatalogShopaholic\Classes\Helper
 * @author  Sergey Zakharevich, s.zakharevich@lovata.com, LOVATA Group
 */
class ExportCatalogSalidziniHelper
{
    const EVENT_SALIDZINI_CATALOG_SHOP_DATA = 'shopaholic.salidzini.catalog.shop.data';
    const EVENT_SALIDZINI_CATALOG_OFFER_DATA = 'shopaholic.salidzini.catalog.offer.data';
    const EVENT_SALIDZINI_CATALOG_PRODUCT_DATA = 'shopaholic.salidzini.catalog.product.data';

    /**
     * @var array
     * $arData = [
     *     'offers' => [
     *          [
     *              'name'           => '',
     *              'link'           => '',
     *              'price'            => '',
     *              'image'             => '',
     *              'category_full'          => '',
     *              'category_link'      => '',
     *              'brand'    => '',
     *              'model'    => '',
     *              'color'         => '',
     *              'mpn' => '',
     *              'gtin'    => '',
     *              'in_stock'    => '',
     *              'delivery_cost_riga'    => '',
     *              'delivery_latvija'    => '',
     *              'delivery_latvijas_pasts'    => '',
     *              'delivery_dpd_paku_bode'    => '',
     *              'delivery_pasta_stacija'    => '',
     *              'delivery_omniva'    => '',
     *              'delivery_circlek'    => '',
     *              'delivery_venipak'    => '',
     *              'delivery_days_riga'    => '',
     *              'delivery_days_latvija'    => '',
     *              'delivery_days_latvija'    => '',
     *              'used'    => '',
     *              'adult'    => '',
     *          ],
     *     ],
     * ]
     */
    protected $arData = [
        'offers' => [],
    ];

    /**
     * @var Currency
     */
    protected $obDefaultCurrency;

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
     * Init product list data
     */
    protected function initProductListData()
    {
        $obProductList = ProductCollection::make()->active();
        if ($obProductList->isEmpty()) {
            return;
        }

        /** @var ProductItem $obProduct */
        foreach ($obProductList as $obProduct) {
            $this->initOfferListData($obProduct);
        }
    }

    /**
     * Init offers data
     *
     * @param ProductItem $obProduct $obProduct
     */
    protected function initOfferListData($obProduct)
    {
        if ($obProduct->category->isEmpty()) {
            return;
        }

        $obOfferList = $obProduct->offer;
        if ($obOfferList->isEmpty()) {
            return;
        }

        foreach ($obOfferList as $obOffer) {
            if ($obProduct->offer->count() > 0) {
                $this->initOffer($obOffer, $obProduct);
            }
        }
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
            'link' => ($obProduct->offer->count() == 1) ? CmsPage::url('product', ['slug' => $obProduct->slug]) : CmsPage::url('product', ['slug' => $obProduct->slug, 'offer' => $obOffer->id]),
            'price' => $obOffer->price_value,
            'offerImage' => !is_null($obOffer->preview_image) ? $obOffer->preview_image->path : null,
            'productImage' => !is_null($obProduct->preview_image) ? $obProduct->preview_image->path : null,
            'category_full' => $this->getBreadcrumbsNames($obProduct->category),
            'category_link' => $this->getLastParentUrl($obProduct->category),
            'category' => $obProduct->category->name,
            'in_stock' => $obOffer->quantity,
            'brand' => $obProduct->brand->name ? $obProduct->brand->name : null,
            'salidzini_brand' => XMLExportSettings::getValue('salidzini_brand') ? XMLExportSettings::getValue('salidzini_brand') : null,
            // 'model'          => $obProduct->category->name,
            // 'color'          => $obOffer->category->name,
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

                $arOfferData = array_merge($arOfferData, $arEventOfferData, $arProductData);
            }
        }

        $this->arData['offers'][] = $arOfferData;
        // dd($this->arData['offers']);
    }

    /**
     * Get Bredcrumb names
     *
     * @param CategoryItem $obCategory
     * @return string
     */
    protected function getBreadcrumbsNames($obCategory)
    {
        $arBreadcrumbs = [];
        $obCurrentCategory = $obCategory;
        while ($obCurrentCategory->isNotEmpty()) {
            $arBreadcrumbs[] = [
                'name' => $obCurrentCategory->name,
            ];
            $obCurrentCategory = $obCurrentCategory->parent;
        }

        $arBreadcrumbs = array_reverse($arBreadcrumbs);
        return implode(' > ', array_column($arBreadcrumbs, 'name'));
    }

    /**
     * Get Category parent url
     *
     * @param CategoryItem $obCategory
     * @return string
     */
    protected function getLastParentUrl($obCategory)
    {
        $arBreadcrumbs = [];
        $obCurrentCategory = $obCategory;
        while ($obCurrentCategory->isNotEmpty()) {
            $arBreadcrumbs[] = [
                'url' => $obCurrentCategory->getPageUrl('catalog'),
            ];
            $obCurrentCategory = $obCurrentCategory->parent;
        }

        $arBreadcrumbs = array_reverse($arBreadcrumbs);
        $arEndBreadcrumbs = end($arBreadcrumbs);
        return $arEndBreadcrumbs['url'];
    }

}

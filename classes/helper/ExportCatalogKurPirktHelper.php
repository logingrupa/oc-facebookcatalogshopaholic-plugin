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
class ExportCatalogKurPirktHelper
{
    const EVENT_KURPIRKT_CATALOG_SHOP_DATA = 'shopaholic.kurpirkt.catalog.shop.data';
    const EVENT_KURPIRKT_CATALOG_OFFER_DATA = 'shopaholic.kurpirkt.catalog.offer.data';
    const EVENT_KURPIRKT_CATALOG_PRODUCT_DATA = 'shopaholic.kurpirkt.catalog.product.data';

    /**
     * @var array
     * $arData = [
     *     'shop'   => [
     *          'name'         => '',
     *          'company'      => '',
     *          'url'          => '',
     *          'platform'     => 'October CMS',
     *          'agency'       => '',
     *          'email_agency' => '',
     *          'currencies' => [
     *              'id' => [
     *                  'id'   => '',
     *                  'rate' => '',
     *              ],
     *          ],
     *          'categories' => [
     *              [
     *                  'id'        => '',
     *                  'parent_id' => '',
     *                  'name'      => '',
     *              ],
     *          ]
     *     ],
     *     'offers' => [
     *          [
     *              'rate'           => '',
     *              'name'           => '',
     *              'url'            => '',
     *              'id'             => '',
     *              'price'          => '',
     *              'old_price'      => '',
     *              'currency_id'    => '',
     *              'category_id'    => '',
     *              'images'         => [],
     *              'auto_discounts' => '',
     *              'description'    => '',
     *              'properties'     => [
     *                  [
     *                      'name'    => '',
     *                      'value'   => '',
     *                      'measure' => '',
     *                  ],
     *              ],
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
        // dd($this->arData['offers']);

        //Generate XML file
        $obGenerateXMLForKurPirktCatalog = new GenerateXMLForKurPirktCatalog();
        $obGenerateXMLForKurPirktCatalog->generate($this->arData);
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

        // foreach ($obProductList as $obProduct) {
        //     $this->initProduct($obProduct);
        // }

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
            'url' => ($obProduct->offer->count() == 1) ? CmsPage::url('product', ['slug' => $obProduct->slug]) : CmsPage::url('product', ['slug' => $obProduct->slug, 'offer' => $obOffer->id]),
            'price' => $obOffer->price_value,
            'offerImage' => !is_null($obOffer->preview_image) ? $obOffer->preview_image->path : null,
            'productImage' => !is_null($obProduct->preview_image) ? $obProduct->preview_image->path : null,
            'manufacturer' => XMLExportSettings::getValue('kurpirkt_manufacturer'),
            'category' => $obProduct->category->name,
            'category_full' => $this->getBreadcrumbsNames($obProduct->category),
            'category_link' => $this->getLastParentUrl($obProduct->category),
            'inventory' => $obOffer->quantity,
            'delivery_cost_riga' => XMLExportSettings::getValue('delivery_cost_riga'),
        ];

        $arEventData = Event::fire(self::EVENT_KURPIRKT_CATALOG_OFFER_DATA, [$arOfferData]);
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

<?php namespace Logingrupa\FacebookCatalogShopaholic\Classes\Helper;

use Cms\Classes\Page as CmsPage;
use Lovata\Toolbox\Classes\Item\ItemStorage;
use Lovata\Shopaholic\Classes\Item\OfferItem;
use Lovata\Shopaholic\Classes\Item\ProductItem;
use Lovata\Shopaholic\Classes\Item\CategoryItem;
use Lovata\Shopaholic\Classes\Collection\ProductCollection;

/**
 * Class AbstractPriceComparisonHelper
 *
 * Shared catalog walk of the KurPirkt.lv and Salidzini.lv feeds: one row per active offer
 * of an active product that has a category.
 *
 * @package Logingrupa\FacebookCatalogShopaholic\Classes\Helper
 */
abstract class AbstractPriceComparisonHelper
{
    /**
     * @var array ['offers' => [offer row, ...]]
     */
    protected $arData = [
        'offers' => [],
    ];

    /**
     * Add one offer row to $arData
     *
     * @param OfferItem $obOffer
     * @param ProductItem $obProduct
     */
    abstract protected function initOffer($obOffer, $obProduct);

    /**
     * Init product list data
     *
     * One product at a time: Toolbox keeps every made item in ItemStorage for the whole
     * process, and a cold-cache walk of the full catalog peaks above the 512 MB console limit.
     */
    protected function initProductListData()
    {
        $arProductIDList = ProductCollection::make()->active()->getIDList();
        foreach ($arProductIDList as $iProductID) {
            $obProduct = ProductItem::make($iProductID);
            $this->initOfferListData($obProduct);
            foreach ($obProduct->offer->getIDList() as $iOfferID) {
                ItemStorage::clear(OfferItem::class, $iOfferID);
            }
            ItemStorage::clear(ProductItem::class, $iProductID);
        }
    }

    /**
     * Init offers data
     *
     * @param ProductItem $obProduct
     */
    protected function initOfferListData($obProduct)
    {
        if ($obProduct->category->isEmpty()) {
            return;
        }

        foreach ($obProduct->offer as $obOffer) {
            $this->initOffer($obOffer, $obProduct);
        }
    }

    /**
     * Bare /p/<slug>, plus the offer ID when the product has more than one offer
     *
     * @param OfferItem $obOffer
     * @param ProductItem $obProduct
     * @return string
     */
    protected function getOfferUrl($obOffer, $obProduct)
    {
        $arParams = ['slug' => $obProduct->slug];
        if ($obProduct->offer->count() > 1) {
            $arParams['offer'] = $obOffer->id;
        }

        return CmsPage::url('product', $arParams);
    }

    /**
     * Price comparison sites read stock as units on hand, 1C sends negative stock for oversold offers
     *
     * @param OfferItem $obOffer
     * @return int
     */
    protected function getStockQuantity($obOffer)
    {
        return max(0, (int) $obOffer->quantity);
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

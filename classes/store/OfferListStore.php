<?php namespace LoginGrupa\FacebookCatalogShopaholic\Classes\Store;

use Lovata\Toolbox\Classes\Store\AbstractListStore;

use LoginGrupa\FacebookCatalogShopaholic\Classes\Store\Offer\ActiveProductActiveOfferListStore;

/**
 * Class OfferListStore
 * @package Lovata\Shopaholic\Classes\Store
 * @author  Andrey Kharanenka, a.khoronenko@lovata.com, LOVATA Group
 *
 * @property ActiveProductActiveOfferListStore  $active
 */
class OfferListStore extends AbstractListStore
{
    protected static $instance;

    /**
     * Init store method
     */
    protected function init()
    {
        $this->addToStoreList('activeProductOffers', ActiveProductActiveOfferListStore::class);
    }
}

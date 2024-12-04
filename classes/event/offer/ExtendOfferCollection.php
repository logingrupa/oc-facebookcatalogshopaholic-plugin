<?php namespace LoginGrupa\FacebookCatalogShopaholic\Classes\Event\Offer;

use Lovata\Shopaholic\Classes\Collection\OfferCollection;
use LoginGrupa\FacebookCatalogShopaholic\Classes\Store\OfferListStore;

/**
 * Class ExtendOfferCollection
 * @package Lovata\BaseCode\Classes\Event\Offer
 */
class ExtendOfferCollection
{
    public function subscribe()
    {
        OfferCollection::extend(function ($obOfferList) {
            $this->addCustomMethod($obOfferList);
        });
    }

    /**
     * Add myCustomMethod method
     * @param OfferCollection $obOfferList
     */
    protected function addCustomMethod($obOfferList)
    {
        $obOfferList->addDynamicMethod('activeProductActiveOffers', function () use ($obOfferList) {
            
            $arResultIDList = OfferListStore::instance()->activeProductOffers->get();
            
            return $obOfferList->intersect($arResultIDList);
        });
    }
}
<?php namespace LoginGrupa\FacebookCatalogShopaholic\Classes\Store\Offer;

use Lovata\Toolbox\Classes\Store\AbstractStoreWithoutParam;

use Lovata\Shopaholic\Models\Offer;
use Illuminate\Support\Facades\DB;

/**
 * Class ActiveProductActiveOfferListStore
 * @package Logingrupa\Shopaholic\Classes\Store\Offer
 * @author  Andrey Kharanenka, a.khoronenko@lovata.com, LOVATA Group
 */
class ActiveProductActiveOfferListStore extends AbstractStoreWithoutParam
{
    protected static $instance;

    /**
     * Get ID list from database
     * @return array
     */
    protected function getIDListFromDB() : array
    {
        $arElementIDList = (array) Offer::with('product')
            ->where('active', true)
            ->whereHas('product', function ($query) {
                $query->where('active', true);
            })
            ->pluck('id')
            ->all();

        return $arElementIDList;
    }
}

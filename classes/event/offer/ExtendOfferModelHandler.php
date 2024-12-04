<?php namespace LoginGrupa\FacebookCatalogShopaholic\Classes\Event\Offer;

use Lovata\Shopaholic\Models\Offer;
use Lovata\Shopaholic\Classes\Item\OfferItem;
use Lovata\Toolbox\Classes\Event\ModelHandler;
use Lovata\Shopaholic\Classes\Collection\OfferCollection;
use LoginGrupa\FacebookCatalogShopaholic\Classes\Store\OfferListStore;
/**
 * Class OfferModelHandler
 *
 * @package LoginGrupa\FacebookCatalogShopaholic\Classes\Event\Offer
 * @author  Sergey Zakharevich, s.zakharevich@lovata.com, LOVATA Group
 */
class ExtendOfferModelHandler extends ModelHandler
{
    /** @var Offer */
    protected $obElement;
 
    /**
     * @param $obEvent
     */
    public function subscribe($obEvent)
     {
        parent::subscribe($obEvent);

        Offer::extend(function ($obOffer) {
            $this->extendOfferModel($obOffer);
        });
    }   

    protected function extendOfferModel($obOffer)
    {
        /** @var Offer $obOffer */
        $obOffer->fillable[] = 'preview_image_yandex';
        $obOffer->fillable[] = 'images_yandex';

        $obOffer->attachOne['preview_image_yandex'] = 'System\Models\File';
        $obOffer->attachMany['images_yandex'] = 'System\Models\File';

        $obOffer->addCachedField(['preview_image_yandex', 'images_yandex']);
    }

    /**
     * After save event handler
     */
    protected function afterSave()
    {
        $this->checkFieldChanges('activeProductOffers', OfferListStore::instance()->activeProductOffers);
    }

    /**
     * After delete event handler
     */
    protected function afterDelete()
    {
        if ($this->obElement->activeProductOffers) {
            OfferListStore::instance()->activeProductOffers->clear();
        }
    }

    /**
     * Get model class
     * @return string
     */
    protected function getModelClass()
    {
        return Offer::class;
    }

    /**
     * Get item class
     * @return string
     */
    protected function getItemClass()
    {
        return OfferItem::class;
    }
}

<?php namespace LoginGrupa\FacebookCatalogShopaholic\Classes\Event\Offer;

use Lovata\Toolbox\Classes\Event\AbstractBackendFieldHandler;

use Lovata\Shopaholic\Models\Offer;
use Lovata\Shopaholic\Controllers\Offers;
use LoginGrupa\FacebookCatalogShopaholic\Models\XMLExportSettings;

/**
 * Class ExtendOfferFieldsHandler
 *
 * @package LoginGrupa\FacebookCatalogShopaholic\Classes\Event\Offer
 * @author  Sergey Zakharevich, s.zakharevich@lovata.com, LOVATA Group
 */
class ExtendOfferFieldsHandler extends AbstractBackendFieldHandler
{
    /**
     * Extend fields model
     * @param \Backend\Widgets\Form $obWidget
     */
    protected function extendFields($obWidget)
    {
        $sCodeModelForImages = XMLExportSettings::getValue('code_model_for_images', '');
        if ($sCodeModelForImages != XMLExportSettings::CODE_OFFER) {
            return;
        }

        $arFields = [
            'section_yandex_market' => [
                'label' => 'logingrupa.facebookcatalogshopaholic::lang.field.section_yandex_market',
                'tab' => 'lovata.toolbox::lang.tab.images',
                'type' => 'section',
                'span' => 'full',
            ],
            'preview_image_yandex' => [
                'label' => 'lovata.toolbox::lang.field.preview_image',
                'tab' => 'lovata.toolbox::lang.tab.images',
                'type' => 'fileupload',
                'span' => 'left',
                'mode' => 'image',
                'fileTypes' => 'jpeg,png',
            ],
            'images_yandex' => [
                'label' => 'lovata.toolbox::lang.field.images',
                'type' => 'fileupload',
                'span' => 'left',
                'mode' => 'image',
                'tab' => 'lovata.toolbox::lang.tab.images',
                'fileTypes' => 'jpeg,png',
            ],
        ];

        $obWidget->addTabFields($arFields);
    }

    /**
     * Get model class name
     * @return string
     */
    protected function getModelClass(): string
    {
        return Offer::class;
    }

    /**
     * Get controller class name
     * @return string
     */
    protected function getControllerClass(): string
    {
        return Offers::class;
    }
}

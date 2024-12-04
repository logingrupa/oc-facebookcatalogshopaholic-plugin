<?php namespace LoginGrupa\FacebookCatalogShopaholic\Models;

use Lang;
use System\Classes\PluginManager;
use October\Rain\Database\Traits\Validation;

use Lovata\Toolbox\Models\CommonSettings;
use Lovata\Shopaholic\Models\Currency;

/**
 * Class XMLExportSettings
 *
 * @package LoginGrupa\FacebookCatalogShopaholic\Models
 * @author  Sergey Zakharevich, s.zakharevich@lovata.com, LOVATA Group
 *
 * @mixin \October\Rain\Database\Builder
 * @mixin \Eloquent
 * @mixin \System\Behaviors\SettingsModel
 */
class XMLExportSettings extends CommonSettings
{
    use Validation;

    const SETTINGS_CODE = 'logingrupa_xml_export_settings';

    const RATE_DEFAULT = 'DEFAULT';
    const RATE_CBRF = 'CBRF';
    const RATE_NBU = 'NBU';
    const RATE_NBK = 'NBK';
    const RATE_CB = 'CB';

    const CODE_OFFER = 'offer';
    const CODE_PRODUCT = 'product';

    /**
     * @var string
     */
    public $settingsCode = 'logingrupa_xml_export_settings';
    /**
     * @var array
     */
    public $rules = [
        // 'yandex_short_store_name'   => 'required',
        // 'yandex_full_company_name'  => 'required',
        // 'yandex_store_homepage_url' => 'required',
        // 'yandex_offers_rate'        => 'required|integer',
    ];

    /**
     * @var array
     */
    public $attributeNames = [
        // 'yandex_short_store_name'   => 'logingrupa.facebookcatalogshopaholic::lang.field.yandex_short_store_name',
        // 'yandex_full_company_name'  => 'logingrupa.facebookcatalogshopaholic::lang.field.yandex_full_company_name',
        // 'yandex_store_homepage_url' => 'logingrupa.facebookcatalogshopaholic::lang.field.yandex_store_homepage_url',
        // 'yandex_offers_rate'        => 'logingrupa.facebookcatalogshopaholic::lang.field.yandex_offers_rate',
    ];

    /**
     * Get currency options
     *
     * @return array
     */
    public function getCurrencyOptions()
    {
        $arResult = (array)Currency::where('is_default', false)->lists('name', 'id');

        return $arResult;
    }

    /**
     * Get rate options
     *
     * @return array
     */
    public function getRateOptions()
    {
        return [
            self::RATE_DEFAULT => self::RATE_DEFAULT,
            self::RATE_CBRF => self::RATE_CBRF,
            self::RATE_NBU => self::RATE_NBU,
            self::RATE_NBK => self::RATE_NBK,
            self::RATE_CB => self::RATE_CB,
        ];
    }

    /**
     * Get Shipping options prices
     *
     * @return array
     */
    public function listShippingOptionsPrices()
    {
        $shippingOptions = \Lovata\OrdersShopaholic\Models\ShippingType::lists('name', 'price');
        $mergShippingOptions = array_add($shippingOptions, 0, 'Free delivery/Don\'t deliver - 0.00');

        return array_reverse($mergShippingOptions);
    }

    /**
     * Get Shipping options name
     *
     * @return array
     */
    public function listShippingOptionsNames()
    {
        return \Lovata\OrdersShopaholic\Models\ShippingType::lists('name', 'name');


    }

    /**
     * Get model potions
     *
     * @return array
     */
    public function getGetImagesFromOptions()
    {
        return [
            self::CODE_OFFER => Lang::get('lovata.shopaholic::lang.field.offer'),
            self::CODE_PRODUCT => Lang::get('lovata.toolbox::lang.field.product'),
        ];
    }

    /**
     * Get offer properties options
     *
     * @return array
     */
    public function getOfferPropertiesOptions()
    {
        if (!PluginManager::instance()->hasPlugin('Lovata.PropertiesShopaholic')) {
            return [];
        }

        $arPropertyList = (array)\Lovata\PropertiesShopaholic\Models\Property::active()->lists('name', 'id');

        return $arPropertyList;
    }
}

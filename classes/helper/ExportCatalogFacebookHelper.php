<?php namespace LoginGrupa\FacebookCatalogShopaholic\Classes\Helper;

use Event;
use Cms\Classes\Page as CmsPage;
use System\Classes\PluginManager;
use Lovata\Shopaholic\Models\Currency;
use Lovata\Shopaholic\Models\Category;
use Lovata\Shopaholic\Classes\Item\CategoryItem;
use Lovata\Shopaholic\Classes\Item\OfferItem;
use Lovata\Shopaholic\Classes\Item\ProductItem;
use Lovata\Shopaholic\Classes\Collection\OfferCollection;
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
class ExportCatalogFacebookHelper
{
    const EVENT_FACEBOOK_CATALOG_SHOP_DATA = 'shopaholic.Facebook.Catalog.shop.data';
    const EVENT_FACEBOOK_CATALOG_OFFER_DATA = 'shopaholic.Facebook.Catalog.offer.data';
    const EVENT_FACEBOOK_CATALOG_PRODUCT_DATA = 'shopaholic.Facebook.Catalog.product.data';

    /**
     * @var array
     */
    protected $arXMLExportSettings = [];

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
        'shop' => [],
        'offers' => [],
        'products' => [],
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
        $this->initShopData();
        $this->initOffersListData();
        $this->initProductListData();

        //Generate XML file
        $obGenerateXMLForFacebookCatalog = new GenerateXMLForFacebookCatalog();
        $obGenerateXMLForFacebookCatalog->generate($this->arData);
    }

    /**
     * Init shop data
     */
    protected function initShopData()
    {
        
        array_set($this->arData, 'shop.name', XMLExportSettings::getValue('short_store_name'));
        array_set($this->arData, 'shop.company', XMLExportSettings::getValue('full_company_name'));
        array_set($this->arData, 'shop.url', XMLExportSettings::getValue('store_homepage_url'));
        array_set($this->arData, 'shop.platform', 'October CMS | Shopaholic');
        array_set($this->arData, 'shop.agency', XMLExportSettings::getValue('agency'));
        array_set($this->arData, 'shop.email_agency', XMLExportSettings::getValue('email_agency'));
        array_set($this->arData, 'shop.currencies', $this->getCurrencyList());

        $arShopData = array_get($this->arData, 'shop');
        
        $arEventData = Event::fire(self::EVENT_FACEBOOK_CATALOG_SHOP_DATA, [$arShopData]);
        if (!empty($arEventData)) {
            foreach ($arEventData as $arEventShopData) {
                if (empty($arEventShopData) || !is_array($arEventShopData)) {
                    continue;
                }

                $arShopData = array_merge($arShopData, $arEventShopData);
            }
        }

        return $this->arData['shop'] = $arShopData;
    }

    /**
     * Get currencies
     *
     * @return array
     */
    protected function getCurrencyList()
    {
        $arResult = [];
        $this->obDefaultCurrency = Currency::isDefault()->first();
        if (empty($this->obDefaultCurrency)) {
            return $arResult;
        }

        $bUseMainCurrencyOnly = XMLExportSettings::getValue('use_main_currency_only', false);
        if ($bUseMainCurrencyOnly) {
            $arResult[] = ['id' => $this->obDefaultCurrency->code, 'rate' => '1'];

            return $arResult;
        }

        $obCurrencyList = Currency::active()->get();
        if ($obCurrencyList->isEmpty()) {
            return $arResult;
        }

        foreach ($obCurrencyList as $obCurrency) {
            $sRate = $this->getCurrencyRate($obCurrency);
            if (empty($sRate)) {
                continue;
            }

            $arResult[] = [
                'id' => $obCurrency->code,
                'rate' => $this->getCurrencyRate($obCurrency),
            ];
        }

        return $arResult;
    }

    /**
     * Get currency rate
     * @param Currency $obCurrency
     * @return string
     */
    protected function getCurrencyRate($obCurrency)
    {
        if ($obCurrency->is_default) {
            return '1';
        }

        $bDefaultCurrencyRates = XMLExportSettings::getValue('default_currency_rates', true);
        if ($bDefaultCurrencyRates) {
            return $obCurrency->rate;
        }

        $arXMLExportSettingsRate = (array)XMLExportSettings::getValue('currency_rates', []);
        if (empty($arXMLExportSettingsRate) || !is_array($arXMLExportSettingsRate)) {
            return '';
        }

        $sRate = '';
        foreach ($arXMLExportSettingsRate as $arRate) {
            $iCurrencyId = array_get($arRate, 'currency_id', '');
            $sRate = array_get($arRate, 'rate', '');
            if (empty($iCurrencyId) || $iCurrencyId != $obCurrency->id) {
                continue;
            }

            if ($sRate == XMLExportSettings::RATE_DEFAULT) {
                return $obCurrency->rate;
            }
        }

        return $sRate;
    }

    /**
     * Init Product list data
     */
    protected function initProductListData()
    {
        $obProductList = ProductCollection::make()->active();
        if ($obProductList->isEmpty()) {
            return;
        }
        foreach ($obProductList as $obProduct) {
            $this->initProduct($obProduct);
        }
    }

    /**
     * Init Offer list data
     */
    protected function initOffersListData()
    {
        $obOfferList = OfferCollection::make()->activeProductActiveOffers();
        if ($obOfferList->isEmpty()) {
            return;
        }
        foreach ($obOfferList as $obOffer) {
            if ($obOffer->product->offer->count() > 1) {
                $this->initOffer($obOffer);
            }
        }
    }

    /**
     * Init Product{% set obOffer = obProduct.offer.sort('price|asc').first() %}
     *
     * @param ProductItem $obProduct
     * @return array
     */
    protected function initProduct($obProduct)
    {
        $arProductData = [
            'name' => $obProduct->name,
            'ean' => $obProduct->code ? $obProduct->code : $obProduct->offer->first()->code,
            'url' => CmsPage::url('product', ['slug' => $obProduct->slug]),
            'offer_id' => 'SKU-' . $obProduct->id,
            'product_id' => 'SKU-' . $obProduct->id,
            'offer_count' => $obProduct->offer->count(),
            'price' => $this->getCorrectOfferPrice($obProduct->offer->sort('price|asc')->first())[0],
            'inventory' => 99,
            'visibility' => $obProduct->offer->first()->quantity > 0 ? 'published' : 'hidden',
            'availability' => $obProduct->offer->first()->quantity > 0 ? 'in stock' : 'out of stock',
            'currency_id' => !empty($this->obDefaultCurrency) ? $this->obDefaultCurrency->code : '',
            'product_category' => $this->getBreadcrumbsNames($obProduct->category),
            'product_image' => !is_null($obProduct->preview_image) ? $obProduct->preview_image->path : 'https://via.placeholder.com/1000x821/f1f1f1/?retina=0&text=' . $obProduct->name,
            'images'       => $this->getImages($obProduct->offer->first()),
            'description' => $obProduct->description ? preg_replace('/<[^>]*>/', '', $obProduct->description) : $obProduct->name .' NAILS cosmetics profesionālais produktu klāsts',
            'brand_name'     => !empty($this->getBrandName($obProduct)) ? $this->getBrandName($obProduct) : 'NAILS cosmetics',
            'sale_price' => $this->getCorrectOfferPrice($obProduct->offer->sort('price|asc')->first())[1],
        ];

        $arEventData = Event::fire(self::EVENT_FACEBOOK_CATALOG_PRODUCT_DATA, [$arProductData]);
        if (!empty($arEventData)) {
            foreach ($arEventData as $arEventProductData) {
                if (empty($arEventProductData) || !is_array($arEventProductData)) {
                    continue;
                }

                $arProductData = array_merge($arProductData, $arEventProductData);
            }
        }
        $this->arData['products'][] = $arProductData;
    }

    /**
     * Get offer images
     *
     * @param OfferItem $obOffer
     * @param ProductItem $obProduct 
     * @return array
     */
    protected function getImages($obOffer)
    {
        $arResult = [];

        // Add offer preview image
        if ($obOffer->preview_image) {
            $arResult[] = $obOffer->preview_image->getPath();
        } else {
            $arResult[] = 'https://via.placeholder.com/1000x821/f1f1f1/?retina=0&text=' . (preg_match('/\(([^)]+)\)$/', $obOffer->name, $matches) ? $matches[1] : $obOffer->name);
        }

        // Add product preview image (if available)
        if ($obOffer->product && $obOffer->product->preview_image) {
            $arResult[] = $obOffer->product->preview_image->getPath();
        }

        // Add offer images if only more than one image
        if (count($obOffer->images) > 1) {
            foreach ($obOffer->images as $image) {
                $arResult[] = $image->getPath();
            }
        }

        // Add offer product images only if more than one image
        if (count($obOffer->product->images) > 1) {
            foreach ($obOffer->product->images as $image) {
                $arResult[] = $image->getPath();
            }
        }

        return $arResult;
    }

    /**
     * Get Offer Video path
     *
     * @param OfferItem $obOffer
     * @param ProductItem $obProduct
     *
     * @return array
     */
    protected function getVideoPath($obOffer)
    {
        $google_pagh = 'https://drive.google.com/uc?export=view&id=';
        if(!$obOffer->preview_video) {
            return;
        }
        return $google_pagh . $obOffer->preview_video;
    }

    /**
     * Init Offer
     *
     * @param OfferItem $obOffer
     */
    protected function initOffer($obOffer)
    {
        $arOfferData = [
            'name' => (strlen($obOffer->name) > 65) ? $this->getShorterTitle($obOffer->name) : $obOffer->name,
            'ean' => !is_null($obOffer->code) ? $obOffer->code : $obOffer->product->code,
            'url' => ($obOffer->product->offer->count() == 1) ? CmsPage::url('product', ['slug' => $obOffer->product->slug]) : CmsPage::url('product', ['slug' => $obOffer->product->slug, 'offer' => $obOffer->id]),
            'offer_id' => 'SKU-' . $obOffer->product->id . '-' . $obOffer->id,
            'product_id' => 'SKU-' . $obOffer->product->id,
            'offerCount' => $obOffer->product->offer->count(),
            'id' => $obOffer->id,
            'price' => $this->getCorrectOfferPrice($obOffer)[0],
            'inventory' => $obOffer->quantity < 0 ? 0 : $obOffer->quantity,
            'visibility' => $obOffer->quantity > 0 ? 'published' : 'hidden',
            'availability' => $obOffer->quantity > 0 ? 'in stock' : 'out of stock',
            'currency_id' => !empty($this->obDefaultCurrency) ? $this->obDefaultCurrency->code : '',
            'product_category' => $this->getBreadcrumbsNames($obOffer->product->category),
            'color' => preg_match('/(?<=\().+?(?=\))/', $obOffer->name, $output_array) ? $output_array[0] : '' . $obOffer->variation,
            'offer_image' => !is_null($obOffer->preview_image) ? $obOffer->preview_image->path : null,
            'product_image' => !is_null($obOffer->product->preview_image) ? $obOffer->product->preview_image->path : null,
            'images' => $this->getImages($obOffer),
            'description' => $obOffer->description ? preg_replace('/<[^>]*>/', '', $obOffer->description) : ($obOffer->product->description ? preg_replace('/<[^>]*>/', '', $obOffer->product->description) : $obOffer->name .' NAILS cosmetics profesionālais produktu klāsts'),
            'brand_name'     => $this->getBrandName($obOffer->product),
            'sale_price' => $this->getCorrectOfferPrice($obOffer)[1],
            'video' =>  $this->getVideoPath($obOffer),
        ];

        $arEventData = Event::fire(self::EVENT_FACEBOOK_CATALOG_OFFER_DATA, [$arOfferData]);
        if (!empty($arEventData)) {
            foreach ($arEventData as $arEventOfferData) {
                if (empty($arEventOfferData) || !is_array($arEventOfferData)) {
                    continue;
                }

                $arOfferData = array_merge($arOfferData, $arEventOfferData, $arProductData);
            }
        }

        $this->arData['offers'][] = $arOfferData;

    }
    /**
     * Get offer sales price
     *
     * @param OfferItem $obOffer
     * @return string
     */
    protected function getCorrectOfferPrice($obOffer)
    {
        $price = $obOffer->price;
        $old_price = $obOffer->old_price;

        // Check if there's a discount
    if ($old_price != '0.00') {
        $price = max($old_price, $price);
        $sales_price = min($old_price, $obOffer->price);
    } else {
        $price = $price;
        $sales_price = null;
    }

        return [$price, $sales_price];
    }

    /**
     * Get Product Bredcrumb category names
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
     * Get This code will check if the title is longer than 65 characters.
     * If it is, it will truncate it to 62 characters and append '...' to the end.
     * If the title is already 65 characters or less, it will leave it unchanged.
     * You can use this code to truncate the title before outputting it in your XML file.
     *
     * @param CategoryItem $obCategory
     * @return string
     */
    protected function getShorterTitle($sTitle)
    {
        // Use a regular expression to match the first part and the second part of the title
        preg_match('/^(.+?) \((.+)\)$/', $sTitle, $matches);
        // Koferis manikīra un kosmētikas piederumiem - 3 veidi (Black Diamond)
        // If the match is successful, assign the parts to variables
        if ($matches) {
            $first_part = $matches[1];
            $second_part = $matches[2];
            // Initialize a counter variable
            $counter = 0;
            // Loop until the length is less than or equal to 65
            while(strlen($first_part . ' (' . $second_part . ')') > 65) {
                // Increment while loop counter by 1
                $counter++;
                // Remove the last word from the first part using a regular expression
                $first_part = preg_replace('/\s+\S+$/', '', $first_part);
                // Trim any extra spaces
                $first_part = trim($first_part);
                if ($counter == 6) {
                    break;
                }
            }
            // Return the shortened title
            return $first_part . ' (' . $second_part . ')';
        }
        // If the match is not successful, throw an exception
        else {
            return $sTitle;
        }       
    }

    /**
     * Get brand name
     *
     * @param ProductItem $obProduct
     * @return string
     */
    protected function getBrandName($obProduct)
    {
        $bFieldBrand = XMLExportSettings::getValue('field_brand', false);
        $sResult = $bFieldBrand ? (string)$obProduct->brand->name : '';

        return $sResult;
    }

    /**
     * Get offer property
     *
     * @param OfferItem $obOffer
     * @return array
     */
    protected function getOfferProperties($obOffer)
    {
        $arResult = [];

        $bHasPlugin = PluginManager::instance()->hasPlugin('Lovata.PropertiesShopaholic');
        $arAvailableProperty = (array)XMLExportSettings::getValue('field_offer_properties', []);

        if (!$bHasPlugin || empty($arAvailableProperty)) {
            return $arResult;
        }

        $obPropertyList = $obOffer->property->intersect($arAvailableProperty);
        if ($obPropertyList->isEmpty()) {
            return $arResult;
        }


        /** @var PropertyItem $obPropertyItem */
        foreach ($obPropertyList as $obPropertyItem) {
            if (!$obPropertyItem->hasValue()) {
                continue;
            }

            $obPropertyValueList = $obPropertyItem->property_value;
            if ($obPropertyValueList->isEmpty()) {
                continue;
            }

            /** @var PropertyValueItem $obPropertyValueItem */
            foreach ($obPropertyValueList as $obPropertyValueItem) {
                $arResult[] = $this->getProperty($obPropertyItem, $obPropertyValueItem);
            }
        }

        return $arResult;
    }

    /**
     * Get property
     *
     * @param PropertyItem $obPropertyItem
     * @param PropertyValueItem $obPropertyValueItem
     *
     * @return array
     */
    public function getProperty($obPropertyItem, $obPropertyValueItem)
    {
        $arResult = [
            'name' => $obPropertyItem->name,
            'value' => $obPropertyValueItem->value,
        ];

        if ($obPropertyItem->measure->isNotEmpty()) {
            $arResult['measure'] = $obPropertyItem->measure->name;
        }

        return $arResult;
    }
}

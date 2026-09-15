<?php namespace LoginGrupa\FacebookCatalogShopaholic;

use Event;
use System\Classes\PluginBase;
use Lovata\Shopaholic\Classes\Item\ProductItem;
use Lovata\Shopaholic\Classes\Item\OfferItem;

// Command
use LoginGrupa\FacebookCatalogShopaholic\Classes\Console\CatalogExportForYandexMarket;
use LoginGrupa\FacebookCatalogShopaholic\Classes\Console\CatalogExportForFacebookCatalog;
use LoginGrupa\FacebookCatalogShopaholic\Classes\Console\CatalogExportForKurPirktCatalog;
use LoginGrupa\FacebookCatalogShopaholic\Classes\Console\CatalogExportForSalidziniCatalog;

// Offer event
use LoginGrupa\FacebookCatalogShopaholic\Classes\Event\Offer\ExtendOfferFieldsHandler;
use LoginGrupa\FacebookCatalogShopaholic\Classes\Event\Offer\ExtendOfferModelHandler;
use LoginGrupa\FacebookCatalogShopaholic\Classes\Event\Offer\ExtendOfferCollection;
// Product event
// use LoginGrupa\FacebookCatalogShopaholic\Classes\Event\Product\ExtendProductFieldsHandler;
// use LoginGrupa\FacebookCatalogShopaholic\Classes\Event\Product\ProductModelHandler;

/**
 * Class Plugin
 *
 * @package LoginGrupa\FacebookCatalogShopaholic
 * @author  Sergey Zakharevich, s.zakharevich@lovata.com, LOVATA Group
 */
class Plugin extends PluginBase
{
    /** @var array Plugin dependencies */
    public $require = ['Lovata.Shopaholic', 'Lovata.Toolbox'];

    /**
     * Register settings
     * @return array
     */
    public function registerSettings()
    {
        return [
            'config' => [
                'label' => 'logingrupa.facebookcatalogshopaholic::lang.menu.settings',
                'description' => 'logingrupa.facebookcatalogshopaholic::lang.menu.settings_description',
                'category' => 'lovata.shopaholic::lang.tab.settings',
                'icon' => 'icon-upload',
                'class' => 'LoginGrupa\FacebookCatalogShopaholic\Models\XMLExportSettings',
                'permissions' => ['shopaholic-menu-yandex-market-export'],
                'order' => 9000,
            ],
        ];
    }

    /**
     * Plugin boot method
     */
    public function boot()
    {
        // // Offer event
        Event::subscribe(ExtendOfferFieldsHandler::class);
        Event::subscribe(ExtendOfferModelHandler::class);
        Event::subscribe(ExtendOfferCollection::class);
        // // Product event
        // Event::subscribe(ExtendProductFieldsHandler::class);
        // Event::subscribe(ProductModelHandler::class);

        // ExtendOfferModelHandler registers preview_image_yandex/images_yandex as
        // cached attachment fields; without eager loading Toolbox setCachedFieldList
        // lazy-loads them one query per offer (2 empty queries per offer on cold
        // priming - no offer has yandex files). Product-level yandex relations do
        // NOT exist (product handlers above are disabled) - only the offer-nested
        // paths are valid on ProductItem.
        OfferItem::$arQueryWith = array_merge(OfferItem::$arQueryWith, [
            'preview_image_yandex',
            'images_yandex',
        ]);
        ProductItem::$arQueryWith = array_merge(ProductItem::$arQueryWith, [
            'offer.preview_image_yandex',
            'offer.images_yandex',
        ]);
    }

    /**
     * Register artisan command
     */
    public function register()
    {
        $this->registerConsoleCommand('shopaholic:catalog_export.yandex_market', CatalogExportForYandexMarket::class);
        $this->registerConsoleCommand('shopaholic:catalog_export.facebook_catalog', CatalogExportForFacebookCatalog::class);
        $this->registerConsoleCommand('shopaholic:catalog_export.salidzini_catalog', CatalogExportForSalidziniCatalog::class);
        $this->registerConsoleCommand('shopaholic:catalog_export.kurpirkt_catalog', CatalogExportForKurPirktCatalog::class);

    }

    /**
     * Register scheduled tasks
     * @param \Illuminate\Console\Scheduling\Schedule $obSchedule
     */
    public function registerSchedule($obSchedule)
    {
        // The command checks facebook_export_is_active itself, so a site with the feed
        // switched off writes nothing.
        $obSchedule->command('shopaholic:catalog_export.facebook_catalog')
            ->dailyAt('02:30')
            ->timezone('Europe/Riga')
            ->withoutOverlapping();
    }

    /**
     * @return array
     */
    public function registerReportWidgets()
    {
        return [
            'LoginGrupa\FacebookCatalogShopaholic\Widgets\ExportToXML' => [
                'label' => 'logingrupa.facebookcatalogshopaholic::lang.widget.export_catalog_to_xml_for_yandex_market',
            ],
        ];
    }
}

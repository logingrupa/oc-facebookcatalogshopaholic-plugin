# Logingrupa.FacebookCatalogShopaholic

XML catalog feeds for Facebook Catalog, Yandex Market, Salidzini.lv, and KurPirkt.lv (4 artisan commands, backend widget, `XMLExportSettings`). Namespace `LoginGrupa\FacebookCatalogShopaholic` (legacy casing on existing classes), package `logingrupa/oc-facebookcatalogshopaholic-plugin`. Requires `Lovata.Shopaholic` + `Lovata.Toolbox`. Extends root `CLAUDE.md`.

## Architecture Map
- `classes/console/` - `CatalogExportForFacebookCatalog` (`shopaholic:catalog_export.facebook_catalog`), `CatalogExportForYandexMarket`, `CatalogExportForSalidziniCatalog`, `CatalogExportForKurPirktCatalog` (Facebook, KurPirkt, Salidzini extend `AbstractCatalogExportCommand`; Yandex calls its helper directly with `FeedSiteContext`)
- `classes/helper/` - `FeedSiteContext` (canonical shop site, locale & CMS URL prefix), `ExportCatalog*Helper` + `GenerateXML*`, `AbstractPriceComparisonHelper`, `SavesFeedFileAtomically`
- `classes/event/` - `offer/` (`ExtendOfferFieldsHandler`, `ExtendOfferModelHandler` for `preview_image_yandex`/`images_yandex`, `ExtendOfferCollection`); `product/` handlers on disk are commented out in `Plugin.php`
- `classes/store/` - `OfferListStore`, `offer/ActiveProductActiveOfferListStore`
- `models/XMLExportSettings.php`, `widgets/ExportToXML.php`

## Quality Gates
```bash
php artisan plugin:test Logingrupa.FacebookCatalogShopaholic   # from root (hermetic SQLite)
```
Check feed content parity by running exports against the local DB.

## Gotchas
- **Canonical site prefix (`2.1.9`)**: `FeedSiteContext` applies the shop site, locale, and normalized CMS URL route prefix to all four feeds (including Yandex): `/lv/p/...` on `.lv`, `/lt/p/...` on `.lt`, `/p/...` on `.no`. Backend widget exports run inside `Site::withContext()` and restore admin context afterwards.
- **N+1 eager-load**: `Plugin::boot()` adds `preview_image_yandex`/`images_yandex` to `OfferItem::$arQueryWith` (`ProductItem` yandex eager-load was removed in `2.1.6`; product-level yandex relations do not exist).
- **Settings keys (`2.1.2`)**: settings field names were renamed in `2.1.2` (must be refilled and each feed switch enabled separately on upgrades). Facebook reads `short_store_name` / `store_homepage_url`, not `yandex_*`.
- **October v4**: use `Carbon\Carbon` (no `October\Rain\Argon\Argon`).
- **512M CLI memory cap**: helpers walk ID lists and clear each offer/product from `ItemStorage` after use so cold exports stay well under the 512M Forge CLI cap.
- **Stock & price filters**: KurPirkt/Salidzini clamp negative 1C stock to `max(0, quantity)` and skip `price_value <= 0`.
- **Atomic writes, switches & schedule**: `SavesFeedFileAtomically` writes temp + rename (never a 404 mid-export). The three scheduled commands (`Plugin::EXPORT_SCHEDULE`, daily Europe/Riga, `withoutOverlapping`: facebook `02:30`, salidzini `06:40`, kurpirkt `19:40`) check their `*_export_is_active` switch and exit `1` if active but no fresh file is written; Yandex calls its helper directly.

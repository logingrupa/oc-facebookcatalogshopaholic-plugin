# Logingrupa.FacebookCatalogShopaholic

XML catalog export feeds for external marketplaces: Facebook Catalog, Yandex Market,
Salidzini.lv, KurPirkt.lv - four artisan commands, a backend report widget and an
XMLExportSettings settings page. Namespace LoginGrupa\FacebookCatalogShopaholic, composer
package logingrupa/oc-facebookcatalogshopaholic-plugin. Requires Lovata.Shopaholic +
Lovata.Toolbox. README.md documents the feeds.

## Environment

- Parent app: C:\laragon\www\nc.
- This plugin dir is its OWN git repo - commit here, not in the root repo.

## Architecture map

- classes/console/  CatalogExportForFacebookCatalog (shopaholic:catalog_export.facebook_catalog),
                    CatalogExportForYandexMarket, CatalogExportForSalidziniCatalog,
                    CatalogExportForKurPirktCatalog (same naming pattern)
- classes/helper/   ExportCatalog*Helper + GenerateXML* pair per feed (data gather + XML write)
- classes/event/    offer/ExtendOfferFieldsHandler, ExtendOfferModelHandler (registers
                    preview_image_yandex/images_yandex attachments), ExtendOfferCollection;
                    product/ handlers exist on disk but are COMMENTED OUT in Plugin.php
- classes/store/    OfferListStore, offer/ActiveProductActiveOfferListStore
- models/           XMLExportSettings (settings page, permission shopaholic-menu-yandex-market-export)
- widgets/          ExportToXML report widget

## Quality gates

No working automated gate - tests do not exist and lint does not cover this dir.
composer lint does NOT cover this plugin (phpcs.xml scope excludes plugins/logingrupa) - fix
phpcs.xml scope or lint manually; `vendor/bin/phpcs --standard=phpcs.xml <plugin path>` won't
work either since the ruleset pins files; note as known gap.

## Ship

Ship via /nc-ship (root CLAUDE.md release flow); package logingrupa/oc-facebookcatalogshopaholic-plugin.

## Conventions

Root CLAUDE.md governs: Hungarian notation, Store -> Collection -> Item read path, Tiger-Style.

## Gotchas

- boot() eager-loads preview_image_yandex/images_yandex into OfferItem::$arQueryWith and
  the OFFER-NESTED paths into ProductItem::$arQueryWith - N+1 guard for Toolbox cached
  attachment fields. Product-level yandex relations do NOT exist (product handlers are
  disabled); only offer-nested paths are valid on ProductItem.
- 2.1.2 RENAMED the settings field names - after that update settings must be re-filled,
  and each XML export option must be activated separately. The Facebook feed reads
  short_store_name / store_homepage_url (title, link, g:brand), NOT the yandex_* copies.
- October v4 has no October\Rain\Argon\Argon; the XML writers use Carbon\Carbon. The Argon
  import killed every export from the v4 cutover until 2.1.4.
- Console memory_limit on the Forge boxes is 512M. Iterating a full Toolbox collection keeps
  every item in ItemStorage (cold cache: 1.3 GB). The Facebook helper walks ID lists and
  clears each offer/product from ItemStorage after use; keep that pattern in the other feeds
  if they are ever scheduled.
- Console has no applied site: app locale, null site-scoped settings.
  CatalogExportForFacebookCatalog::applyShopSite() sets the active site + locale (primary
  site, or the first enabled one on .no where the primary is disabled). It deliberately does
  NOT apply the route prefix: feed links stay bare /p/... so October redirects every
  Facebook/Instagram visitor to their own language (owner ruling 2026-09-15). The other three
  commands still run without any site.
- Schedule: registerSchedule runs shopaholic:catalog_export.facebook_catalog dailyAt 02:30
  Europe/Riga on every install; the command exits early when facebook_export_is_active is off.

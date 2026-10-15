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
                    CatalogExportForKurPirktCatalog (same naming pattern); all but Yandex
                    extend AbstractCatalogExportCommand
- classes/helper/   ExportCatalog*Helper + GenerateXML* pair per feed (data gather + XML write);
                    AbstractPriceComparisonHelper is the shared KurPirkt/Salidzini walk,
                    SavesFeedFileAtomically the shared save()
- classes/event/    offer/ExtendOfferFieldsHandler, ExtendOfferModelHandler (registers
                    preview_image_yandex/images_yandex attachments), ExtendOfferCollection;
                    product/ handlers exist on disk but are COMMENTED OUT in Plugin.php
- classes/store/    OfferListStore, offer/ActiveProductActiveOfferListStore
- models/           XMLExportSettings (settings page, permission shopaholic-menu-yandex-market-export)
- widgets/          ExportToXML report widget

## Quality gates

Own phpunit.xml: run `php ../../../vendor/bin/phpunit` from the plugin dir (hermetic SQLite,
tests/FeedExportPluginTestCase.php). Feed content parity is checked by running the exports
against the local DB, not by PHPUnit.
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
  every item in ItemStorage (cold cache: 1.3 GB Facebook, ~590 MB KurPirkt/Salidzini). The
  Facebook helper and AbstractPriceComparisonHelper (KurPirkt + Salidzini) walk ID lists and
  clear each offer/product from ItemStorage after use; cold peak is now under 150 MB.
- Console has no applied site: app locale, null site-scoped settings. The three scheduled
  commands extend AbstractCatalogExportCommand: applyShopSite() sets the active site + locale
  (primary site, or the first enabled one on .no where the primary is disabled), then the
  command checks its *_export_is_active switch, runs, and reports "Written" only when the
  file mtime is newer than the run start. It deliberately does NOT apply the route prefix:
  feed links stay bare /p/... so October redirects every visitor to their own language
  (owner ruling 2026-09-15). Only the Yandex command runs without a site.
- KurPirkt/Salidzini export stock as max(0, quantity): 1C sends negative stock for oversold
  offers and both sites read the field as units on hand.
- Every generator saves through SavesFeedFileAtomically (temp file in the same dir, then
  rename), so a crawler fetching mid-export gets the old or the new file, never a 404.
- Schedule: registerSchedule runs Plugin::EXPORT_SCHEDULE dailyAt Europe/Riga,
  withoutOverlapping, on every install: facebook 02:30, salidzini 06:40 (crawler ~05:35 UTC),
  kurpirkt 19:40 (crawler 18:50-23:10 UTC). Each command exits early when its switch is off.

# Facebook Catalog for Shopaholic

October CMS v4 extension for [Lovata Shopaholic](https://octobercms.com/plugin/lovata-shopaholic) exporting XML feeds for **Facebook Catalog**, **Yandex Market**, **KurPirkt.lv**, and **Salidzini.lv**.

For architecture details and agent gotchas, see [CLAUDE.md](CLAUDE.md).

## Configuration & Usage
- Configure in backend: `Settings > Export to Facebook.Catalog` (`short_store_name`, `store_homepage_url`, per-feed active switches, export paths; default `storage/app/media/`).
- **Dashboard widget**: `Export to XML` runs exports inside the active shop's `Site::withContext()` and restores backend context afterwards.
- **Artisan commands**:
  - `shopaholic:catalog_export.facebook_catalog`
  - `shopaholic:catalog_export.yandex_market`
  - `shopaholic:catalog_export.kurpirkt_catalog`
  - `shopaholic:catalog_export.salidzini_catalog`

## Schedule & Feed Behavior
- Scheduled daily (Europe/Riga, `withoutOverlapping`): Facebook `02:30`, Salidzini `06:40`, KurPirkt `19:40`. Those three scheduled commands exit cleanly when their active switch is off and exit `1` if active but no fresh file is written (Yandex calls its helper directly).
- **Canonical URLs (`2.1.9+`)**: `FeedSiteContext` applies the shop's site, locale, and route prefix across all four feeds (`/lv/p/...` on `.lv`, `/lt/p/...` on `.lt`, `/p/...` on `.no`).
- **Safety**: KurPirkt/Salidzini clamp negative 1C stock to `0` and skip non-positive prices. All feeds write atomically via temp file + rename.

## License
© 2024 Logingrupa ([GNU GPL v3](https://opensource.org/licenses/GPL-3.0)), built on Lovata Shopaholic.

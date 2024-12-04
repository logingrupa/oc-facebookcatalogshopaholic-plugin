# Facebook Catalog for Shopaholic based on Shopaholic.YandexMarketShopaholic

## Overview

The **Facebook Catalog for Shopaholic** plugin for October CMS is an extension of the [Lovata Shopaholic](https://octobercms.com/plugin/lovata-shopaholic) plugin. It enables merchants to export their product catalogs in formats compatible with various platforms, including **Facebook Catalog**, **Yandex Market**, **KurPirkt.lv**, and **Salidzini.lv**.


## Configuration

Navigate to `Settings > Export to Facebook.Catalog` in the October CMS backend to configure the plugin.


### Key Features

- **Export to Facebook Catalog**: Generates an export file in **XML** format for Facebook Catalogs.
- **Platform Support**: Includes support for **Yandex Market**, **KurPirkt.lv**, and **Salidzini.lv** formats.
- **Extensible**: Easily extend and customize the product and offer data fields via event listeners.
- **CLI and GUI Options**: Manage catalog exports using artisan commands or backend widgets.
- **Customizable Export Paths**: Define the export file's storage location.

## Installation

### Artisan

Use the following artisan command to install the plugin:

```bash
php artisan plugin:install Logingrupa.FacebookCatalogShopaholic
```

### Exporting Files

1. **Dashboard Widget**: Use the `Export to XML` widget from the October CMS dashboard to generate catalogs.
2. **Artisan Commands**: Execute the following artisan commands for platform-specific exports:
   - **Facebook Catalog**: `shopaholic:catalog_export.facebook_catalog`
   - **Yandex Market**: `shopaholic:catalog_export.yandex_market`
   - **KurPirkt.lv**: `shopaholic:catalog_export.kur_pirkt`
   - **Salidzini.lv**: `shopaholic:catalog_export.salidzini`

By default, export files are stored in `storage/app/media/`.

## Detailed Functionality

### Core Features

#### 1. Export Helpers
Located in `classes/helper`, these classes handle the generation of XML files for various platforms:
- **`ExportCatalogFacebookHelper`**: Generates XML for Facebook Catalogs.
- **`ExportCatalogHelper`**: Abstracts common catalog export logic.
- **`ExportCatalogKurPirktHelper`**, **`ExportCatalogSalidziniHelper`**: Platform-specific helpers for KurPirkt.lv and Salidzini.lv.
- **`GenerateXML`**: Core logic for building XML documents.

#### 2. Event Listeners
Found in `classes/event`, these handlers allow the extension of product and offer data models:
- **`ExtendProductFieldsHandler`**: Adds additional fields to product models.
- **`ExtendOfferFieldsHandler`**: Extends offer models with custom fields.
- **`ExtendOfferCollection`**: Modifies the collection logic for offers.

#### 3. Console Commands
Located in `classes/console`, these commands provide a CLI interface for generating exports:
- **`CatalogExportForFacebookCatalog.php`**: Command for Facebook Catalog export.
- **`CatalogExportForYandexMarket.php`**: Command for Yandex Market export.
- Additional commands for KurPirkt.lv and Salidzini.lv.

#### 4. Widgets
- **`ExportToXML.php`**: Provides a widget to generate XML files directly from the October CMS backend.

### Language Support
- **English**: `lang/en/lang.php`
- **Russian**: `lang/ru/lang.php`

## What is Shopaholic?

Shopaholic is the most popular e-commerce ecosystem for October CMS, designed with a focus on modularity and simplicity. Learn more at [shopaholic.one](https://shopaholic.one).

## Credits

- Extended by **Logingrupa** as an extension of [Lovata Shopaholic](https://octobercms.com/plugin/lovata-shopaholic).
- Core plugin development by [Sergey Zakharevich](https://github.com/wobqqq) and [Andrey Kharanenka](https://github.com/kharanenka).

## License

© 2024, Logingrupa under [GNU GPL v3](https://opensource.org/licenses/GPL-3.0).

This plugin builds on the original [Shopaholic Plugin](https://github.com/lovata/oc-shopaholic-plugin) by **LOVATA Group, LLC**.
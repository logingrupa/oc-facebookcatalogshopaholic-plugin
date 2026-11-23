<?php namespace Logingrupa\FacebookCatalogShopaholic\Classes\Helper;

use App;
use Cms;
use Config;
use ReflectionProperty;
use RuntimeException;
use Site;
use System\Models\SiteDefinition;
use RainLab\Translate\Classes\Translator;

/**
 * Class FeedSiteContext
 *
 * Applies the shop's canonical site context (locale, site-scoped settings and CMS URL
 * route prefix) for catalog XML exports, whether triggered from CLI or from the backend
 * report widget.
 *
 * Prefixed shops (.lv -> /lv, .lt -> /lt) emit canonical prefixed URLs matching the feed
 * language so price comparison sites and ad catalogs do not publish redirecting bare URLs
 * or misroute English-UI local shoppers via Accept-Language. Unprefixed shops (.no, where
 * is_prefixed is false or route_prefix is "/") clear the CMS URL prefix to null so URLs
 * stay at /p/... without a doubled slash.
 *
 * @package Logingrupa\FacebookCatalogShopaholic\Classes\Helper
 */
class FeedSiteContext
{
    /**
     * Resolve the shop's own site: primary if enabled, otherwise the first enabled site
     * (.no keeps a disabled primary Latvian row and serves from the first enabled site).
     *
     * @return SiteDefinition
     */
    public static function resolveShopSite(): SiteDefinition
    {
        $obSite = Site::getPrimarySite();
        if (empty($obSite) || !$obSite->is_enabled) {
            $obSite = Site::listEnabled()->first();
        }

        if (empty($obSite)) {
            throw new RuntimeException('No enabled site definition, cannot resolve the shop site');
        }

        return $obSite;
    }

    /**
     * Apply the shop site, its locale and its normalized CMS URL prefix to the current process.
     *
     * @param SiteDefinition|null $obSite
     * @return SiteDefinition
     */
    public static function apply(?SiteDefinition $obSite = null): SiteDefinition
    {
        $obSite = $obSite ?: self::resolveShopSite();

        Site::applyActiveSite($obSite);
        self::setCmsUrlPrefix(self::normalizedRoutePrefix($obSite));

        if ($obSite->locale && class_exists(Translator::class)) {
            Translator::instance()->setLocale($obSite->locale, false);
        }

        return $obSite;
    }

    /**
     * Run a callback in the shop's canonical site context and restore the previous site,
     * locale, config and CMS URL prefix afterwards. Uses Site::withContext() so Multisite
     * settings (XMLExportSettings) read the shop site's row even when triggered from the
     * backend while an admin is editing another locale.
     *
     * @param callable $fnCallback
     * @return mixed
     */
    public static function runInShopSite(callable $fnCallback)
    {
        $iPreviousSiteId = Site::getActiveSiteId();
        $sPreviousLocale = App::getLocale();
        $sPreviousFallbackLocale = App::getFallbackLocale();
        $sPreviousTranslatorLocale = class_exists(Translator::class)
            ? Translator::instance()->getLocale()
            : null;
        $sPreviousPrefix = self::getCmsUrlPrefix();
        $sPreviousTheme = Config::get('cms.active_theme');
        $sPreviousAppUrl = Config::get('app.url');
        $sPreviousTimezone = Config::get('cms.timezone');

        try {
            $obSite = self::apply();

            return Site::withContext($obSite->id, fn () => $fnCallback());
        } finally {
            Config::set('cms.active_theme', $sPreviousTheme);
            Config::set('app.url', $sPreviousAppUrl);
            Config::set('cms.timezone', $sPreviousTimezone);
            self::setCmsUrlPrefix($sPreviousPrefix);
            App::setLocale($sPreviousLocale);
            App::setFallbackLocale($sPreviousFallbackLocale);

            if ($sPreviousTranslatorLocale !== null && class_exists(Translator::class)) {
                Translator::instance()->setLocale($sPreviousTranslatorLocale, false);
            }

            if ($iPreviousSiteId !== null) {
                Site::setActiveSiteId($iPreviousSiteId);
            } else {
                Config::set('system.active_site', null);
            }
        }
    }

    /**
     * Normalize a site's route prefix for Cms::url(). Returns null for unprefixed sites
     * and for a bare "/" prefix (.no), because Cms::url() treats an empty string "" as an
     * active prefix and prepends a leading slash that turns into "//path".
     *
     * @param SiteDefinition $obSite
     * @return string|null
     */
    public static function normalizedRoutePrefix(SiteDefinition $obSite): ?string
    {
        if (!$obSite->is_prefixed) {
            return null;
        }

        $sPrefix = trim((string) $obSite->route_prefix, '/');

        return $sPrefix === '' ? null : $sPrefix;
    }

    /**
     * Read the current urlPathPrefix off the Cms helper singleton.
     *
     * @return string|null
     */
    public static function getCmsUrlPrefix(): ?string
    {
        $obCms = Cms::getFacadeRoot();
        $obProperty = new ReflectionProperty($obCms, 'urlPathPrefix');
        $mValue = $obProperty->getValue($obCms);

        return is_string($mValue) ? $mValue : null;
    }

    /**
     * Set or clear urlPathPrefix on the Cms helper singleton. Cms::setUrlPrefix() only
     * accepts a string and leaves "" non-null, so clearing back to null goes through the
     * property directly.
     *
     * @param string|null $sPrefix
     * @return void
     */
    public static function setCmsUrlPrefix(?string $sPrefix): void
    {
        if ($sPrefix !== null && trim($sPrefix, '/') !== '') {
            Cms::setUrlPrefix($sPrefix);

            return;
        }

        $obCms = Cms::getFacadeRoot();
        $obProperty = new ReflectionProperty($obCms, 'urlPathPrefix');
        $obProperty->setValue($obCms, null);
    }
}

<?php

require_once __DIR__ . '/../FeedExportPluginTestCase.php';

use Cms\Classes\Controller;
use Cms\Classes\Page as CmsPage;
use Illuminate\Support\Facades\URL;
use Logingrupa\FacebookCatalogShopaholic\Classes\Helper\FeedSiteContext;

/**
 * Product feeds (Salidzini, KurPirkt, Facebook, Yandex) must build links under the
 * shop's canonical site context: prefixed on .lv (/lv/p/...) and .lt (/lt/p/...),
 * unprefixed without double slashes on .no (/p/...), and backend widget exports must
 * run under the shop site's Multisite context and restore the admin's site afterwards.
 */
class FeedSiteContextTest extends FeedExportPluginTestCase
{
    /** Core schema comes from createApplication(), no catalog tables needed */
    protected $autoMigrate = false;

    const APP_URL = 'https://nailscosmetics.test';

    public function setUp(): void
    {
        parent::setUp();

        Config::set('app.url', self::APP_URL);
        URL::forceRootUrl(self::APP_URL);
        URL::forceScheme('https');
    }

    public function tearDown(): void
    {
        FeedSiteContext::setCmsUrlPrefix(null);
        (new ReflectionProperty(Controller::class, 'instance'))->setValue(null, null);

        parent::tearDown();
    }

    public function testPrefixedShopAppliesRoutePrefixToCmsAndProductPageUrls()
    {
        $this->defineSites([
            ['lv', true, true, '/lv'],
            ['en', false, true, '/en'],
            ['ru', false, true, '/ru'],
        ]);

        $obSite = FeedSiteContext::apply();

        $this->assertSame('lv', $obSite->locale);
        $this->assertSame('lv', App::getLocale());
        $this->assertSame('lv', FeedSiteContext::getCmsUrlPrefix());
        $this->assertSame(self::APP_URL . '/lv/p/acetona-zimulis/1040', Cms::url('/p/acetona-zimulis/1040'));
        $this->assertSame(
            self::APP_URL . '/lv/p/acetona-zimulis/1040',
            CmsPage::url('product', ['slug' => 'acetona-zimulis', 'offer' => 1040])
        );
        $this->assertSame(
            self::APP_URL . '/lv/p/acetona-zimulis',
            CmsPage::url('product', ['slug' => 'acetona-zimulis'])
        );
    }

    public function testUnprefixedShopClearsPrefixAndNeverDoublesSlashes()
    {
        // .no shape: primary lv is disabled; enabled nb-no carries route_prefix "/"
        $this->defineSites([
            ['lv', true, false, '/lv'],
            ['nb-no', false, true, '/', true],
        ]);

        // Seed a stale prefix from an earlier site context to prove apply() clears it
        Cms::setUrlPrefix('/lv');

        $obSite = FeedSiteContext::apply();

        $this->assertSame('nb-no', $obSite->locale);
        $this->assertSame('nb-no', App::getLocale());
        $this->assertNull(FeedSiteContext::getCmsUrlPrefix());
        $this->assertSame(self::APP_URL . '/p/aceton-penn', Cms::url('/p/aceton-penn'));
        $this->assertSame(
            self::APP_URL . '/p/aceton-penn',
            CmsPage::url('product', ['slug' => 'aceton-penn'])
        );
    }

    public function testIsPrefixedFalseIgnoresStaleRoutePrefixValue()
    {
        $this->defineSites([
            ['nb-no', true, true, '/stale', false],
        ]);

        Cms::setUrlPrefix('/stale');

        FeedSiteContext::apply();

        $this->assertNull(FeedSiteContext::getCmsUrlPrefix());
        $this->assertSame(self::APP_URL . '/p/aceton-penn', Cms::url('/p/aceton-penn'));
    }

    public function testRunInShopSiteUsesShopMultisiteContextAndRestoresPreviousState()
    {
        $arSiteIds = $this->defineSites([
            ['lv', true, true, '/lv'],
            ['ru', false, true, '/ru'],
        ]);

        // Simulate admin editing Russian site in the backend (edit site != shop site)
        Site::setEditSite(Site::getSiteFromId($arSiteIds['ru']));
        Site::applyActiveSiteId($arSiteIds['ru']);
        App::setLocale('ru');
        Cms::setUrlPrefix('/ru');

        $arCaptured = FeedSiteContext::runInShopSite(function () {
            return [
                'locale' => App::getLocale(),
                'context_site_id' => Site::getSiteIdFromContext(),
                'url' => CmsPage::url('product', ['slug' => 'acetona-zimulis', 'offer' => 1040]),
            ];
        });

        $this->assertSame('lv', $arCaptured['locale']);
        $this->assertSame($arSiteIds['lv'], $arCaptured['context_site_id']);
        $this->assertSame(self::APP_URL . '/lv/p/acetona-zimulis/1040', $arCaptured['url']);

        // Previous state restored after normal return
        $this->assertSame($arSiteIds['ru'], Site::getActiveSiteId());
        $this->assertSame($arSiteIds['ru'], Site::getSiteIdFromContext());
        $this->assertSame('ru', App::getLocale());
        $this->assertSame('ru', FeedSiteContext::getCmsUrlPrefix());

        // Exception path must still restore both active site and Multisite context
        $obCaught = null;
        try {
            FeedSiteContext::runInShopSite(function () {
                throw new \RuntimeException('simulated export failure');
            });
        } catch (\RuntimeException $obException) {
            $obCaught = $obException;
        }

        $this->assertNotNull($obCaught);
        $this->assertSame($arSiteIds['ru'], Site::getActiveSiteId());
        $this->assertSame($arSiteIds['ru'], Site::getSiteIdFromContext());
        $this->assertSame('ru', App::getLocale());
        $this->assertSame('ru', FeedSiteContext::getCmsUrlPrefix());
    }
}

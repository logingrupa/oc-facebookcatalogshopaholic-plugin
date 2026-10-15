<?php

/**
 * Hermetic base test case: SQLite :memory: with the core module schema built before
 * PluginTestCase::setUp() reaches Mail::pretend(), whose MailSetting read queries
 * system_settings whenever the shared dev manifest claims the database exists.
 */
abstract class FeedExportPluginTestCase extends PluginTestCase
{
    /** @var bool set when defineSites() replaced the site definitions */
    protected $bSitesDefined = false;

    /**
     * @return \Symfony\Component\HttpKernel\HttpKernelInterface
     */
    public function createApplication()
    {
        $app = parent::createApplication();

        $this->pinHermeticSqliteConfig($app);
        $this->migrateCoreModulesEarly($app);

        return $app;
    }

    /**
     * pinHermeticSqliteConfig re-pins SQLite :memory: after kernel bootstrap and drops the
     * site definitions cached from the shared disk manifest for this app instance.
     * @param \Illuminate\Contracts\Foundation\Application $app
     * @return void
     */
    protected function pinHermeticSqliteConfig($app)
    {
        config([
            'app.env' => 'testing',
            'database.default' => 'sqlite',
            'database.connections.sqlite' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
                'foreign_key_constraints' => false,
            ],
            'cache.default' => 'array',
            'session.driver' => 'array',
        ]);

        $app['db']->purge();

        $app['system.sites']->resetCache();
    }

    /**
     * migrateCoreModulesEarly runs the module migrations before PluginTestCase::setUp()
     * resolves the mailer. $this->app is assigned because refreshApplication() has not
     * returned yet and the migration trait reads it.
     * @param \Illuminate\Contracts\Foundation\Application $app
     * @return void
     */
    protected function migrateCoreModulesEarly($app)
    {
        $this->app = $app;

        $this->migrateModules();
    }

    /**
     * defineSites replaces the site definitions the core migrations seed.
     * @param array $arSiteList list of [locale, is_primary, is_enabled]
     * @return array site id per locale
     */
    protected function defineSites($arSiteList)
    {
        Db::table('system_site_definitions')->delete();

        $arSiteIdList = [];
        foreach (array_values($arSiteList) as $iIndex => [$sLocale, $bIsPrimary, $bIsEnabled]) {
            $iSiteId = $iIndex + 1;
            Db::table('system_site_definitions')->insert([
                'id' => $iSiteId,
                'name' => $sLocale,
                'code' => $sLocale,
                'sort_order' => $iSiteId,
                'locale' => $sLocale,
                'is_primary' => $bIsPrimary,
                'is_enabled' => $bIsEnabled,
                'is_enabled_edit' => $bIsEnabled,
            ]);

            $arSiteIdList[$sLocale] = $iSiteId;
        }

        $this->bSitesDefined = true;

        $this->forgetSiteState();

        return $arSiteIdList;
    }

    /**
     * The site state lives in static singletons that outlive the application instance, so
     * it is dropped after the parent tears the application down.
     * @return void
     */
    public function tearDown(): void
    {
        parent::tearDown();

        if ($this->bSitesDefined) {
            $this->forgetSiteState();
        }
    }

    /**
     * forgetSiteState clears the site list, the RainLab locale list derived from it and the
     * translator holding the active locale.
     * @return void
     */
    protected function forgetSiteState()
    {
        Site::resetCache();

        if (!class_exists(\RainLab\Translate\Classes\Locale::class)) {
            return;
        }

        \RainLab\Translate\Classes\Locale::clearCache();
        \RainLab\Translate\Classes\Translator::forgetInstance();
    }
}

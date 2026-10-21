<?php

namespace Webkul\FSBO\Providers;

use Illuminate\Support\ServiceProvider;

class FSBOServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        $this->loadRoutesFrom(__DIR__.'/../Routes/web.php');

        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'fsbo');
    }

    /**
     * Register services.
     */
    public function register(): void
    {
        $this->registerConfig();
    }

    /**
     * Register package configuration.
     */
    protected function registerConfig(): void
    {
        $this->mergeConfigFrom(dirname(__DIR__).'/Config/menu.php', 'menu.admin');

        $this->mergeConfigFrom(dirname(__DIR__).'/Config/acl.php', 'acl');
    }
}

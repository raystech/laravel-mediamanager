<?php

namespace raystech\medialibrary;

use Illuminate\Support\ServiceProvider;

class medialibraryServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        // $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'raystech');
        // $this->loadViewsFrom(__DIR__.'/../resources/views', 'raystech');
        // $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        // $this->loadRoutesFrom(__DIR__.'/routes.php');

        // Publishing is only necessary when using the CLI.
        if ($this->app->runningInConsole()) {

            // Publishing the configuration file.
            $this->publishes([
                __DIR__.'/../config/medialibrary.php' => config_path('medialibrary.php'),
            ], 'medialibrary.config');

            // Publishing the views.
            /*$this->publishes([
                __DIR__.'/../resources/views' => base_path('resources/views/vendor/raystech'),
            ], 'medialibrary.views');*/

            // Publishing assets.
            /*$this->publishes([
                __DIR__.'/../resources/assets' => public_path('vendor/raystech'),
            ], 'medialibrary.views');*/

            // Publishing the translation files.
            /*$this->publishes([
                __DIR__.'/../resources/lang' => resource_path('lang/vendor/raystech'),
            ], 'medialibrary.views');*/

            // Registering package commands.
            // $this->commands([]);
        }
    }

    /**
     * Register any package services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/medialibrary.php', 'medialibrary');

        // Register the service the package provides.
        $this->app->singleton('medialibrary', function ($app) {
            return new medialibrary;
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['medialibrary'];
    }
}
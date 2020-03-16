<?php

namespace Zanichelli\IdpExtension\Providers;

class IdpProvider implements ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        Auth::extend('idp', function ($app, $name, array $config) {
            return tap($this->makeGuard($config), function ($guard) {
                $this->app->refresh('request', $guard, 'setRequest');
            });
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../../database/migrations' => database_path('/migrations'),
        ], 'database');
    }

    /**
     * Register Idp's migration files.
     *
     * @return void
     */
    protected function registerMigrations()
    {
        if (IdpExtension::$runsMigrations) {
            return $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        }
    }
}

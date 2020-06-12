<?php

namespace Zanichelli\IdpExtension\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Session;

class IdpProvider extends ServiceProvider
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
        Session::extend('idp-token', function ($app) {
            $connection = $app['config']['session.connection'];
            return new SessionWithTokenHandler(
                $app['db']->connection($connection),
                $app['config']['session.table'],
                $app['config']['session.lifetime'],
                $app
            );
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
            __DIR__ . '/../database/migrations/' => database_path('migrations'),
        ], 'migrations');
        $this->publishes([
            __DIR__ . '/../config/idp-extension.php' => config_path('idp-extension.php'),
        ], 'config');

        Session::extend('idp-token', function ($app) {
            $connection = $app['config']['session.connection'];
            return new SessionWithTokenHandler(
                $app['db']->connection($connection),
                $app['config']['session.table'],
                $app['config']['session.lifetime'],
                $app
            );
        });
    }
}

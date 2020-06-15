<?php

namespace Zanichelli\IdpExtension\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;
use Zanichelli\IdpExtension\Guards\ZGuard;

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

        Auth::provider('z-provider', function ($app, array $config) {
            return new ZAuthServiceProvider();
        });

        Auth::extend('z-session', function ($app, $name, array $config) {
            return ZGuard::create($this->app['session.store'], Auth::createUserProvider($config['provider']));
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

        $this->app['router']->namespace('Zanichelli\IdpExtension\Http\Controllers')
            ->middleware(['api'])
            ->group(function () {
                $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');
            });

        $this->publishes([
            __DIR__ . '/../routes/api.php' => base_path('routes/idp-api.php'),
        ], 'routes');

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

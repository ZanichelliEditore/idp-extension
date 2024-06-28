<?php

namespace Zanichelli\IdpExtension\Providers;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\ServiceProvider;
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
            __DIR__ . '/../database/migrations/2022_03_01_145900_change_grants_table_columns.php' => database_path('migrations/2022_03_01_145900_change_grants_table_columns.php'),
            __DIR__ . '/../database/migrations/2022_03_15_111939_alter_grants_table_new_columns.php' => database_path('migrations/2022_03_15_111939_alter_grants_table_new_columns.php'),
        ], 'grants-by-name-instead-of-id');

        $this->app['router']->namespace('Zanichelli\IdpExtension\Http\Controllers')
            ->middleware(['api'])
            ->group(function () {
                $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');
            });

        $this->publishes([
            __DIR__ . '/../routes/api.php' => base_path('routes/idp-api.php'),
        ], 'routes');

        $this->mergeConfigFrom(
            __DIR__ . '/../config/auth.php',
            'auth'
        );

        $this->publishes([
            __DIR__ . '/../config/idp.php' => config_path('idp.php')
        ], 'config');
    }

    /**
     * Merge the given configuration with the existing configuration.
     *
     * @param  string  $path
     * @param  string  $key
     * @return void
     */
    protected function mergeConfigFrom($path, $key)
    {
        $config = $this->app['config']->get($key, []);

        $this->app['config']->set($key, $this->mergeConfig(require $path, $config));
    }

    /**
     * Merges the configs together and takes multi-dimensional arrays into account.
     *
     * @param  array  $original
     * @param  array  $merging
     * @return array
     */
    protected function mergeConfig(array $original, array $merging)
    {
        $array = array_merge($original, $merging);

        foreach ($original as $key => $value) {
            if (!is_array($value)) {
                continue;
            }

            if (!Arr::exists($merging, $key)) {
                continue;
            }

            if (is_numeric($key)) {
                continue;
            }

            $array[$key] = $this->mergeConfig($value, $merging[$key]);
        }

        return $array;
    }
}

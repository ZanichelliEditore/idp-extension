<?php

namespace Zanichelli\IdpExtension\Tests;

use Orchestra\Testbench\TestCase as BaseTestCase;
use Zanichelli\IdpExtension\Providers\IdpProvider;

abstract class TestCase extends BaseTestCase
{
    /**
     * The base url of the fake identity provider used by the tests.
     */
    public const IDP_BASE_URL = 'https://idp.test';

    protected function getPackageProviders($app)
    {
        return [IdpProvider::class];
    }

    protected function defineEnvironment($app)
    {
        $_ENV['IDP_BASE_URL'] = $_SERVER['IDP_BASE_URL'] = self::IDP_BASE_URL;

        $app['config']->set('app.key', 'base64:' . base64_encode(random_bytes(32)));

        // The file driver keeps the suite runnable without a database. Session
        // identifier rotation and record destruction are handler independent:
        // SessionWithTokenHandler inherits destroy() from DatabaseSessionHandler
        // without overriding it.
        $app['config']->set('session.driver', 'file');

        $app['config']->set('auth.defaults.guard', 'z-session');
    }
}

<?php

namespace Berkayk\OneSignal\Tests;

use Berkayk\OneSignal\OneSignalServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            OneSignalServiceProvider::class,
        ];
    }

    protected function getPackageAliases($app): array
    {
        return [
            'OneSignal' => \Berkayk\OneSignal\OneSignalFacade::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('onesignal.app_id', 'test-app-id');
        $app['config']->set('onesignal.rest_api_key', 'test-rest-api-key');
        $app['config']->set('onesignal.organization_api_key', 'test-org-api-key');
    }
}

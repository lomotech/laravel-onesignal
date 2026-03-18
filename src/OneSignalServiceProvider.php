<?php

namespace Berkayk\OneSignal;

use Illuminate\Support\ServiceProvider;

class OneSignalServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $configPath = __DIR__ . '/../config/onesignal.php';

        $this->publishes([$configPath => config_path('onesignal.php')], 'config');
        $this->mergeConfigFrom($configPath, 'onesignal');
    }

    public function register(): void
    {
        $this->app->singleton('onesignal', function ($app) {
            $config = $app['config']['services.onesignal'] ?? $app['config']['onesignal'];

            return new OneSignal(
                appId: $config['app_id'] ?? '',
                restApiKey: $config['rest_api_key'] ?? '',
                organizationApiKey: $config['organization_api_key'] ?? $config['user_auth_key'] ?? null,
                timeout: (int) ($config['guzzle_client_timeout'] ?? 30),
                restApiUrl: $config['rest_api_url'] ?? null,
                maxRetries: (int) ($config['max_retries'] ?? 2),
                retryDelay: (int) ($config['retry_delay'] ?? 500),
            );
        });

        $this->app->alias('onesignal', OneSignal::class);
    }

    public function provides(): array
    {
        return ['onesignal'];
    }
}

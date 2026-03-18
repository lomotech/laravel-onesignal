<?php

namespace Berkayk\OneSignal\Api;

use Berkayk\OneSignal\Enums\AuthType;
use Berkayk\OneSignal\Http\HttpClient;

class AppApi
{
    public function __construct(
        protected HttpClient $http,
        protected string $appId,
    ) {}

    public function list(): array
    {
        return $this->http->get('/apps', AuthType::Bearer);
    }

    public function get(?string $appId = null): array
    {
        $appId ??= $this->appId;

        return $this->http->get("/apps/{$appId}", AuthType::Bearer);
    }

    public function create(array $parameters): array
    {
        return $this->http->post('/apps', $parameters, AuthType::Bearer);
    }

    public function update(array $parameters, ?string $appId = null): array
    {
        $appId ??= $this->appId;

        return $this->http->put("/apps/{$appId}", $parameters, AuthType::Bearer);
    }
}

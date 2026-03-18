<?php

namespace Berkayk\OneSignal\Api;

use Berkayk\OneSignal\Http\HttpClient;

class TemplateApi
{
    public function __construct(
        protected HttpClient $http,
        protected string $appId,
    ) {}

    public function list(): array
    {
        return $this->http->get("/templates?app_id={$this->appId}");
    }

    public function get(string $templateId): array
    {
        return $this->http->get("/templates/{$templateId}?app_id={$this->appId}");
    }

    public function create(array $parameters): array
    {
        $parameters['app_id'] ??= $this->appId;

        return $this->http->post('/templates', $parameters);
    }

    public function update(string $templateId, array $parameters): array
    {
        $parameters['app_id'] ??= $this->appId;

        return $this->http->put("/templates/{$templateId}", $parameters);
    }
}

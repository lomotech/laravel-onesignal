<?php

namespace Berkayk\OneSignal\Api;

use Berkayk\OneSignal\Http\HttpClient;

class UserApi
{
    public function __construct(
        protected HttpClient $http,
        protected string $appId,
    ) {}

    public function create(array $properties = [], array $subscriptions = [], ?string $identity = null, ?string $identityValue = null): array
    {
        $data = ['properties' => $properties];

        if (! empty($subscriptions)) {
            $data['subscriptions'] = $subscriptions;
        }

        if ($identity !== null && $identityValue !== null) {
            $data['identity'] = [$identity => $identityValue];
        }

        return $this->http->post("/apps/{$this->appId}/users", $data);
    }

    public function get(string $aliasLabel, string $aliasId): array
    {
        return $this->http->get("/apps/{$this->appId}/users/by/{$aliasLabel}/{$aliasId}");
    }

    public function update(string $aliasLabel, string $aliasId, array $properties): array
    {
        return $this->http->patch("/apps/{$this->appId}/users/by/{$aliasLabel}/{$aliasId}", [
            'properties' => $properties,
        ]);
    }

    public function delete(string $aliasLabel, string $aliasId): array
    {
        return $this->http->delete("/apps/{$this->appId}/users/by/{$aliasLabel}/{$aliasId}");
    }

    public function addAliases(string $aliasLabel, string $aliasId, array $aliases): array
    {
        return $this->http->patch("/apps/{$this->appId}/users/by/{$aliasLabel}/{$aliasId}/identity", [
            'identity' => $aliases,
        ]);
    }

    public function removeAlias(string $aliasLabel, string $aliasId, string $aliasToRemove): array
    {
        return $this->http->delete("/apps/{$this->appId}/users/by/{$aliasLabel}/{$aliasId}/identity/{$aliasToRemove}");
    }
}

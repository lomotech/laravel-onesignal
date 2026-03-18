<?php

namespace Berkayk\OneSignal\Api;

use Berkayk\OneSignal\Http\HttpClient;

class LiveActivityApi
{
    public function __construct(
        protected HttpClient $http,
        protected string $appId,
    ) {}

    public function begin(string $activityId, array $parameters): array
    {
        $parameters['app_id'] ??= $this->appId;

        return $this->http->post("/apps/{$this->appId}/live_activities/{$activityId}/token", $parameters);
    }

    public function update(string $activityId, array $parameters): array
    {
        $parameters['app_id'] ??= $this->appId;

        return $this->http->post("/apps/{$this->appId}/live_activities/{$activityId}/notifications", $parameters);
    }

    public function end(string $activityId, string $subscriptionId): array
    {
        return $this->http->delete("/apps/{$this->appId}/live_activities/{$activityId}/token/{$subscriptionId}");
    }
}

<?php

namespace Berkayk\OneSignal\Api;

use Berkayk\OneSignal\Http\HttpClient;

class SegmentApi
{
    public function __construct(
        protected HttpClient $http,
        protected string $appId,
    ) {}

    public function list(): array
    {
        return $this->http->get("/apps/{$this->appId}/segments");
    }

    public function create(string $name, array $filters): array
    {
        return $this->http->post("/apps/{$this->appId}/segments", [
            'name' => $name,
            'filters' => $filters,
        ]);
    }

    public function delete(string $segmentId): array
    {
        return $this->http->delete("/apps/{$this->appId}/segments/{$segmentId}");
    }
}

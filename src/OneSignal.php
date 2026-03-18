<?php

namespace Berkayk\OneSignal;

use Berkayk\OneSignal\Api\AppApi;
use Berkayk\OneSignal\Api\LiveActivityApi;
use Berkayk\OneSignal\Api\NotificationApi;
use Berkayk\OneSignal\Api\SegmentApi;
use Berkayk\OneSignal\Api\SubscriptionApi;
use Berkayk\OneSignal\Api\TemplateApi;
use Berkayk\OneSignal\Api\UserApi;
use Berkayk\OneSignal\Http\HttpClient;
use GuzzleHttp\Promise\PromiseInterface;

class OneSignal
{
    protected HttpClient $http;

    protected bool $requestAsync = false;

    protected ?\Closure $requestCallback = null;

    protected array $additionalParams = [];

    private ?NotificationApi $notificationApi = null;
    private ?UserApi $userApi = null;
    private ?SubscriptionApi $subscriptionApi = null;
    private ?SegmentApi $segmentApi = null;
    private ?TemplateApi $templateApi = null;
    private ?AppApi $appApi = null;
    private ?LiveActivityApi $liveActivityApi = null;

    public function __construct(
        protected string $appId,
        protected string $restApiKey,
        protected ?string $organizationApiKey = null,
        int $timeout = 30,
        ?string $restApiUrl = null,
        int $maxRetries = 2,
        int $retryDelay = 500,
        ?HttpClient $httpClient = null,
    ) {
        $this->http = $httpClient ?? new HttpClient(
            baseUrl: $restApiUrl ?? 'https://api.onesignal.com',
            apiKey: $restApiKey,
            organizationApiKey: $organizationApiKey,
            timeout: $timeout,
            maxRetries: $maxRetries,
            retryDelay: $retryDelay,
        );
    }

    // --- Sub-API accessors ---

    public function notifications(): NotificationApi
    {
        return $this->notificationApi ??= new NotificationApi($this->http, $this->appId);
    }

    public function users(): UserApi
    {
        return $this->userApi ??= new UserApi($this->http, $this->appId);
    }

    public function subscriptions(): SubscriptionApi
    {
        return $this->subscriptionApi ??= new SubscriptionApi($this->http, $this->appId);
    }

    public function segments(): SegmentApi
    {
        return $this->segmentApi ??= new SegmentApi($this->http, $this->appId);
    }

    public function templates(): TemplateApi
    {
        return $this->templateApi ??= new TemplateApi($this->http, $this->appId);
    }

    public function apps(): AppApi
    {
        return $this->appApi ??= new AppApi($this->http, $this->appId);
    }

    public function liveActivities(): LiveActivityApi
    {
        return $this->liveActivityApi ??= new LiveActivityApi($this->http, $this->appId);
    }

    // --- Fluent helpers ---

    public function async(bool $on = true): static
    {
        $this->requestAsync = $on;

        return $this;
    }

    public function callback(callable $callback): static
    {
        $this->requestCallback = $callback(...);

        return $this;
    }

    public function setParam(string $key, mixed $value): static
    {
        $this->additionalParams[$key] = $value;

        return $this;
    }

    public function addParams(array $params = []): static
    {
        $this->additionalParams = array_merge($this->additionalParams, $params);

        return $this;
    }

    // --- Convenience shortcuts ---

    public function sendNotificationToAll(string $message, ?string $url = null, ?array $data = null, ?array $buttons = null, ?string $schedule = null, ?string $headings = null, ?string $subtitle = null): array|PromiseInterface
    {
        $extra = $this->buildNotificationExtras($url, $data, $buttons, $schedule, $headings, $subtitle);

        return $this->notifications()->sendToAll($message, $extra, $this->requestAsync, $this->requestCallback);
    }

    public function sendNotificationToSegment(string $message, string $segment, ?string $url = null, ?array $data = null, ?array $buttons = null, ?string $schedule = null, ?string $headings = null, ?string $subtitle = null): array|PromiseInterface
    {
        $extra = $this->buildNotificationExtras($url, $data, $buttons, $schedule, $headings, $subtitle);

        return $this->notifications()->sendToSegment($message, $segment, $extra, $this->requestAsync, $this->requestCallback);
    }

    public function sendNotificationCustom(array $parameters = []): array|PromiseInterface
    {
        $parameters = array_merge($parameters, $this->additionalParams);

        if (empty($parameters['included_segments']) && empty($parameters['include_subscription_ids']) && empty($parameters['include_aliases']) && empty($parameters['filters'])) {
            $parameters['included_segments'] = ['All'];
        }

        return $this->notifications()->send($parameters, $this->requestAsync, $this->requestCallback);
    }

    // --- Deprecated legacy methods ---

    /** @deprecated Use notifications()->sendToSubscriptionIds() instead */
    public function sendNotificationToUser(string $message, string|array $userId, ?string $url = null, ?array $data = null, ?array $buttons = null, ?string $schedule = null, ?string $headings = null, ?string $subtitle = null): array|PromiseInterface
    {
        trigger_error('sendNotificationToUser() is deprecated. Use notifications()->sendToSubscriptionIds() instead.', E_USER_DEPRECATED);

        $extra = $this->buildNotificationExtras($url, $data, $buttons, $schedule, $headings, $subtitle);
        $extra['include_subscription_ids'] = is_array($userId) ? $userId : [$userId];

        return $this->notifications()->send(array_merge([
            'contents' => ['en' => $message],
        ], $extra), $this->requestAsync, $this->requestCallback);
    }

    /** @deprecated Use notifications()->sendToAliases() instead */
    public function sendNotificationToExternalUser(string $message, string|array $userId, ?string $url = null, ?array $data = null, ?array $buttons = null, ?string $schedule = null, ?string $headings = null, ?string $subtitle = null): array|PromiseInterface
    {
        trigger_error('sendNotificationToExternalUser() is deprecated. Use notifications()->sendToAliases() instead.', E_USER_DEPRECATED);

        $extra = $this->buildNotificationExtras($url, $data, $buttons, $schedule, $headings, $subtitle);
        $extra['include_aliases'] = ['external_id' => is_array($userId) ? $userId : [$userId]];

        return $this->notifications()->send(array_merge([
            'contents' => ['en' => $message],
        ], $extra), $this->requestAsync, $this->requestCallback);
    }

    /** @deprecated Players API has been removed by OneSignal. Use users()->create() instead. */
    public function createPlayer(array $parameters): array|PromiseInterface
    {
        trigger_error('createPlayer() is deprecated. The Players API has been removed by OneSignal. Use users()->create() instead.', E_USER_DEPRECATED);

        return $this->notifications()->send($parameters, $this->requestAsync, $this->requestCallback);
    }

    /** @deprecated Players API has been removed by OneSignal. Use users()->update() instead. */
    public function editPlayer(array $parameters): array|PromiseInterface
    {
        trigger_error('editPlayer() is deprecated. The Players API has been removed by OneSignal. Use users()->update() instead.', E_USER_DEPRECATED);

        return $this->notifications()->send($parameters, $this->requestAsync, $this->requestCallback);
    }

    // --- Notification helpers ---

    public function getNotification(string $notificationId): array
    {
        return $this->notifications()->get($notificationId);
    }

    public function getNotifications(?int $limit = null, ?int $offset = null): array
    {
        return $this->notifications()->list($limit, $offset);
    }

    public function deleteNotification(string $notificationId): array|PromiseInterface
    {
        return $this->notifications()->cancel($notificationId);
    }

    // --- App helpers ---

    public function getApp(?string $appId = null): array
    {
        return $this->apps()->get($appId);
    }

    public function getApps(): array
    {
        return $this->apps()->list();
    }

    // --- Internal ---

    protected function buildNotificationExtras(?string $url, ?array $data, ?array $buttons, ?string $schedule, ?string $headings, ?string $subtitle): array
    {
        $extra = [];

        if ($url !== null) {
            $extra['url'] = $url;
        }

        if ($data !== null) {
            $extra['data'] = $data;
        }

        if ($buttons !== null) {
            $extra['buttons'] = $buttons;
        }

        if ($schedule !== null) {
            $extra['send_after'] = $schedule;
        }

        if ($headings !== null) {
            $extra['headings'] = ['en' => $headings];
        }

        if ($subtitle !== null) {
            $extra['subtitle'] = ['en' => $subtitle];
        }

        return array_merge($extra, $this->additionalParams);
    }
}

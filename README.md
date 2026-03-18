# OneSignal Push Notifications for Laravel

[![Latest Stable Version](https://poser.pugx.org/berkayk/onesignal-laravel/v/stable)](https://packagist.org/packages/berkayk/onesignal-laravel)
[![Total Downloads](https://poser.pugx.org/berkayk/onesignal-laravel/downloads)](https://packagist.org/packages/berkayk/onesignal-laravel)
[![License](https://poser.pugx.org/berkayk/onesignal-laravel/license)](https://packagist.org/packages/berkayk/onesignal-laravel)

## Introduction

A Laravel wrapper for the OneSignal User Model API. Supports push notifications, users, subscriptions, segments, templates, apps, and live activities.

**Requirements:** PHP 8.3+, Laravel 11/12/13

## Installation

```sh
composer require berkayk/onesignal-laravel
```

Publish the configuration file:

```sh
php artisan vendor:publish --provider="Berkayk\OneSignal\OneSignalServiceProvider" --tag="config"
```

## Configuration

Add your OneSignal credentials to `.env`:

```
ONESIGNAL_APP_ID=your-app-id
ONESIGNAL_REST_API_KEY=your-rest-api-key
ONESIGNAL_ORGANIZATION_API_KEY=your-organization-api-key
```

Optional settings:

```
ONESIGNAL_GUZZLE_CLIENT_TIMEOUT=30
ONESIGNAL_MAX_RETRIES=2
ONESIGNAL_RETRY_DELAY=500
```

## Quick Start

```php
use OneSignal;

// Send to all users
OneSignal::sendNotificationToAll('Hello everyone!');

// Send to a segment
OneSignal::sendNotificationToSegment('Hello!', 'Active Users');

// Send a custom notification
OneSignal::sendNotificationCustom([
    'contents' => ['en' => 'Hello!'],
    'included_segments' => ['All'],
]);
```

## API Reference

### Notifications

```php
// Send to all
OneSignal::notifications()->sendToAll('Hello!');

// Send to segment(s)
OneSignal::notifications()->sendToSegment('Hello!', 'Active Users');
OneSignal::notifications()->sendToSegment('Hello!', ['Active Users', 'VIP']);

// Send to specific users by alias
OneSignal::notifications()->sendToAliases('Hello!', [
    'external_id' => ['user-1', 'user-2'],
]);

// Send to subscription IDs
OneSignal::notifications()->sendToSubscriptionIds('Hello!', ['sub-id-1', 'sub-id-2']);

// Send with filters
OneSignal::notifications()->sendWithFilters('Hello!', [
    ['field' => 'tag', 'key' => 'level', 'relation' => '>', 'value' => '10'],
]);

// Send with extra parameters
OneSignal::notifications()->sendToAll('Hello!', [
    'url' => 'https://example.com',
    'data' => ['key' => 'value'],
    'headings' => ['en' => 'Important'],
]);

// Full custom notification
OneSignal::notifications()->send([
    'contents' => ['en' => 'Hello!'],
    'included_segments' => ['All'],
    'priority' => 10,
    'android_accent_color' => 'FFCCAA72',
]);

// Get / list / cancel
$notification = OneSignal::notifications()->get('notification-id');
$list = OneSignal::notifications()->list(limit: 50, offset: 0);
OneSignal::notifications()->cancel('notification-id');
```

### Users

```php
// Create a user
OneSignal::users()->create(
    properties: ['tags' => ['level' => '5']],
    subscriptions: [['type' => 'Email', 'token' => 'user@example.com']],
    identity: 'external_id',
    identityValue: 'user-123',
);

// Get a user by alias
$user = OneSignal::users()->get('external_id', 'user-123');

// Update user properties
OneSignal::users()->update('external_id', 'user-123', [
    'tags' => ['level' => '10'],
]);

// Delete a user
OneSignal::users()->delete('external_id', 'user-123');

// Manage aliases
OneSignal::users()->addAliases('external_id', 'user-123', ['my_alias' => 'value']);
OneSignal::users()->removeAlias('external_id', 'user-123', 'my_alias');
```

### Subscriptions

```php
// Create a subscription
OneSignal::subscriptions()->create('external_id', 'user-123', [
    'type' => 'iOSPush',
    'token' => 'push-token-here',
]);

// Update a subscription
OneSignal::subscriptions()->update('subscription-id', ['enabled' => false]);

// Delete a subscription
OneSignal::subscriptions()->delete('subscription-id');

// Check token validity
$result = OneSignal::subscriptions()->viewByToken('iOSPush', $token);

// Transfer subscription to another user
OneSignal::subscriptions()->transfer('subscription-id', 'external_id', 'new-user-id');
```

### Segments

```php
$segments = OneSignal::segments()->list();

OneSignal::segments()->create('VIP Users', [
    ['field' => 'tag', 'key' => 'vip', 'value' => 'true'],
]);

OneSignal::segments()->delete('segment-id');
```

### Templates

```php
$templates = OneSignal::templates()->list();
$template = OneSignal::templates()->get('template-id');

OneSignal::templates()->create([
    'name' => 'Welcome',
    'contents' => ['en' => 'Welcome to our app!'],
]);

OneSignal::templates()->update('template-id', [
    'contents' => ['en' => 'Updated welcome message'],
]);
```

### Apps

Requires `ONESIGNAL_ORGANIZATION_API_KEY` (uses Bearer auth).

```php
$apps = OneSignal::apps()->list();
$app = OneSignal::apps()->get('app-id');

OneSignal::apps()->create(['name' => 'My New App']);
OneSignal::apps()->update(['name' => 'Updated Name']);
```

### Live Activities

```php
OneSignal::liveActivities()->begin('activity-id', [
    'push_token' => 'token',
    'subscription_id' => 'sub-id',
]);

OneSignal::liveActivities()->update('activity-id', [
    'event' => 'update',
    'event_updates' => ['score' => '3-2'],
]);

OneSignal::liveActivities()->end('activity-id', 'subscription-id');
```

## Advanced Usage

### Async Requests

```php
// Fluent async
$promise = OneSignal::async()->sendNotificationToAll('Hello!');
$result = $promise->wait();

// With callback
OneSignal::async()->callback(function ($result) {
    logger('Notification sent', $result);
})->sendNotificationToAll('Hello!');

// API-level async
$promise = OneSignal::notifications()->sendToAll('Hello!', async: true);
```

### Custom Parameters

```php
// Chain parameters
OneSignal::setParam('priority', 10)
    ->setParam('small_icon', 'ic_stat_default')
    ->sendNotificationToAll('Important!');

// Batch parameters
OneSignal::addParams([
    'android_accent_color' => 'FFCCAA72',
    'small_icon' => 'ic_stat_default',
])->sendNotificationToSegment('Hello!', 'Testers');
```

### Rate Limiting

429 responses automatically throw `RateLimitException` with the `Retry-After` value:

```php
use Berkayk\OneSignal\Exceptions\RateLimitException;

try {
    OneSignal::sendNotificationToAll('Hello!');
} catch (RateLimitException $e) {
    $retryAfter = $e->retryAfter; // seconds to wait
}
```

### Error Handling

```php
use Berkayk\OneSignal\Exceptions\AuthenticationException;
use Berkayk\OneSignal\Exceptions\NotFoundException;
use Berkayk\OneSignal\Exceptions\ValidationException;
use Berkayk\OneSignal\Exceptions\OneSignalException;

try {
    OneSignal::notifications()->get('invalid-id');
} catch (NotFoundException $e) {
    // Notification not found
} catch (AuthenticationException $e) {
    // Invalid API key
} catch (OneSignalException $e) {
    // Other API errors
    $errors = $e->errors; // array of error messages from OneSignal
}
```

## Migration from v2.x

### Breaking Changes

- **PHP 8.3+** required (was PHP 5.4+)
- **Laravel 11+** required (was Laravel 5.5+)
- Lumen support dropped
- `OneSignalClient` renamed to `OneSignal`
- Default timeout changed from 0 (infinite) to 30 seconds

### Deprecated Methods

These methods still work but trigger `E_USER_DEPRECATED` warnings:

| v2.x Method | v3.x Replacement |
|---|---|
| `sendNotificationToUser()` | `notifications()->sendToSubscriptionIds()` |
| `sendNotificationToExternalUser()` | `notifications()->sendToAliases()` |
| `createPlayer()` | `users()->create()` |
| `editPlayer()` | `users()->update()` |

### Config Changes

Add these new keys to your `config/onesignal.php`:

```php
'organization_api_key' => env('ONESIGNAL_ORGANIZATION_API_KEY'),
'max_retries' => env('ONESIGNAL_MAX_RETRIES', 2),
'retry_delay' => env('ONESIGNAL_RETRY_DELAY', 500),
```

The `user_auth_key` config is kept as a fallback for `organization_api_key`.

### Convenience Methods

`sendNotificationToAll()`, `sendNotificationToSegment()`, and `sendNotificationCustom()` continue to work as before with the same signatures.

## Testing

```sh
composer test
```

## License

MIT

<?php

use Berkayk\OneSignal\OneSignal;
use Berkayk\OneSignal\OneSignalFacade;

it('resolves to OneSignal instance via facade', function () {
    $instance = OneSignalFacade::getFacadeRoot();

    expect($instance)->toBeInstanceOf(OneSignal::class);
});

it('provides sub-api accessors via facade', function () {
    $instance = OneSignalFacade::getFacadeRoot();

    expect($instance->notifications())->toBeInstanceOf(\Berkayk\OneSignal\Api\NotificationApi::class);
    expect($instance->users())->toBeInstanceOf(\Berkayk\OneSignal\Api\UserApi::class);
    expect($instance->subscriptions())->toBeInstanceOf(\Berkayk\OneSignal\Api\SubscriptionApi::class);
    expect($instance->segments())->toBeInstanceOf(\Berkayk\OneSignal\Api\SegmentApi::class);
    expect($instance->templates())->toBeInstanceOf(\Berkayk\OneSignal\Api\TemplateApi::class);
    expect($instance->apps())->toBeInstanceOf(\Berkayk\OneSignal\Api\AppApi::class);
    expect($instance->liveActivities())->toBeInstanceOf(\Berkayk\OneSignal\Api\LiveActivityApi::class);
});

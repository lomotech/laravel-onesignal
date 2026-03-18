<?php

use Berkayk\OneSignal\Enums\DeviceType;

it('has correct values matching OneSignal spec', function () {
    expect(DeviceType::iOS->value)->toBe(0);
    expect(DeviceType::Android->value)->toBe(1);
    expect(DeviceType::Amazon->value)->toBe(2);
    expect(DeviceType::WindowsPhone->value)->toBe(3);
    expect(DeviceType::ChromeExtension->value)->toBe(4);
    expect(DeviceType::ChromeWeb->value)->toBe(5);
    expect(DeviceType::WindowsDesktop->value)->toBe(6);
    expect(DeviceType::Safari->value)->toBe(7);
    expect(DeviceType::Firefox->value)->toBe(8);
    expect(DeviceType::macOS->value)->toBe(9);
    expect(DeviceType::Alexa->value)->toBe(10);
    expect(DeviceType::Email->value)->toBe(11);
    expect(DeviceType::ForHuaweiOnly->value)->toBe(13);
    expect(DeviceType::SMS->value)->toBe(14);
});

it('can be created from int value', function () {
    expect(DeviceType::from(0))->toBe(DeviceType::iOS);
    expect(DeviceType::from(1))->toBe(DeviceType::Android);
});

it('returns null for invalid values with tryFrom', function () {
    expect(DeviceType::tryFrom(99))->toBeNull();
    expect(DeviceType::tryFrom(12))->toBeNull();
});

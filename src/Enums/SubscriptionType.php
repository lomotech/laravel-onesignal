<?php

namespace Berkayk\OneSignal\Enums;

enum SubscriptionType: string
{
    case iOSPush = 'iOSPush';
    case AndroidPush = 'AndroidPush';
    case FireOSPush = 'FireOSPush';
    case ChromeExtensionPush = 'ChromeExtensionPush';
    case ChromeWebPush = 'ChromeWebPush';
    case WindowsPush = 'WindowsPush';
    case SafariPush = 'SafariPush';
    case FirefoxPush = 'FirefoxPush';
    case macOSPush = 'macOSPush';
    case HuaweiPush = 'HuaweiPush';
    case Email = 'Email';
    case SMS = 'SMS';
}

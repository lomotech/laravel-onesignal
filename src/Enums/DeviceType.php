<?php

namespace Berkayk\OneSignal\Enums;

enum DeviceType: int
{
    case iOS = 0;
    case Android = 1;
    case Amazon = 2;
    case WindowsPhone = 3;
    case ChromeExtension = 4;
    case ChromeWeb = 5;
    case WindowsDesktop = 6;
    case Safari = 7;
    case Firefox = 8;
    case macOS = 9;
    case Alexa = 10;
    case Email = 11;
    case ForHuaweiOnly = 13;
    case SMS = 14;
}

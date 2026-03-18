<?php

namespace Berkayk\OneSignal;

use Illuminate\Support\Facades\Facade;

/**
 * @mixin \Berkayk\OneSignal\OneSignal
 */
class OneSignalFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'onesignal';
    }
}

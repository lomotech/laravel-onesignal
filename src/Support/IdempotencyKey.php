<?php

namespace Berkayk\OneSignal\Support;

use Illuminate\Support\Str;

class IdempotencyKey
{
    public static function generate(): string
    {
        return (string) Str::uuid();
    }
}

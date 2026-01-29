<?php

use Carbon\Carbon;

if (!function_exists('app_now')) {
    function app_now()
    {
        return Carbon::now(config('app.timezone'));
    }
}

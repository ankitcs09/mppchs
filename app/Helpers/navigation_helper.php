<?php

use App\Services\NavigationService;
use Config\Services;

if (! function_exists('navigation_items')) {
    function navigation_items(?array $override = null): array
    {
        if ($override !== null) {
            return $override;
        }

        static $cached;
        if ($cached !== null) {
            return $cached;
        }

        /** @var NavigationService $navigation */
        $navigation = Services::navigation();
        $cached = $navigation->items();

        return $cached;
    }
}

<?php

return [
    'enabled' => (bool) env('reverse_collect.enabled', false),
    'collector_base_url' => rtrim((string) env('reverse_collect.collector_base_url', ''), '/'),
    'collector_api_key' => (string) env('reverse_collect.collector_api_key', ''),
    'callback_base_url' => rtrim((string) env('reverse_collect.callback_base_url', ''), '/'),
    'callback_secret' => (string) env('reverse_collect.callback_secret', ''),
    'catalog_refresh_seconds' => (int) env('reverse_collect.catalog_refresh_seconds', 21600),
    'chapter_refresh_seconds' => (int) env('reverse_collect.chapter_refresh_seconds', 86400),
    'dispatch_timeout' => (int) env('reverse_collect.dispatch_timeout', 5),
    'max_attempts' => (int) env('reverse_collect.max_attempts', 5),
];

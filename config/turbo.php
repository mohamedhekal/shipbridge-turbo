<?php

declare(strict_types=1);

return [
    'driver' => 'turbo',
    'base_url' => env('TURBO_BASE_URL', 'https://api.turbo.com.eg/v1'),
    'timeout' => (int) env('TURBO_TIMEOUT', 20),
    'api_key' => env('TURBO_API_KEY'),
    'token' => env('TURBO_API_KEY'),
    'status_map' => [
        'NEW' => 'created',
        'PICKED' => 'picked_up',
        'TRANSIT' => 'in_transit',
        'OFD' => 'out_for_delivery',
        'DELIVERED' => 'delivered',
        'RETURNED' => 'returned',
        'CANCELLED' => 'cancelled',
    ],
];

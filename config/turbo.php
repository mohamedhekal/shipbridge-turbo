<?php

declare(strict_types=1);

return [
    'driver' => 'turbo',

    /*
    |--------------------------------------------------------------------------
    | Turbo External API
    |--------------------------------------------------------------------------
    | Merchant credentials come from the Turbo business dashboard.
    | authentication_key is required on every request body.
    */
    'base_url' => env('TURBO_BASE_URL', 'https://backoffice.turbo-eg.com/external-api'),
    'timeout' => (int) env('TURBO_TIMEOUT', 30),

    'authentication_key' => env('TURBO_AUTHENTICATION_KEY', env('TURBO_API_KEY')),
    'api_key' => env('TURBO_AUTHENTICATION_KEY', env('TURBO_API_KEY')),
    'main_client_code' => env('TURBO_MAIN_CLIENT_CODE'),
    'second_client' => env('TURBO_SECOND_CLIENT'),

    'return_amount' => (float) env('TURBO_RETURN_AMOUNT', 0),
    'can_open' => (int) env('TURBO_CAN_OPEN', 1),

    'tracking_url_template' => env(
        'TURBO_TRACKING_URL_TEMPLATE',
        'https://turbo.info/en/tracking/?codes={barcode}'
    ),

    'status_map' => [
        'NEW' => 'created',
        'PENDING' => 'created',
        'CREATED' => 'created',
        'READY' => 'created',
        'PICKED' => 'picked_up',
        'PICKED UP' => 'picked_up',
        'PICKED_UP' => 'picked_up',
        'RECEIVED' => 'picked_up',
        'IN TRANSIT' => 'in_transit',
        'IN_TRANSIT' => 'in_transit',
        'TRANSIT' => 'in_transit',
        'ON THE WAY' => 'in_transit',
        'OUT FOR DELIVERY' => 'out_for_delivery',
        'OFD' => 'out_for_delivery',
        'WITH COURIER' => 'out_for_delivery',
        'DELIVERED' => 'delivered',
        'RETURNED' => 'returned',
        'RETURN TO VENDOR' => 'returned',
        'RTV' => 'returned',
        'CANCELLED' => 'cancelled',
        'CANCELED' => 'cancelled',
        'DELETED' => 'cancelled',
        'EXCEPTION' => 'exception',
        'ON HOLD' => 'exception',
        'REJECTED' => 'exception',
    ],
];

<?php

return [

    'enabled' => (bool) env('POS_ENABLED', true),

    'business' => [
        'name' => env('POS_BUSINESS_NAME', 'MotoZone'),
        'address' => env('POS_BUSINESS_ADDRESS', 'Av. de los Talleres 245, Col. Industrial'),
        'phone' => env('POS_BUSINESS_PHONE', '55 1234 5678'),
        'tax_id' => env('POS_BUSINESS_TAX_ID', ''),
    ],

    'currency' => 'MXN',

    'ticket' => [
        'width_mm' => 80,
        'footer' => 'Gracias por su compra.',
    ],

    'folio' => [
        'prefix' => env('POS_FOLIO_PREFIX', 'V'),
        'padding' => 6,
    ],

    'payment_methods' => [
        'cash' => [
            'label' => 'Efectivo',
            'enabled' => true,
        ],
        'card' => [
            'label' => 'Tarjeta',
            'enabled' => true,
        ],
        'transfer' => [
            'label' => 'Transferencia',
            'enabled' => true,
        ],
        'mixed' => [
            'label' => 'Mixto',
            'enabled' => true,
        ],
    ],

    'wholesale_enabled' => false,

];

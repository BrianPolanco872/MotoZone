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
        'width_mm' => 58,
        'print_mode' => 'text',
        'uppercase' => false,
        'lines_above' => 0,
        'lines_below' => 5,
        'show_unit_price' => false,
        'full_description' => false,
        'footer' => 'Gracias por su compra.',
        'policy' => '',
        'show_logo' => false,
        'show_tax_id' => false,
        'show_sku' => true,
        'show_cashier' => true,
        'show_tax' => true,
        'show_qr' => false,
        'qr_image_url' => null,
    ],

    'cut' => [
        'width_mm' => 80,
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

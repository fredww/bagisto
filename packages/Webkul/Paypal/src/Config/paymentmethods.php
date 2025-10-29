<?php

return [
    'paypal_smart_button' => [
        'code'                  => 'paypal_smart_button',
        'title'                 => 'PayPal Smart Button',
        'description'           => 'PayPal with Apple Pay support',
        'client_id'             => 'sb',
        'client_secret'         => '',
        'class'                 => 'Webkul\Paypal\Payment\SmartButton',
        'sandbox'               => true,
        'active'                => true,
        'sort'                  => 4,
        'enable_apple_pay'      => false,
        'apple_pay_merchant_id' => '',
        'accepted_currencies'   => 'USD,EUR,GBP',
    ],

    'paypal_standard' => [
        'code'             => 'paypal_standard',
        'title'            => 'PayPal Standard',
        'description'      => 'PayPal Standard',
        'class'            => 'Webkul\Paypal\Payment\Standard',
        'sandbox'          => true,
        'active'           => true,
        'business_account' => 'test@webkul.com',
        'sort'             => 3,
    ],
];

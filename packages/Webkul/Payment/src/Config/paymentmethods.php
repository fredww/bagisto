<?php

return [
    'cashondelivery'  => [
        'code'        => 'cashondelivery',
        'title'       => 'Cash On Delivery',
        'description' => 'Cash On Delivery',
        'class'       => 'Webkul\Payment\Payment\CashOnDelivery',
        'active'      => true,
        'sort'        => 1,
    ],

    'moneytransfer'   => [
        'code'        => 'moneytransfer',
        'title'       => 'Money Transfer',
        'description' => 'Money Transfer',
        'class'       => 'Webkul\Payment\Payment\MoneyTransfer',
        'active'      => true,
        'sort'        => 2,
    ],

    // FortunePay payment method registration
    'fortune_pay' => [
        'code'        => 'fortune_pay',
        'title'       => 'FortunePay',
        'description' => 'FortunePay Gateway',
        'class'       => 'App\\Payment\\FortunePay',
        'active'      => true,
        'sort'        => 50,
    ],
];

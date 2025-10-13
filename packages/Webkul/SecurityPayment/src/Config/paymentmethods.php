<?php

return [
    'security_payment' => [
        'code'        => 'security_payment',
        'title'       => 'Security Payment',
        'description' => 'Security Payment Gateway',
        'class'       => 'Webkul\SecurityPayment\Payment\SecurityPayment',
        'active'      => true,
        'sort'        => 3,
    ],
];
<?php

return [
    [
        'key'    => 'sales.payment_methods.security_payment',
        'name'   => 'security_payment::app.admin.system.title',
        'info'   => 'security_payment::app.admin.system.description',
        'sort'   => 3,
        'fields' => [
            [
                'name'          => 'title',
                'title'         => 'security_payment::app.admin.system.title',
                'type'          => 'text',
                'depends'       => 'active:1',
                'validation'    => 'required_if:active,1',
                'channel_based' => true,
                'locale_based'  => true,
            ], [
                'name'          => 'description',
                'title'         => 'security_payment::app.admin.system.description',
                'type'          => 'textarea',
                'channel_based' => true,
                'locale_based'  => true,
            ], [
                'name'          => 'image',
                'title'         => 'security_payment::app.admin.system.image',
                'type'          => 'image',
                'info'          => 'admin::app.configuration.index.sales.payment-methods.logo-information',
                'channel_based' => true,
                'locale_based'  => false,
                'validation'    => 'mimes:bmp,jpeg,jpg,png,webp',
            ], [
                'name'          => 'merchant_id',
                'title'         => 'security_payment::app.admin.system.merchant-id',
                'type'          => 'text',
                'depends'       => 'active:1',
                'validation'    => 'required_if:active,1',
                'channel_based' => true,
                'locale_based'  => false,
            ], [
                'name'          => 'security_code',
                'title'         => 'security_payment::app.admin.system.security-code',
                'type'          => 'password',
                'depends'       => 'active:1',
                'validation'    => 'required_if:active,1',
                'channel_based' => true,
                'locale_based'  => false,
            ], [
                'name'          => 'instructions',
                'title'         => 'security_payment::app.admin.system.instructions',
                'type'          => 'textarea',
                'channel_based' => true,
                'locale_based'  => true,
            ], [
                'name'          => 'active',
                'title'         => 'admin::app.configuration.index.sales.payment-methods.status',
                'type'          => 'boolean',
                'default_value' => false,
                'channel_based' => true,
                'locale_based'  => false,
            ], [
                'name'          => 'sort',
                'title'         => 'admin::app.configuration.index.sales.payment-methods.sort-order',
                'type'          => 'select',
                'options'       => [
                    ['title' => '1', 'value' => 1],
                    ['title' => '2', 'value' => 2],
                    ['title' => '3', 'value' => 3],
                    ['title' => '4', 'value' => 4],
                    ['title' => '5', 'value' => 5],
                ],
                'channel_based' => true,
                'locale_based'  => false,
            ]
        ]
    ]
];
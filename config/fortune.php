<?php

return [
    // 基础网关地址 Base gateway URL
    'base_url' => env('FORTUNE_BASE_URL', 'https://net.mrphper.xyz'),

    // 商户号 Merchant number
    'merchant_no' => env('FORTUNE_MERCHANT_NO', ''),

    // 支付密钥 User key (used for signing). Prefer storing encrypted in DB via admin UI.
    'user_key' => env('FORTUNE_USER_KEY', ''),

    // 系统用户名 Gateway username
    'username' => env('FORTUNE_USERNAME', 'demo'),

    // 自建站系统类型 BN identifier
    'bn' => env('FORTUNE_BN', 'bagisto_v2'),

    // 支付方式 Payment method (e.g., redirect_pay)
    'payment_method' => env('FORTUNE_PAYMENT_METHOD', 'redirect_pay'),

    // 回调URL配置 Callback URLs. If empty, controller will default to route values.
    'notify_url' => env('FORTUNE_NOTIFY_URL', ''),
    'success_uri' => env('FORTUNE_SUCCESS_URI', ''),
    'return_uri' => env('FORTUNE_RETURN_URI', ''),

    // 支付渠道开关控制 Channel toggles
    'channel_redirect' => env('FORTUNE_CHANNEL_REDIRECT', true),
    'channel_iframe' => env('FORTUNE_CHANNEL_IFRAME', false),
];
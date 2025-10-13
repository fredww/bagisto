<?php

return [
    'admin' => [
        'system' => [
            'title'             => '安全支付',
            'description'       => '通过安全支付网关接受安全支付',
            'merchant_id'       => '商户ID',
            'security_code'     => '安全密钥',
            'active'            => '状态',
            'sort_order'        => '排序',
            'image'             => '图标',
            'new_order_status'  => '新订单状态',
            'instructions'      => '支付说明',
        ],
    ],

    'shop' => [
        'title'       => '安全支付',
        'description' => '通过我们的安全支付网关安全地使用信用卡付款',
        
        'payment' => [
            'redirecting'              => '正在跳转到支付网关',
            'redirect_message'         => '请稍候，我们正在将您重定向到安全支付网关...',
            'security_notice'          => '您的支付信息受到银行级安全保护',
            
            'success_title'            => '支付成功',
            'payment_successful'       => '支付成功！',
            'payment_success_message'  => '谢谢！您的付款已成功处理。',
            'transaction_id'           => '交易号',
            'security_success_notice'  => '您的交易已安全完成。',
            
            'failure_title'            => '支付失败',
            'payment_failed'           => '支付失败',
            'payment_failure_message'  => '我们无法处理您的付款。请重试。',
            'what_next'                => '我接下来应该怎么做？',
            'check_card_details'       => '验证您的卡片详细信息是否正确',
            'check_balance'            => '确保有足够的资金可用',
            'try_different_card'       => '尝试使用不同的付款方式',
            'contact_bank'             => '如果问题仍然存在，请联系您的银行',
            'try_again'                => '重试',
            'need_help'                => '需要付款帮助？',
            'contact_support'          => '联系客服',
            
            'error_title'              => '支付错误',
            'payment_error'            => '支付错误',
            'error_occurred'           => '发生错误',
            'error_description'        => '处理您的付款时遇到意外错误。',
            'possible_causes'          => '可能的原因',
            'network_issue'            => '网络连接问题',
            'gateway_maintenance'      => '支付网关维护',
            'session_expired'          => '您的会话已过期',
            'invalid_request'          => '无效的支付请求',
            'recommended_actions'      => '建议操作',
            'refresh_page'             => '刷新页面并重试',
            'check_connection'         => '检查您的网络连接',
            'try_later'                => '几分钟后再试',
            'persistent_issue'         => '如果问题仍然存在，请联系我们的客服团队。',
            'email_support'            => '邮件客服',
            
            'invalid_signature'        => '无效的支付签名。请重试。',
        ],
    ],
];
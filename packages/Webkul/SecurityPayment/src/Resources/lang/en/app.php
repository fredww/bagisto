<?php

return [
    'admin' => [
        'system' => [
            'title'             => 'Security Payment',
            'description'       => 'Accept secure payments through Security Payment Gateway',
            'merchant_id'       => 'Merchant ID',
            'security_code'     => 'Security Code',
            'active'            => 'Status',
            'sort_order'        => 'Sort Order',
            'image'             => 'Logo',
            'new_order_status'  => 'New Order Status',
            'instructions'      => 'Instructions',
        ],
    ],

    'shop' => [
        'title'       => 'Security Payment',
        'description' => 'Pay securely with your credit card through our secure payment gateway',
        
        'payment' => [
            'redirecting'              => 'Redirecting to Payment Gateway',
            'redirect_message'         => 'Please wait while we redirect you to the secure payment gateway...',
            'security_notice'          => 'Your payment information is protected with bank-level security',
            
            'success_title'            => 'Payment Successful',
            'payment_successful'       => 'Payment Successful!',
            'payment_success_message'  => 'Thank you! Your payment has been processed successfully.',
            'transaction_id'           => 'Transaction ID',
            'security_success_notice'  => 'Your transaction has been completed securely.',
            
            'failure_title'            => 'Payment Failed',
            'payment_failed'           => 'Payment Failed',
            'payment_failure_message'  => 'We were unable to process your payment. Please try again.',
            'what_next'                => 'What should I do next?',
            'check_card_details'       => 'Verify your card details are correct',
            'check_balance'            => 'Ensure sufficient funds are available',
            'try_different_card'       => 'Try using a different payment method',
            'contact_bank'             => 'Contact your bank if the issue persists',
            'try_again'                => 'Try Again',
            'need_help'                => 'Need help with your payment?',
            'contact_support'          => 'Contact Support',
            
            'error_title'              => 'Payment Error',
            'payment_error'            => 'Payment Error',
            'error_occurred'           => 'An Error Occurred',
            'error_description'        => 'We encountered an unexpected error while processing your payment.',
            'possible_causes'          => 'Possible Causes',
            'network_issue'            => 'Network connectivity issues',
            'gateway_maintenance'      => 'Payment gateway maintenance',
            'session_expired'          => 'Your session has expired',
            'invalid_request'          => 'Invalid payment request',
            'recommended_actions'      => 'Recommended Actions',
            'refresh_page'             => 'Refresh the page and try again',
            'check_connection'         => 'Check your internet connection',
            'try_later'                => 'Try again in a few minutes',
            'persistent_issue'         => 'If the problem persists, please contact our support team.',
            'email_support'            => 'Email Support',
            
            'invalid_signature'        => 'Invalid payment signature. Please try again.',
        ],
    ],
];
<?php
declare(strict_types=1);

/**
 * Payment gateway helper configuration.
 * Gateway credentials are stored encrypted in the payment_methods table.
 */
return [
    'razorpay' => [
        'api_base' => 'https://api.razorpay.com/v1',
        'checkout_js' => 'https://checkout.razorpay.com/v1/checkout.js',
        'supported_currencies' => ['INR', 'USD', 'EUR', 'GBP'],
    ],
    'manual' => [
        'require_reference' => true,
        'allow_proof_upload' => true,
    ],
];

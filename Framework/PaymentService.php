<?php

namespace Framework;

use InvalidArgumentException;

class PayFastService
{
    private array $config;

    public function __construct()
    {
        $config = require basePath('config/payfast.php');
    }

    public function createPayment(mixed $order): string
    {
        if ($order->amount <= 0) {
            throw new InvalidArgumentException('Invalid payment amount.');
        }

        


        // 1. Prepare payment payload
        // 2. Authenticate / sign request as required
        // 3. Send request to PayFast
        // 4. Parse gateway response
        // 5. Return redirect URL

        // throw new RuntimeException(
        //     'PayFast payment request not implemented yet'
        // );

        return '';
    }

    public function verifyPayment(array $data): bool
    {
        // Validate payment notification according
        // to PayFast's official verification protocol.

        return false;
    }
}
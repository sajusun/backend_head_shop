<?php

namespace App\Services\Payment;

use App\Contracts\PaymentGatewayInterface;
use InvalidArgumentException;

class PaymentManager
{
    /**
     * Resolve the payment gateway based on the payment method.
     */
    public function getGateway(string $method): PaymentGatewayInterface
    {
        return match (strtolower($method)) {
            'stripe' => new StripeGateway(),
            'sslcommerz' => new SSLCommerzGateway(),
            default => throw new InvalidArgumentException("Payment method [{$method}] not supported."),
        };
    }
}

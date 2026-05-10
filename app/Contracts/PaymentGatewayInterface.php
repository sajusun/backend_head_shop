<?php

namespace App\Contracts;

use App\Models\Order;
use Illuminate\Http\Request;

interface PaymentGatewayInterface
{
    /**
     * Initialize the payment and return the redirect URL.
     */
    public function initialize(Order $order): string;

    /**
     * Verify the payment from the callback request.
     */
    public function verify(Request $request): bool;

    /**
     * Get the identifier for the gateway.
     */
    public function getIdentifier(): string;
}

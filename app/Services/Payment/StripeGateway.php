<?php

namespace App\Services\Payment;

use App\Contracts\PaymentGatewayInterface;
use App\Models\Order;
use Illuminate\Http\Request;
use Stripe\Stripe;
use Stripe\Checkout\Session;

class StripeGateway implements PaymentGatewayInterface
{
    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    public function initialize(Order $order): string
    {
        $session = Session::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => 'usd',
                    'product_data' => [
                        'name' => 'Order ' . $order->order_number,
                    ],
                    'unit_amount' => (int)($order->total_amount * 100),
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => route('payment.callback', ['gateway' => 'stripe', 'status' => 'success']) . '?session_id={CHECKOUT_SESSION_ID}&order_number=' . $order->order_number,
            'cancel_url' => route('payment.callback', ['gateway' => 'stripe', 'status' => 'cancel']) . '?order_number=' . $order->order_number,
            'metadata' => [
                'order_id' => $order->id,
            ],
        ]);

        return $session->url;
    }

    public function verify(Request $request): bool
    {
        $sessionId = $request->get('session_id');
        if (!$sessionId) return false;

        $session = Session::retrieve($sessionId);
        return $session->payment_status === 'paid';
    }

    public function getIdentifier(): string
    {
        return 'stripe';
    }
}

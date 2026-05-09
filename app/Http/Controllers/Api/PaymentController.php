<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Payment\PaymentManager;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    use ApiResponse;

    protected $paymentManager;

    public function __construct(PaymentManager $paymentManager)
    {
        $this->paymentManager = $paymentManager;
    }

    /**
     * Initiate a payment session.
     */
    public function initiate(Request $request)
    {
        $request->validate([
            'order_id' => 'required|exists:orders,id',
        ]);

        try {
            $order = Order::findOrFail($request->order_id);

            if ($order->payment_status === 'paid') {
                return $this->error('Order already paid', 400);
            }

            $gateway = $this->paymentManager->getGateway($order->payment_method);
            $redirectUrl = $gateway->initialize($order);

            return $this->success('Payment session created', [
                'redirect_url' => $redirectUrl,
            ]);
        } catch (\Throwable $th) {
            Log::error('Payment Initiation Error: ' . $th->getMessage());
            return $this->error('Failed to initiate payment: ' . $th->getMessage(), 500);
        }
    }

    /**
     * Unified callback handler for all gateways.
     */
    public function handleCallback(Request $request, string $gatewayName, string $status)
    {
        Log::info("Payment Callback [{$gatewayName}]: {$status}", $request->all());

        try {
            $gateway = $this->paymentManager->getGateway($gatewayName);
            
            // Get order number from request (most gateways send it as tran_id or order_number)
            $orderNumber = $request->input('tran_id') ?? $request->input('order_number');
            $order = Order::where('order_number', $orderNumber)->first();

            if (!$order) {
                return redirect(config('app.frontend_url') . '/order/fail');
            }

            if ($status === 'success' || $status === 'ipn') {
                if ($gateway->verify($request)) {
                    $order->update([
                        'payment_status' => 'paid',
                        'status' => 'processing'
                    ]);
                    
                    if ($status === 'ipn') return response('OK', 200);

                    return redirect(config('app.frontend_url') . '/order/success?order=' . $order->order_number);
                }
            }

            // Handle Fail/Cancel
            if ($status === 'cancel') {
                return redirect(config('app.frontend_url') . '/order/cancel');
            }

            return redirect(config('app.frontend_url') . '/order/fail');

        } catch (\Throwable $th) {
            Log::error('Payment Callback Error: ' . $th->getMessage());
            return redirect(config('app.frontend_url') . '/order/fail');
        }
    }
}

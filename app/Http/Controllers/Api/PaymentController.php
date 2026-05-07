<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Stripe;
use Stripe\PaymentIntent;

class PaymentController extends Controller
{
    use ApiResponse;

    public function initiateStripePayment(Request $request)
    {
        $request->validate([
            'order_id' => 'required|exists:orders,id',
        ]);

        try {
            $order = Order::findOrFail($request->order_id);

            if ($order->payment_status === 'paid') {
                return $this->error('Order already paid', 400);
            }

            Stripe::setApiKey(config('services.stripe.secret'));

            $intent = PaymentIntent::create([
                'amount' => (int)($order->total_amount * 100), // Amount in cents
                'currency' => 'usd',
                'metadata' => [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                ],
            ]);

            return $this->success('Payment intent created', [
                'client_secret' => $intent->client_secret,
            ]);
        } catch (\Throwable $th) {
            Log::error('Stripe Initiation Error: ' . $th->getMessage());
            return $this->error('Failed to initiate payment', 500);
        }
    }

    public function initiateSSLCommerzPayment(Request $request)
    {
        $request->validate([
            'order_id' => 'required|exists:orders,id',
        ]);

        try {
            $order = Order::findOrFail($request->order_id);

            $post_data = array();
            $post_data['store_id'] = config('services.sslcommerz.store_id');
            $post_data['store_passwd'] = config('services.sslcommerz.store_password');
            $post_data['total_amount'] = $order->total_amount;
            $post_data['currency'] = "BDT";
            $post_data['tran_id'] = $order->order_number;
            $post_data['success_url'] = route('ssl.success');
            $post_data['fail_url'] = route('ssl.fail');
            $post_data['cancel_url'] = route('ssl.cancel');
            $post_data['ipn_url'] = route('ssl.ipn');

            # CUSTOMER INFORMATION
            $post_data['cus_name'] = $order->full_name;
            $post_data['cus_email'] = $order->email;
            $post_data['cus_add1'] = $order->address;
            $post_data['cus_city'] = $order->city;
            $post_data['cus_postcode'] = $order->postal_code;
            $post_data['cus_country'] = "Bangladesh";
            $post_data['cus_phone'] = $order->phone;

            # SHIPMENT INFORMATION
            $post_data['shipping_method'] = "NO";
            $post_data['num_of_item'] = $order->items()->count();
            $post_data['product_name'] = "Order " . $order->order_number;
            $post_data['product_category'] = "General";
            $post_data['product_profile'] = "general";

            $is_sandbox = config('services.sslcommerz.is_sandbox', true);
            $direct_api_url = $is_sandbox 
                ? "https://sandbox.sslcommerz.com/gwprocess/v4/api.php" 
                : "https://securepay.sslcommerz.com/gwprocess/v4/api.php";

            $handle = curl_init();
            curl_setopt($handle, CURLOPT_URL, $direct_api_url);
            curl_setopt($handle, CURLOPT_TIMEOUT, 30);
            curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 30);
            curl_setopt($handle, CURLOPT_POST, 1);
            curl_setopt($handle, CURLOPT_POSTFIELDS, http_build_query($post_data));
            curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, FALSE); # KEEP IT FALSE IF YOU RUN FROM LOCAL PC

            $content = curl_exec($handle);
            $code = curl_getinfo($handle, CURLINFO_HTTP_CODE);

            if ($code == 200 && !(curl_errno($handle))) {
                curl_close($handle);
                $response = json_decode($content, true);
                if (isset($response['GatewayPageURL']) && $response['GatewayPageURL'] != "") {
                    return $this->success('SSLCommerz session created', [
                        'redirect_url' => $response['GatewayPageURL']
                    ]);
                } else {
                    return $this->error('SSLCommerz Error: ' . ($response['failedreason'] ?? 'Unknown'), 400);
                }
            } else {
                curl_close($handle);
                return $this->error('Failed to connect with SSLCommerz', 500);
            }

        } catch (\Throwable $th) {
            Log::error('SSLCommerz Initiation Error: ' . $th->getMessage());
            return $this->error('Failed to initiate payment', 500);
        }
    }

    public function sslSuccess(Request $request)
    {
        $tran_id = $request->input('tran_id');
        $order = Order::where('order_number', $tran_id)->first();

        if ($order) {
            $order->update([
                'payment_status' => 'paid',
                'status' => 'processing'
            ]);
            
            // Redirect to frontend success page
            return redirect(config('app.frontend_url') . '/order/success?order=' . $order->order_number);
        }

        return redirect(config('app.frontend_url') . '/order/fail');
    }

    public function sslFail(Request $request)
    {
        return redirect(config('app.frontend_url') . '/order/fail');
    }

    public function sslCancel(Request $request)
    {
        return redirect(config('app.frontend_url') . '/order/cancel');
    }

    public function sslIpn(Request $request)
    {
        // Handle IPN (Instant Payment Notification)
        $tran_id = $request->input('tran_id');
        $status = $request->input('status');

        if ($status === 'VALID' || $status === 'AUTHENTICATED') {
            $order = Order::where('order_number', $tran_id)->first();
            if ($order && $order->payment_status !== 'paid') {
                $order->update([
                    'payment_status' => 'paid',
                    'status' => 'processing'
                ]);
            }
        }
    }
}

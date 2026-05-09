<?php

namespace App\Services\Payment;

use App\Contracts\PaymentGatewayInterface;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SSLCommerzGateway implements PaymentGatewayInterface
{
    protected $storeId;
    protected $storePassword;
    protected $isSandbox;
    protected $apiUrl;

    public function __construct()
    {
        $this->storeId = config('services.sslcommerz.store_id');
        $this->storePassword = config('services.sslcommerz.store_password');
        $this->isSandbox = config('services.sslcommerz.is_sandbox', true);
        
        $this->apiUrl = $this->isSandbox 
            ? "https://sandbox.sslcommerz.com/gwprocess/v4/api.php" 
            : "https://securepay.sslcommerz.com/gwprocess/v4/api.php";
    }

    public function initialize(Order $order): string
    {
        $post_data = [
            'store_id' => $this->storeId,
            'store_passwd' => $this->storePassword,
            'total_amount' => $order->total_amount,
            'currency' => "BDT",
            'tran_id' => $order->order_number,
            'success_url' => route('payment.callback', ['gateway' => 'sslcommerz', 'status' => 'success']),
            'fail_url' => route('payment.callback', ['gateway' => 'sslcommerz', 'status' => 'fail']),
            'cancel_url' => route('payment.callback', ['gateway' => 'sslcommerz', 'status' => 'cancel']),
            'ipn_url' => route('payment.callback', ['gateway' => 'sslcommerz', 'status' => 'ipn']),
            'cus_name' => $order->full_name,
            'cus_email' => $order->email,
            'cus_add1' => $order->address,
            'cus_city' => $order->city,
            'cus_postcode' => $order->postal_code,
            'cus_country' => "Bangladesh",
            'cus_phone' => $order->phone,
            'shipping_method' => "NO",
            'num_of_item' => $order->items()->count(),
            'product_name' => "Order " . $order->order_number,
            'product_category' => "General",
            'product_profile' => "general",
        ];

        $handle = curl_init();
        curl_setopt($handle, CURLOPT_URL, $this->apiUrl);
        curl_setopt($handle, CURLOPT_POST, 1);
        curl_setopt($handle, CURLOPT_POSTFIELDS, http_build_query($post_data));
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, FALSE);

        $content = curl_exec($handle);
        $code = curl_getinfo($handle, CURLINFO_HTTP_CODE);
        curl_close($handle);

        if ($code == 200) {
            $response = json_decode($content, true);
            if (isset($response['GatewayPageURL']) && $response['GatewayPageURL'] != "") {
                return $response['GatewayPageURL'];
            }
        }

        throw new \Exception('Failed to connect with SSLCommerz: ' . $content);
    }

    public function verify(Request $request): bool
    {
        $status = $request->input('status');
        return $status === 'VALID' || $status === 'AUTHENTICATED' || $status === 'success';
    }

    public function getIdentifier(): string
    {
        return 'sslcommerz';
    }
}

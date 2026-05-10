<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmed — HERD SHOP</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; background: #f4f4f5; color: #18181b; }
        .wrapper { max-width: 640px; margin: 40px auto; background: #ffffff; border-radius: 24px; overflow: hidden; box-shadow: 0 4px 40px rgba(0,0,0,0.08); }
        .header { background: linear-gradient(135deg, #18181b 0%, #27272a 60%, #3f3f46 100%); padding: 48px 40px; text-align: center; }
        .header-logo { font-size: 13px; font-weight: 800; letter-spacing: 4px; color: #a1a1aa; text-transform: uppercase; margin-bottom: 8px; }
        .header-brand { font-size: 28px; font-weight: 900; color: #ffffff; letter-spacing: -1px; }
        .header-brand span { color: #f59e0b; }
        .success-badge { display: inline-block; background: rgba(34,197,94,0.15); border: 1px solid rgba(34,197,94,0.3); color: #4ade80; font-size: 12px; font-weight: 700; letter-spacing: 2px; text-transform: uppercase; padding: 6px 16px; border-radius: 100px; margin-top: 24px; }
        .body { padding: 40px; }
        .greeting { font-size: 22px; font-weight: 800; color: #18181b; margin-bottom: 8px; }
        .subtext { font-size: 15px; color: #71717a; line-height: 1.6; margin-bottom: 32px; }
        .order-box { background: #f4f4f5; border-radius: 16px; padding: 24px; margin-bottom: 32px; }
        .order-number { font-size: 13px; color: #71717a; font-weight: 600; margin-bottom: 4px; }
        .order-number strong { color: #18181b; font-size: 18px; font-weight: 900; }
        .order-meta { display: flex; gap: 24px; margin-top: 16px; flex-wrap: wrap; }
        .meta-item { font-size: 13px; color: #71717a; }
        .meta-item strong { display: block; color: #18181b; font-weight: 700; font-size: 14px; }
        .section-title { font-size: 13px; font-weight: 800; letter-spacing: 2px; text-transform: uppercase; color: #a1a1aa; margin-bottom: 16px; }
        .item-row { display: flex; justify-content: space-between; align-items: flex-start; padding: 14px 0; border-bottom: 1px solid #f4f4f5; }
        .item-row:last-child { border-bottom: none; }
        .item-name { font-size: 14px; font-weight: 700; color: #18181b; }
        .item-meta { font-size: 12px; color: #a1a1aa; margin-top: 2px; }
        .item-price { font-size: 14px; font-weight: 800; color: #18181b; white-space: nowrap; }
        .totals { margin-top: 24px; border-top: 2px solid #f4f4f5; padding-top: 20px; }
        .total-row { display: flex; justify-content: space-between; font-size: 14px; padding: 6px 0; }
        .total-row.grand { font-size: 17px; font-weight: 900; border-top: 2px solid #18181b; margin-top: 8px; padding-top: 12px; }
        .address-box { background: #f9f9f9; border-radius: 12px; padding: 20px; margin-bottom: 32px; }
        .address-text { font-size: 14px; color: #52525b; line-height: 1.8; }
        .cta-btn { display: block; text-align: center; background: #18181b; color: #ffffff; text-decoration: none; font-size: 15px; font-weight: 800; letter-spacing: 0.5px; padding: 18px 32px; border-radius: 14px; margin: 32px 0; }
        .footer { background: #f4f4f5; padding: 28px 40px; text-align: center; }
        .footer-text { font-size: 12px; color: #a1a1aa; line-height: 1.8; }
        .footer-brand { font-size: 14px; font-weight: 900; color: #71717a; letter-spacing: 2px; margin-bottom: 8px; }
        .divider { height: 1px; background: #f4f4f5; margin: 24px 0; }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="header">
        <div class="header-logo">Premium Collection</div>
        <div class="header-brand">HERD <span>SHOP</span></div>
        <div class="success-badge">✓ Order Confirmed</div>
    </div>

    <div class="body">
        <h1 class="greeting">Thank you, {{ $order->full_name }}!</h1>
        <p class="subtext">We've received your order and it's now being prepared. You'll receive another email when it ships.</p>

        <div class="order-box">
            <div class="order-number">Order Number<br><strong>{{ $order->order_number }}</strong></div>
            <div class="order-meta">
                <div class="meta-item">
                    <strong>{{ \Carbon\Carbon::parse($order->created_at)->format('M d, Y') }}</strong>
                    Order Date
                </div>
                <div class="meta-item">
                    <strong>{{ ucfirst($order->payment_method) }}</strong>
                    Payment Method
                </div>
                <div class="meta-item">
                    <strong style="color: #f59e0b;">{{ ucfirst($order->status) }}</strong>
                    Status
                </div>
            </div>
        </div>

        <div class="section-title">Order Summary</div>
        @foreach($order->items as $item)
        <div class="item-row">
            <div>
                <div class="item-name">{{ $item->product->name ?? 'Product' }}</div>
                <div class="item-meta">
                    Qty: {{ $item->quantity }}
                    @if($item->selected_color) · {{ $item->selected_color }} @endif
                    @if($item->selected_size) · {{ $item->selected_size }} @endif
                </div>
            </div>
            <div class="item-price">${{ number_format($item->price * $item->quantity, 2) }}</div>
        </div>
        @endforeach

        <div class="totals">
            <div class="total-row"><span>Subtotal</span><span>${{ number_format($order->subtotal, 2) }}</span></div>
            <div class="total-row"><span>Shipping</span><span>{{ $order->shipping_cost > 0 ? '$'.number_format($order->shipping_cost, 2) : 'Free' }}</span></div>
            <div class="total-row"><span>Tax</span><span>${{ number_format($order->tax, 2) }}</span></div>
            <div class="total-row grand"><span>Total</span><span>${{ number_format($order->total_amount, 2) }}</span></div>
        </div>

        <div class="divider"></div>

        <div class="section-title">Shipping To</div>
        <div class="address-box">
            <div class="address-text">
                <strong>{{ $order->full_name }}</strong><br>
                {{ $order->address }}<br>
                {{ $order->city }}, {{ $order->postal_code }}<br>
                {{ $order->phone }}
            </div>
        </div>

        <a href="{{ env('FRONTEND_URL') }}/orders" class="cta-btn">Track Your Order →</a>
    </div>

    <div class="footer">
        <div class="footer-brand">HERD SHOP</div>
        <div class="footer-text">
            Questions? Reply to this email or contact us at support@herdshop.com<br>
            © {{ date('Y') }} HERD SHOP. All rights reserved.
        </div>
    </div>
</div>
</body>
</html>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Update — HERD SHOP</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; background: #f4f4f5; color: #18181b; }
        .wrapper { max-width: 640px; margin: 40px auto; background: #ffffff; border-radius: 24px; overflow: hidden; box-shadow: 0 4px 40px rgba(0,0,0,0.08); }
        .header { background: linear-gradient(135deg, #18181b 0%, #27272a 60%, #3f3f46 100%); padding: 48px 40px; text-align: center; }
        .header-logo { font-size: 13px; font-weight: 800; letter-spacing: 4px; color: #a1a1aa; text-transform: uppercase; margin-bottom: 8px; }
        .header-brand { font-size: 28px; font-weight: 900; color: #ffffff; letter-spacing: -1px; }
        .header-brand span { color: #f59e0b; }
        .status-badge { display: inline-block; font-size: 12px; font-weight: 700; letter-spacing: 2px; text-transform: uppercase; padding: 6px 16px; border-radius: 100px; margin-top: 24px; }
        .status-processing { background: rgba(59,130,246,0.15); border: 1px solid rgba(59,130,246,0.3); color: #60a5fa; }
        .status-shipped { background: rgba(168,85,247,0.15); border: 1px solid rgba(168,85,247,0.3); color: #c084fc; }
        .status-delivered { background: rgba(34,197,94,0.15); border: 1px solid rgba(34,197,94,0.3); color: #4ade80; }
        .status-cancelled { background: rgba(239,68,68,0.15); border: 1px solid rgba(239,68,68,0.3); color: #f87171; }
        .status-default { background: rgba(245,158,11,0.15); border: 1px solid rgba(245,158,11,0.3); color: #fbbf24; }
        .body { padding: 40px; }
        .greeting { font-size: 22px; font-weight: 800; color: #18181b; margin-bottom: 8px; }
        .subtext { font-size: 15px; color: #71717a; line-height: 1.6; margin-bottom: 32px; }
        .order-box { background: #f4f4f5; border-radius: 16px; padding: 24px; margin-bottom: 32px; }
        .order-number { font-size: 13px; color: #71717a; font-weight: 600; margin-bottom: 4px; }
        .order-number strong { color: #18181b; font-size: 18px; font-weight: 900; }
        .status-timeline { display: flex; align-items: center; gap: 0; margin: 32px 0; overflow-x: auto; }
        .timeline-step { text-align: center; flex: 1; min-width: 70px; }
        .timeline-dot { width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 14px; margin: 0 auto 8px; font-weight: 700; }
        .timeline-dot.active { background: #18181b; color: white; }
        .timeline-dot.done { background: #22c55e; color: white; }
        .timeline-dot.future { background: #f4f4f5; color: #a1a1aa; border: 2px solid #e4e4e7; }
        .timeline-line { flex: 1; height: 2px; background: #e4e4e7; margin-bottom: 24px; min-width: 20px; }
        .timeline-line.done-line { background: #22c55e; }
        .timeline-label { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: #a1a1aa; }
        .timeline-label.active-label { color: #18181b; }
        .cta-btn { display: block; text-align: center; background: #18181b; color: #ffffff; text-decoration: none; font-size: 15px; font-weight: 800; letter-spacing: 0.5px; padding: 18px 32px; border-radius: 14px; margin: 32px 0; }
        .info-box { background: #fffbeb; border: 1px solid #fde68a; border-radius: 12px; padding: 16px 20px; font-size: 13px; color: #92400e; line-height: 1.6; margin-bottom: 24px; }
        .footer { background: #f4f4f5; padding: 28px 40px; text-align: center; }
        .footer-text { font-size: 12px; color: #a1a1aa; line-height: 1.8; }
        .footer-brand { font-size: 14px; font-weight: 900; color: #71717a; letter-spacing: 2px; margin-bottom: 8px; }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="header">
        <div class="header-logo">Order Update</div>
        <div class="header-brand">HERD <span>SHOP</span></div>
        @php
            $statusClass = match($newStatus) {
                'processing' => 'status-processing',
                'shipped'    => 'status-shipped',
                'delivered'  => 'status-delivered',
                'cancelled'  => 'status-cancelled',
                default      => 'status-default',
            };
            $statusEmoji = match($newStatus) {
                'processing' => '⚙️ Processing',
                'shipped'    => '🚚 Shipped',
                'delivered'  => '📦 Delivered',
                'cancelled'  => '❌ Cancelled',
                default      => ucfirst($newStatus),
            };
        @endphp
        <div class="status-badge {{ $statusClass }}">{{ $statusEmoji }}</div>
    </div>

    <div class="body">
        <h1 class="greeting">Order Status Updated</h1>
        <p class="subtext">
            Hi {{ $order->full_name }}, your order <strong>{{ $order->order_number }}</strong> status has been updated.
        </p>

        @if($newStatus === 'processing')
        <div class="info-box">
            ⚙️ Our team is carefully preparing your order. This usually takes 1–2 business days.
        </div>
        @elseif($newStatus === 'shipped')
        <div class="info-box">
            🚚 Your order is on its way! Estimated delivery: 3–5 business days. Keep an eye out for your package!
        </div>
        @elseif($newStatus === 'delivered')
        <div class="info-box" style="background:#f0fdf4; border-color:#86efac; color:#166534;">
            📦 Your order has been delivered! We hope you love your purchase. Consider leaving a review to help others.
        </div>
        @elseif($newStatus === 'cancelled')
        <div class="info-box" style="background:#fef2f2; border-color:#fca5a5; color:#991b1b;">
            ❌ Your order has been cancelled. If you have questions or believe this is an error, please contact our support team.
        </div>
        @endif

        {{-- Status Timeline --}}
        @if($newStatus !== 'cancelled')
        @php
            $steps = ['pending', 'processing', 'shipped', 'delivered'];
            $currentIndex = array_search($newStatus, $steps);
            if ($currentIndex === false) $currentIndex = 0;
        @endphp
        <div class="status-timeline">
            @foreach($steps as $i => $step)
                <div class="timeline-step">
                    <div class="timeline-dot {{ $i < $currentIndex ? 'done' : ($i === $currentIndex ? 'active' : 'future') }}">
                        {{ $i < $currentIndex ? '✓' : ($i + 1) }}
                    </div>
                    <div class="timeline-label {{ $i === $currentIndex ? 'active-label' : '' }}">{{ ucfirst($step) }}</div>
                </div>
                @if($i < count($steps) - 1)
                <div class="timeline-line {{ $i < $currentIndex ? 'done-line' : '' }}"></div>
                @endif
            @endforeach
        </div>
        @endif

        <div class="order-box">
            <div class="order-number">Order Number<br><strong>{{ $order->order_number }}</strong></div>
        </div>

        <a href="{{ env('FRONTEND_URL') }}/orders" class="cta-btn">View Order Details →</a>
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

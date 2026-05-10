<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Newsletter Subscription — HERD SHOP</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; background: #f4f4f5; color: #18181b; }
        .wrapper { max-width: 640px; margin: 40px auto; background: #fff; border-radius: 24px; overflow: hidden; box-shadow: 0 4px 40px rgba(0,0,0,0.08); }
        .header { background: linear-gradient(135deg, #18181b 0%, #3f3f46 100%); padding: 56px 40px; text-align: center; }
        .header-brand { font-size: 28px; font-weight: 900; color: #fff; letter-spacing: -1px; }
        .header-brand span { color: #f59e0b; }
        .body { padding: 48px 40px; }
        .greeting { font-size: 24px; font-weight: 900; color: #18181b; margin-bottom: 12px; }
        .subtext { font-size: 15px; color: #71717a; line-height: 1.8; margin-bottom: 32px; }
        .info-box { background: #f0fdf4; border: 1px solid #86efac; border-radius: 14px; padding: 20px 24px; margin-bottom: 28px; font-size: 14px; color: #166534; line-height: 1.8; }
        .cta-btn { display: block; text-align: center; background: #18181b; color: #fff; text-decoration: none; font-size: 15px; font-weight: 800; padding: 18px 32px; border-radius: 14px; margin: 24px 0; }
        .unsubscribe { text-align: center; font-size: 12px; color: #a1a1aa; margin-top: 16px; }
        .footer { background: #f4f4f5; padding: 24px 40px; text-align: center; font-size: 12px; color: #a1a1aa; line-height: 1.8; }
        .footer-brand { font-size: 14px; font-weight: 900; color: #71717a; letter-spacing: 2px; margin-bottom: 6px; }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="header">
        <div style="font-size:48px; margin-bottom:16px;">📬</div>
        <div class="header-brand">HERD <span>SHOP</span></div>
        <div style="font-size:13px; color:#a1a1aa; margin-top:8px;">You're now subscribed!</div>
    </div>
    <div class="body">
        <h1 class="greeting">You're In! 🎉</h1>
        <p class="subtext">Thank you for subscribing to the HERD SHOP newsletter. You'll be the first to know about:</p>
        <div class="info-box">
            ✅ <strong>New Arrivals</strong> — First access to our latest premium drops<br>
            ✅ <strong>Exclusive Deals</strong> — Members-only discounts and flash sales<br>
            ✅ <strong>Style Guides</strong> — Expert tips and curated collections<br>
            ✅ <strong>Behind the Scenes</strong> — Stories from our makers and designers
        </div>
        <a href="{{ env('FRONTEND_URL') }}/products" class="cta-btn">Shop the Latest Collection →</a>
        <p class="unsubscribe">You can manage your subscription preferences anytime in your account <a href="{{ env('FRONTEND_URL') }}/settings" style="color:#71717a;">Settings</a>.</p>
    </div>
    <div class="footer">
        <div class="footer-brand">HERD SHOP</div>
        © {{ date('Y') }} HERD SHOP. All rights reserved.<br>
        Sent to: {{ $subscriberEmail }}
    </div>
</div>
</body>
</html>

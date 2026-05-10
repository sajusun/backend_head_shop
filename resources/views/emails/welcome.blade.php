<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to HERD SHOP</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; background: #f4f4f5; color: #18181b; }
        .wrapper { max-width: 640px; margin: 40px auto; background: #fff; border-radius: 24px; overflow: hidden; box-shadow: 0 4px 40px rgba(0,0,0,0.08); }
        .header { background: linear-gradient(135deg, #18181b 0%, #3f3f46 100%); padding: 56px 40px; text-align: center; }
        .header-brand { font-size: 28px; font-weight: 900; color: #fff; letter-spacing: -1px; }
        .header-brand span { color: #f59e0b; }
        .header-tagline { font-size: 14px; color: #a1a1aa; margin-top: 8px; }
        .body { padding: 48px 40px; }
        .greeting { font-size: 24px; font-weight: 900; color: #18181b; margin-bottom: 12px; }
        .subtext { font-size: 15px; color: #71717a; line-height: 1.8; margin-bottom: 32px; }
        .feature { display: flex; gap: 14px; align-items: flex-start; padding: 16px; background: #f9f9f9; border-radius: 12px; margin-bottom: 12px; }
        .feature-icon { font-size: 22px; }
        .feature-title { font-size: 14px; font-weight: 800; color: #18181b; }
        .feature-desc { font-size: 13px; color: #71717a; }
        .cta-btn { display: block; text-align: center; background: #18181b; color: #fff; text-decoration: none; font-size: 15px; font-weight: 800; padding: 18px 32px; border-radius: 14px; margin: 32px 0; }
        .footer { background: #f4f4f5; padding: 24px 40px; text-align: center; font-size: 12px; color: #a1a1aa; line-height: 1.8; }
        .footer-brand { font-size: 14px; font-weight: 900; color: #71717a; letter-spacing: 2px; margin-bottom: 6px; }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="header">
        <div style="font-size:48px; margin-bottom:16px;">🎉</div>
        <div class="header-brand">HERD <span>SHOP</span></div>
        <div class="header-tagline">Premium Collection · Crafted with Excellence</div>
    </div>
    <div class="body">
        <h1 class="greeting">Welcome, {{ $user->name }}!</h1>
        <p class="subtext">We're thrilled to have you join HERD SHOP. Your account is ready — here's what you can do:</p>
        <div class="feature"><div class="feature-icon">🛍️</div><div><div class="feature-title">Shop Premium Products</div><div class="feature-desc">Browse our curated collection with exclusive deals.</div></div></div>
        <div class="feature"><div class="feature-icon">📦</div><div><div class="feature-title">Track Your Orders</div><div class="feature-desc">Real-time tracking from placement to delivery.</div></div></div>
        <div class="feature"><div class="feature-icon">❤️</div><div><div class="feature-title">Save Favorites</div><div class="feature-desc">Build your wishlist and never lose track of items you love.</div></div></div>
        <div class="feature"><div class="feature-icon">🔔</div><div><div class="feature-title">Notification Preferences</div><div class="feature-desc">Control what you hear about in your account Settings.</div></div></div>
        <a href="{{ env('FRONTEND_URL') }}/products" class="cta-btn">Start Shopping Now →</a>
    </div>
    <div class="footer">
        <div class="footer-brand">HERD SHOP</div>
        © {{ date('Y') }} HERD SHOP. All rights reserved.<br>
        You received this email because you created an account with us.
    </div>
</div>
</body>
</html>

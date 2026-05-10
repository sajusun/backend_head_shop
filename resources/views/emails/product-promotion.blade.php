<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Special Offer — HERD SHOP</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; background: #f4f4f5; color: #18181b; }
        .wrapper { max-width: 640px; margin: 40px auto; background: #fff; border-radius: 24px; overflow: hidden; box-shadow: 0 4px 40px rgba(0,0,0,0.08); }
        .header { background: linear-gradient(135deg, #18181b 0%, #3f3f46 100%); padding: 48px 40px; text-align: center; }
        .header-brand { font-size: 28px; font-weight: 900; color: #fff; letter-spacing: -1px; }
        .header-brand span { color: #f59e0b; }
        .promo-badge { display: inline-block; background: #f59e0b; color: #18181b; font-size: 11px; font-weight: 800; letter-spacing: 2px; text-transform: uppercase; padding: 6px 16px; border-radius: 100px; margin-top: 20px; }
        .body { padding: 40px; }
        .greeting { font-size: 20px; font-weight: 800; color: #18181b; margin-bottom: 8px; }
        .promo-msg { font-size: 15px; color: #71717a; line-height: 1.8; margin-bottom: 32px; background: #f9f9f9; border-radius: 14px; padding: 20px; border-left: 4px solid #f59e0b; }
        .product-card { border: 1px solid #e4e4e7; border-radius: 20px; overflow: hidden; margin-bottom: 32px; }
        .product-image { width: 100%; height: 260px; object-fit: cover; background: #f4f4f5; display: block; }
        .product-image-placeholder { width: 100%; height: 260px; background: linear-gradient(135deg, #27272a, #52525b); display: flex; align-items: center; justify-content: center; font-size: 64px; }
        .product-info { padding: 24px; }
        .product-category { font-size: 11px; font-weight: 800; letter-spacing: 2px; text-transform: uppercase; color: #a1a1aa; margin-bottom: 6px; }
        .product-name { font-size: 22px; font-weight: 900; color: #18181b; margin-bottom: 8px; }
        .product-desc { font-size: 14px; color: #71717a; line-height: 1.6; margin-bottom: 16px; }
        .product-price { font-size: 24px; font-weight: 900; color: #18181b; }
        .product-price .original { font-size: 16px; color: #a1a1aa; text-decoration: line-through; margin-left: 8px; font-weight: 400; }
        .cta-btn { display: block; text-align: center; background: #f59e0b; color: #18181b; text-decoration: none; font-size: 16px; font-weight: 900; letter-spacing: 0.5px; padding: 20px 32px; border-radius: 14px; margin: 24px 0; }
        .cta-btn:hover { background: #d97706; }
        .footer-note { text-align: center; font-size: 12px; color: #a1a1aa; line-height: 1.8; margin-bottom: 8px; }
        .footer { background: #f4f4f5; padding: 24px 40px; text-align: center; font-size: 12px; color: #a1a1aa; line-height: 1.8; }
        .footer-brand { font-size: 14px; font-weight: 900; color: #71717a; letter-spacing: 2px; margin-bottom: 6px; }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="header">
        <div style="font-size:48px; margin-bottom:16px;">🔥</div>
        <div class="header-brand">HERD <span>SHOP</span></div>
        <div class="promo-badge">Special Offer Just For You</div>
    </div>
    <div class="body">
        <h1 class="greeting">Hi {{ $recipientName }},</h1>
        <div class="promo-msg">{{ $promoMessage }}</div>

        <div class="product-card">
            @if($product->primaryImage?->image_url)
                <img src="{{ $product->primaryImage->image_url }}" alt="{{ $product->name }}" class="product-image" />
            @else
                <div class="product-image-placeholder">🛍️</div>
            @endif
            <div class="product-info">
                @if($product->category)
                <div class="product-category">{{ $product->category->name }}</div>
                @endif
                <div class="product-name">{{ $product->name }}</div>
                @if($product->description)
                <div class="product-desc">{{ Str::limit(strip_tags($product->description), 120) }}</div>
                @endif
                <div class="product-price">
                    ${{ number_format($product->offer_price ?? $product->price, 2) }}
                    @if($product->offer_price && $product->offer_price < $product->price)
                        <span class="original">${{ number_format($product->price, 2) }}</span>
                    @endif
                </div>
            </div>
        </div>

        <a href="{{ env('FRONTEND_URL') }}/products/{{ $product->slug }}" class="cta-btn">Shop Now — View Product →</a>
        <p class="footer-note">You're receiving this because you opted in to promotional emails.<br>Manage preferences in your <a href="{{ env('FRONTEND_URL') }}/settings" style="color:#71717a;">account settings</a>.</p>
    </div>
    <div class="footer">
        <div class="footer-brand">HERD SHOP</div>
        © {{ date('Y') }} HERD SHOP. All rights reserved.
    </div>
</div>
</body>
</html>

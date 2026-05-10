<!-- resources/views/welcome.blade.php -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name') }} API</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: #0f172a;
            color: #fff;
        }
        .container { text-align: center; }
        h1 { font-size: 2rem; margin-bottom: 8px; }
        p { color: #94a3b8; }
        .badge {
            display: inline-block;
            margin-top: 16px;
            padding: 4px 12px;
            background: #22c55e20;
            color: #22c55e;
            border-radius: 999px;
            font-size: 0.85rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>{{ config('app.name') }}</h1>
        <p>API is up and running</p>
        <span class="badge">● Live</span>
    </div>
</body>
</html>
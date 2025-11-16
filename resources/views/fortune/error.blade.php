<!-- 方法说明（中文）：展示支付初始化失败信息的错误页面 -->
<!-- Purpose (English): Show error message when payment initialization fails -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payment Error</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: sans-serif; padding: 2rem; }
        .msg { color: #c62828; }
        .btn { display: inline-block; padding: 0.6rem 1rem; background: #2d7dff; color: #fff; border-radius: 4px; text-decoration: none; }
    </style>
</head>
<body>
    <h3>Payment Error</h3>
    <p class="msg">{{ $message ?? 'Unable to start payment.' }}</p>
    <p>
        <a class="btn" href="{{ route('shop.checkout.cart.index') }}">Back to Cart</a>
    </p>
</body>
</html>
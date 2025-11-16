<!-- 方法说明（中文）：支付返回页（非成功时展示信息） -->
<!-- Purpose (English): Payment return page to show info when not successful -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payment Result</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: sans-serif; padding: 2rem; }
        .ok { color: #2e7d32; }
        .fail { color: #c62828; }
        .btn { display: inline-block; padding: 0.6rem 1rem; background: #2d7dff; color: #fff; border-radius: 4px; text-decoration: none; }
    </style>
</head>
<body>
    <h3>Payment Return</h3>
    @if($valid)
        <p class="ok">Signature verified.</p>
    @else
        <p class="fail">Signature invalid.</p>
    @endif

    @if(!empty($failureCode))
        <p class="fail">Failure Code: {{ $failureCode }}</p>
        @if(!empty($failureMsg))
            <p class="fail">Message: {{ $failureMsg }}</p>
        @endif
    @endif

    @if(!empty($payment))
        <p>Invoice: {{ $payment->invoice_id }}</p>
        <p>Order No: {{ $payment->order_no }}</p>
        <p>Status: {{ $payment->status }}</p>
    @endif

    <p>
        <a class="btn" href="{{ route('shop.checkout.cart.index') }}">Back to Cart</a>
    </p>
</body>
</html>
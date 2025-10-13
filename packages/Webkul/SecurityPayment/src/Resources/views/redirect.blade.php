<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ trans('security_payment::app.shop.payment.redirecting') }}</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .container {
            background: white;
            border-radius: 12px;
            padding: 40px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            text-align: center;
            max-width: 400px;
            width: 90%;
        }
        .spinner {
            width: 50px;
            height: 50px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid #667eea;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        h2 {
            color: #333;
            margin-bottom: 10px;
            font-size: 24px;
        }
        p {
            color: #666;
            margin-bottom: 20px;
            line-height: 1.5;
        }
        .security-info {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-top: 20px;
            font-size: 14px;
            color: #666;
        }
        .security-icon {
            color: #28a745;
            margin-right: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="spinner"></div>
        <h2>{{ trans('security_payment::app.shop.payment.redirecting') }}</h2>
        <p>{{ trans('security_payment::app.shop.payment.redirect_message') }}</p>
        
        <div class="security-info">
            <span class="security-icon">🔒</span>
            {{ trans('security_payment::app.shop.payment.security_notice') }}
        </div>

        <!-- 支付表单 Payment Form -->
        <form id="security_payment_form" action="{{ $paymentUrl }}" method="POST" style="display: none;">
            @foreach($formFields as $key => $value)
                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
            @endforeach
        </form>
    </div>

    <script>
        // 页面加载完成后自动提交表单
        // Auto submit form after page load
        window.onload = function() {
            setTimeout(function() {
                document.getElementById('security_payment_form').submit();
            }, 2000); // 2秒延迟，让用户看到加载动画
        };
    </script>
</body>
</html>
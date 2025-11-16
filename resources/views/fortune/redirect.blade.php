<!-- 方法说明（中文）：自动跳转到网关 URL 的重定向页面 -->
<!-- Purpose (English): Auto-redirect page to forward user to gateway URL -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Redirecting to Payment</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: sans-serif; padding: 2rem; }
        .btn { display: inline-block; padding: 0.6rem 1rem; background: #2d7dff; color: #fff; border-radius: 4px; text-decoration: none; }
    </style>
    <!-- Minimal JS to auto redirect -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var url = "{{ $url ?? '' }}";
            if (url) {
                window.location.href = url;
            }
        });
    </script>
}</head>
<body>
    <p>You will be redirected shortly. If not, click the button below.</p>
    @if(!empty($url))
        <a class="btn" href="{{ $url }}">Continue to Payment</a>
    @else
        <p>Missing payment URL.</p>
    @endif
</body>
</html>
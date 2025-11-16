@php($setting = $setting ?? null)
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FortunePay Config</title>
    <style>
        body { font-family: system-ui, -apple-system, Arial, sans-serif; padding: 24px; }
        .container { max-width: 800px; margin: 0 auto; }
        .card { border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px; }
        .grid { display: grid; grid-template-columns: 1fr 2fr; gap: 12px; }
        label { font-weight: 600; }
        input[type="text"], input[type="url"], input[type="password"] { width: 100%; padding: 8px; border: 1px solid #e5e7eb; border-radius: 6px; }
        .actions { display: flex; gap: 12px; margin-top: 16px; }
        .btn { padding: 10px 16px; border-radius: 6px; border: none; cursor: pointer; }
        .btn-primary { background: #10b981; color: white; }
        .btn-secondary { background: #f3f4f6; }
        .title { margin-bottom: 12px; }
        .alert { padding: 8px 12px; border-radius: 6px; background: #ecfccb; border: 1px solid #bef264; margin-bottom: 12px; }
    </style>
</head>
<body>
<div class="container">
    <h2 class="title">FortunePay Settings</h2>

    @if(session('success'))
        <div class="alert">{{ session('success') }}</div>
    @endif

    <div class="card">
        <form method="POST" action="{{ route('admin.fortune.config.update') }}">
            @csrf
            @method('POST')

            <div class="grid">
                <label for="merchant_no">Merchant No</label>
                <input type="text" id="merchant_no" name="merchant_no" value="{{ old('merchant_no', $setting->merchant_no ?? '') }}" />

                <label for="user_key">User Key</label>
                <input type="password" id="user_key" name="user_key" placeholder="Enter new key to update" />

                <label for="username">Username</label>
                <input type="text" id="username" name="username" value="{{ old('username', $setting->username ?? config('fortune.username')) }}" />

                <label for="bn">BN</label>
                <input type="text" id="bn" name="bn" value="{{ old('bn', $setting->bn ?? config('fortune.bn')) }}" />

                <label for="base_url">Base URL</label>
                <input type="url" id="base_url" name="base_url" value="{{ old('base_url', $setting->base_url ?? config('fortune.base_url')) }}" />

                <label for="payment_method">Payment Method</label>
                <input type="text" id="payment_method" name="payment_method" value="{{ old('payment_method', $setting->payment_method ?? config('fortune.payment_method')) }}" />

                <label for="notify_url">Notify URL</label>
                <input type="url" id="notify_url" name="notify_url" value="{{ old('notify_url', $setting->notify_url ?? '') }}" placeholder="Leave blank to use default route" />

                <label for="success_uri">Success URI</label>
                <input type="url" id="success_uri" name="success_uri" value="{{ old('success_uri', $setting->success_uri ?? '') }}" placeholder="Leave blank to use default route" />

                <label for="return_uri">Return URI</label>
                <input type="url" id="return_uri" name="return_uri" value="{{ old('return_uri', $setting->return_uri ?? '') }}" placeholder="Leave blank to use default route" />

                <label for="channel_redirect">Channel Redirect</label>
                <input type="checkbox" id="channel_redirect" name="channel_redirect" value="1" @checked(old('channel_redirect', ($setting->channel_redirect ?? config('fortune.channel_redirect')))) />

                <label for="channel_iframe">Channel Iframe</label>
                <input type="checkbox" id="channel_iframe" name="channel_iframe" value="1" @checked(old('channel_iframe', ($setting->channel_iframe ?? config('fortune.channel_iframe')))) />
            </div>

            <div class="actions">
                <button type="submit" class="btn btn-primary">Save Settings</button>
                <a class="btn btn-secondary" href="{{ url('/') }}">Back to Site</a>
                <a class="btn btn-secondary" href="{{ route('admin.fortune.payments.index') }}">View Payments</a>
            </div>
        </form>
    </div>
</div>
</body>
</html>
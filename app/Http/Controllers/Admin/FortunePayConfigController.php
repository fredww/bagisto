<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FortuneSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class FortunePayConfigController extends Controller
{
    /**
     * 显示支付配置界面
     * Purpose: Render admin page to edit FortunePay settings
     */
    public function index()
    {
        $setting = FortuneSetting::query()->latest('id')->first();
        return \view('admin.fortune.config', [
            'setting' => $setting,
        ]);
    }

    /**
     * 保存支付配置
     * Purpose: Persist settings; encrypt user key before saving
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'merchant_no' => ['nullable', 'string', 'max:255'],
            'user_key' => ['nullable', 'string', 'max:4096'],
            'username' => ['nullable', 'string', 'max:255'],
            'bn' => ['nullable', 'string', 'max:255'],
            'base_url' => ['nullable', 'url'],
            'payment_method' => ['nullable', 'string', 'max:255'],
            'notify_url' => ['nullable', 'url'],
            'success_uri' => ['nullable', 'url'],
            'return_uri' => ['nullable', 'url'],
            'channel_redirect' => ['nullable'],
            'channel_iframe' => ['nullable'],
        ]);

        $setting = FortuneSetting::query()->latest('id')->first() ?? new FortuneSetting();

        $setting->merchant_no = $validated['merchant_no'] ?? $setting->merchant_no;
        if (!empty($validated['user_key'])) {
            $setting->setUserKey($validated['user_key']);
        }
        $setting->username = $validated['username'] ?? $setting->username;
        $setting->bn = $validated['bn'] ?? $setting->bn;
        $setting->base_url = $validated['base_url'] ?? $setting->base_url;
        $setting->payment_method = $validated['payment_method'] ?? $setting->payment_method;
        $setting->notify_url = $validated['notify_url'] ?? $setting->notify_url;
        $setting->success_uri = $validated['success_uri'] ?? $setting->success_uri;
        $setting->return_uri = $validated['return_uri'] ?? $setting->return_uri;
        $setting->channel_redirect = (bool) $request->boolean('channel_redirect', $setting->channel_redirect);
        $setting->channel_iframe = (bool) $request->boolean('channel_iframe', $setting->channel_iframe);

        $setting->save();

        return redirect()->route('admin.fortune.config')->with('success', 'FortunePay settings saved');
    }
}
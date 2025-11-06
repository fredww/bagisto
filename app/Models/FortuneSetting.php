<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class FortuneSetting extends Model
{
    /**
     * 支付配置模型
     * Purpose: Persist FortunePay configuration and provide helpers for sensitive fields
     */
    protected $table = 'fortune_settings';

    protected $fillable = [
        'merchant_no',
        'user_key_encrypted',
        'username',
        'bn',
        'base_url',
        'payment_method',
        'notify_url',
        'success_uri',
        'return_uri',
        'channel_redirect',
        'channel_iframe',
    ];

    /**
     * 获取解密后的支付密钥
     * Purpose: Return decrypted user key or fallback to config env
     */
    public function getUserKey(): string
    {
        if (!empty($this->user_key_encrypted)) {
            try {
                return Crypt::decryptString($this->user_key_encrypted);
            } catch (\Throwable $e) {
                // Decryption failed; fall back to config
            }
        }

        return (string) config('fortune.user_key', '');
    }

    /**
     * 设置并加密支付密钥
     * Purpose: Encrypt and store user key into user_key_encrypted
     */
    public function setUserKey(string $plain): void
    {
        $this->user_key_encrypted = Crypt::encryptString($plain);
    }
}
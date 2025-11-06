<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 创建支付配置表
     * Purpose: Create table to store FortunePay settings configurable via admin UI
     */
    public function up(): void
    {
        Schema::create('fortune_settings', function (Blueprint $table) {
            $table->id();
            $table->string('merchant_no')->nullable();
            $table->text('user_key_encrypted')->nullable();
            $table->string('username')->nullable();
            $table->string('bn')->nullable();
            $table->string('base_url')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('notify_url')->nullable();
            $table->string('success_uri')->nullable();
            $table->string('return_uri')->nullable();
            $table->boolean('channel_redirect')->default(true);
            $table->boolean('channel_iframe')->default(false);
            $table->timestamps();
        });
    }

    /**
     * 回滚支付配置表
     * Purpose: Drop settings table if exists
     */
    public function down(): void
    {
        Schema::dropIfExists('fortune_settings');
    }
};
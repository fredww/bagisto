<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 创建支付记录表
     * Purpose: Create table to track Fortune payments lifecycle
     */
    public function up(): void
    {
        Schema::create('fortune_payments', function (Blueprint $table) {
            $table->id();
            $table->string('order_no')->index();
            $table->string('invoice_id')->index();
            $table->string('currency', 8)->nullable();
            $table->decimal('amount', 12, 2)->nullable();
            $table->string('status')->default('initiated'); // initiated|requested|pending|success|failed
            $table->string('pay_no')->nullable();
            $table->boolean('redirect')->default(true);
            $table->text('url')->nullable();
            $table->string('failure_code')->nullable();
            $table->text('failure_msg')->nullable();
            $table->string('client_ip')->nullable();
            $table->string('client_agent')->nullable();
            $table->string('client_language')->nullable();
            $table->string('bn')->nullable();
            $table->string('payment_method')->nullable();
            $table->json('gateway_response')->nullable();
            $table->timestamp('notified_at')->nullable();
            $table->timestamp('returned_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * 回滚支付记录表
     * Purpose: Drop payments table if exists
     */
    public function down(): void
    {
        Schema::dropIfExists('fortune_payments');
    }
};
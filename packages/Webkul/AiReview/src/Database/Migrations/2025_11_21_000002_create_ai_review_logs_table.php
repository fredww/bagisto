<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ai_review_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('job_id')->nullable();
            $table->unsignedBigInteger('product_id')->nullable();
            $table->string('status')->default('created');
            $table->string('message')->nullable();
            $table->unsignedSmallInteger('created_count')->default(0);
            $table->timestamps();

            $table->foreign('job_id')->references('id')->on('ai_review_jobs')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_review_logs');
    }
};
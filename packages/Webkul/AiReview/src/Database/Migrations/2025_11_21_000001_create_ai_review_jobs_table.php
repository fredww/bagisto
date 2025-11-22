<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ai_review_jobs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ai_model_id')->nullable();
            $table->unsignedInteger('total_products')->default(0);
            $table->unsignedInteger('processed_products')->default(0);
            $table->string('status')->default('pending');
            $table->json('config')->nullable();
            $table->timestamps();

            $table->foreign('ai_model_id')->references('id')->on('ai_models')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_review_jobs');
    }
};
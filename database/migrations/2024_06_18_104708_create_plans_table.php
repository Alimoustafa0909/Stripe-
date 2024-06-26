<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();; // nullable
            $table->foreignId('product_id')->references('id')->on('products')->cascadeOnDelete();
            $table->integer('amount');
            $table->integer('interval_count');
            $table->string('interval');
            $table->string('currency');
            $table->string('stripe_plan_id');
            $table->string('stripe_product_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};

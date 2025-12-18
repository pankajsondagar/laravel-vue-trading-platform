<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('buyer_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('seller_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('buy_order_id')->constrained('orders')->onDelete('cascade');
            $table->foreignId('sell_order_id')->constrained('orders')->onDelete('cascade');
            $table->string('symbol', 10);
            $table->decimal('price', 20, 8); // Execution price
            $table->decimal('amount', 20, 8); // Crypto amount traded
            $table->decimal('total_value', 20, 8); // price × amount
            $table->decimal('commission', 20, 8); // 1.5% fee
            $table->timestamps();

            // Indexes for analytics
            $table->index(['symbol', 'created_at']);
            $table->index('buyer_id');
            $table->index('seller_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trades');
    }
};
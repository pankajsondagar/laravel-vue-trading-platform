<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('symbol', 10); // BTC, ETH, etc.
            $table->enum('side', ['buy', 'sell']);
            $table->decimal('price', 20, 8); // Limit order price
            $table->decimal('amount', 20, 8); // Crypto amount
            $table->tinyInteger('status')->default(1); // 1=open, 2=filled, 3=cancelled
            
            // Track locked amounts for rollback on cancel
            $table->decimal('locked_usd', 20, 8)->nullable(); // For buy orders
            $table->decimal('locked_asset', 20, 8)->nullable(); // For sell orders
            
            $table->timestamps();

            // Indexes for performance
            $table->index(['symbol', 'side', 'status', 'price']); // For matching
            $table->index(['user_id', 'status']); // For user orders
            $table->index('created_at'); // For orderbook sorting
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
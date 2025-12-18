<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('symbol', 10); // BTC, ETH, etc.
            $table->decimal('amount', 20, 8)->default(0); // Available balance
            $table->decimal('locked_amount', 20, 8)->default(0); // Reserved for orders
            $table->timestamps();

            // Ensure one asset per symbol per user
            $table->unique(['user_id', 'symbol']);
            
            // Index for quick lookups
            $table->index(['user_id', 'symbol']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
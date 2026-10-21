<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cart_reservations', function (Blueprint $table) {
            $table->id();
            $table->char('session_hash', 64);
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('quantity');
            $table->timestamp('expires_at')->index();
            $table->timestamps();
            $table->unique(['session_hash', 'product_id']);
            $table->index(['product_id', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cart_reservations');
    }
};

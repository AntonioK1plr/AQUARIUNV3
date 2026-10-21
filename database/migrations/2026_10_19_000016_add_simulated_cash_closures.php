<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('simulated_order_payments', function (Blueprint $table) {
            $table->decimal('amount_tendered', 10, 2)->nullable();
            $table->decimal('change_amount', 10, 2)->nullable();
        });

        Schema::create('pos_closures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cashier_id')->constrained('users')->restrictOnDelete();
            $table->date('business_date');
            $table->decimal('opening_float', 10, 2)->default(0);
            $table->decimal('expected_cash', 10, 2)->default(0);
            $table->decimal('counted_cash', 10, 2)->default(0);
            $table->decimal('difference', 10, 2)->default(0);
            $table->unsignedInteger('sales_count')->default(0);
            $table->decimal('cash_sales', 10, 2)->default(0);
            $table->decimal('card_sales', 10, 2)->default(0);
            $table->decimal('transfer_sales', 10, 2)->default(0);
            $table->timestamp('closed_at');
            $table->timestamps();
            $table->unique(['cashier_id', 'business_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_closures');
        Schema::table('simulated_order_payments', function (Blueprint $table) {
            $table->dropColumn(['amount_tendered', 'change_amount']);
        });
    }
};

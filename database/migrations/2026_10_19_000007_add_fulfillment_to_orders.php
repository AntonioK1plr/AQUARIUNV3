<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['cashier_id']);
            $table->unsignedBigInteger('cashier_id')->nullable()->change();
            $table->foreign('cashier_id')->references('id')->on('users')->nullOnDelete();
            $table->string('fulfillment_type', 24)->default('mostrador');
            $table->string('fulfillment_status', 32)->default('completado');
            $table->string('pickup_code', 24)->nullable()->unique();
        });
    }

    public function down(): void
    {
        if (DB::table('orders')->whereNull('cashier_id')->exists()) {
            throw new RuntimeException('No se puede revertir: hay pedidos web sin cajero asignado.');
        }

        Schema::table('orders', function (Blueprint $table) {
            $table->dropUnique(['pickup_code']);
            $table->dropColumn(['fulfillment_type', 'fulfillment_status', 'pickup_code']);
            $table->dropForeign(['cashier_id']);
            $table->unsignedBigInteger('cashier_id')->nullable(false)->change();
            $table->foreign('cashier_id')->references('id')->on('users')->restrictOnDelete();
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stripe_payment_attempts', function (Blueprint $table) {
            if (! Schema::hasColumn('stripe_payment_attempts', 'appointment_id')) {
                $table->foreignId('appointment_id')->nullable()->after('order_id')->constrained()->restrictOnDelete();
            }
            if (! Schema::hasColumn('stripe_payment_attempts', 'expires_at')) {
                $table->timestamp('expires_at')->nullable()->index();
            }
            if (! Schema::hasColumn('stripe_payment_attempts', 'refund_reason')) {
                $table->string('refund_reason', 180)->nullable();
            }
            if (! Schema::hasColumn('stripe_payment_attempts', 'refunded_at')) {
                $table->timestamp('refunded_at')->nullable();
            }
        });

        if (Schema::hasColumn('stripe_payment_attempts', 'order_id')) {
            Schema::table('stripe_payment_attempts', function (Blueprint $table) {
                $table->foreignId('order_id')->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        Schema::table('stripe_payment_attempts', function (Blueprint $table) {
            if (Schema::hasColumn('stripe_payment_attempts', 'appointment_id')) {
                $table->dropConstrainedForeignId('appointment_id');
            }
            if (Schema::hasColumn('stripe_payment_attempts', 'expires_at')) {
                $table->dropIndex(['expires_at']);
                $table->dropColumn('expires_at');
            }
            foreach (['refund_reason', 'refunded_at'] as $column) {
                if (Schema::hasColumn('stripe_payment_attempts', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

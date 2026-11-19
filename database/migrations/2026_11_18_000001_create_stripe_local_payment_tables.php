<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stripe_payment_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('appointment_id')->nullable()->constrained()->restrictOnDelete();
            $table->uuid('client_request_key')->unique();
            $table->string('payment_intent_id', 255)->nullable()->unique();
            $table->decimal('amount', 10, 2);
            $table->char('currency', 3)->default('mxn');
            $table->string('status', 32)->default('creating')->index();
            $table->string('session_hash', 64);
            $table->string('failure_code', 120)->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->string('refund_reason', 180)->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->timestamps();
        });

        Schema::create('stripe_webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('event_id', 255)->unique();
            $table->string('event_type', 120);
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stripe_webhook_events');
        Schema::dropIfExists('stripe_payment_attempts');
    }
};

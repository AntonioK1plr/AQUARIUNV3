<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('veterinarian_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('patient_name', 120);
            $table->string('species', 120);
            $table->text('symptoms');
            $table->timestamp('scheduled_at');
            $table->string('status', 32)->default('programada')->index();
            $table->decimal('consultation_fee', 10, 2);
            $table->text('diagnosis')->nullable();
            $table->text('prescription')->nullable();
            $table->text('veterinarian_notes')->nullable();
            $table->string('cancellation_reason', 180)->nullable();
            $table->timestamps();
            $table->index(['veterinarian_id', 'scheduled_at']);
            $table->index(['client_id', 'scheduled_at']);
        });

        Schema::create('simulated_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->unique()->constrained()->restrictOnDelete();
            $table->string('reference', 64)->unique();
            $table->decimal('amount', 10, 2);
            $table->string('method', 32)->default('simulado_local');
            $table->string('status', 32)->default('cargo_simulado');
            $table->string('refund_reason', 180)->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('simulated_payments');
        Schema::dropIfExists('appointments');
    }
};

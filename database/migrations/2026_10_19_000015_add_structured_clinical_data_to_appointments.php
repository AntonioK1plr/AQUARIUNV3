<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->json('clinical_data')->nullable();
            $table->char('clinical_record_hash', 64)->nullable();
            $table->timestamp('clinical_recorded_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn(['clinical_data', 'clinical_record_hash', 'clinical_recorded_at']);
        });
    }
};

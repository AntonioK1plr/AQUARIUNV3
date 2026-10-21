<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('care_responsibility_accepted_at')->nullable();
            $table->timestamp('compatibility_acknowledged_at')->nullable();
            $table->json('compatibility_warnings')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['care_responsibility_accepted_at', 'compatibility_acknowledged_at', 'compatibility_warnings']);
        });
    }
};

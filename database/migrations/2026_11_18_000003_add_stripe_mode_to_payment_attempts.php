<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('stripe_payment_attempts', 'stripe_mode')) {
            Schema::table('stripe_payment_attempts', function (Blueprint $table) {
                $table->string('stripe_mode', 8)->default('test')->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('stripe_payment_attempts', 'stripe_mode')) {
            Schema::table('stripe_payment_attempts', function (Blueprint $table) {
                $table->dropIndex(['stripe_mode']);
                $table->dropColumn('stripe_mode');
            });
        }
    }
};

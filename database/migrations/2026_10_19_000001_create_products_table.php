<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('sku', 64)->unique();
            $table->string('name', 160);
            $table->text('description')->nullable();
            $table->string('category', 40)->index();
            $table->string('water_type', 20)->nullable()->index();
            $table->string('brand', 100)->nullable();
            $table->string('origin', 100)->nullable();
            $table->string('temperament', 60)->nullable();
            $table->decimal('min_ph', 3, 1)->nullable();
            $table->decimal('max_ph', 3, 1)->nullable();
            $table->decimal('min_temp_c', 4, 1)->nullable();
            $table->decimal('max_temp_c', 4, 1)->nullable();
            $table->decimal('price', 10, 2);
            $table->unsignedInteger('stock')->default(0);
            $table->string('image_path', 255)->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('is_featured')->default(false)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};

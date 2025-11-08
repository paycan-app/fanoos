<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('title');
            $table->string('category')->nullable();
            $table->string('subcategory')->nullable();
            $table->decimal('price', 10, 2);
            $table->string('brand')->nullable();
            $table->string('sku')->nullable()->index();
            $table->json('meta')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
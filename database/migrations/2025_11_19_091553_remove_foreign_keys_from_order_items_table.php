<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // SQLite doesn't support dropping foreign keys directly
        // We need to recreate the table without foreign keys
        Schema::rename('order_items', 'order_items_old');

        Schema::create('order_items', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('order_id')->nullable();
            $table->string('product_id')->nullable();
            $table->unsignedInteger('quantity')->nullable();
            $table->decimal('unit_price', 10, 2)->nullable();
            $table->decimal('price', 12, 2)->nullable();
            $table->dateTimeTz('created_at')->nullable();

            // Keep indexes but remove foreign keys
            $table->index(['order_id', 'product_id'], 'oi_order_product_idx');
            $table->index('created_at', 'oi_created_at_idx');
        });

        // Copy data from old table
        DB::statement('INSERT INTO order_items SELECT * FROM order_items_old');

        // Drop old table
        Schema::dropIfExists('order_items_old');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::rename('order_items', 'order_items_old');

        Schema::create('order_items', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('order_id');
            $table->string('product_id');
            $table->unsignedInteger('quantity');
            $table->decimal('unit_price', 10, 2);
            $table->decimal('price', 12, 2);
            $table->dateTimeTz('created_at');

            $table->foreign('order_id')
                ->references('id')->on('orders')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreign('product_id')
                ->references('id')->on('products')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->index(['order_id', 'product_id']);
            $table->index('created_at');
        });

        DB::statement('INSERT INTO order_items SELECT * FROM order_items_old');
        Schema::dropIfExists('order_items_old');
    }
};

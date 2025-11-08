<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('first_name');
            $table->string('last_name')->nullable();
            $table->dateTimeTz('created_at');
            $table->string('email')->nullable()->index();
            $table->string('phone')->nullable()->index();
            $table->string('country')->nullable();
            $table->string('state')->nullable();
            $table->string('city')->nullable();
            $table->string('region')->nullable();
            $table->dateTimeTz('birthday')->nullable();
            $table->string('gender')->nullable();
            $table->string('segment')->nullable()->index();
            $table->json('labels')->nullable();
            $table->string('channel')->nullable();
            $table->json('meta')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
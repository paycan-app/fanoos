<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('campaign_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('campaign_send_id')->constrained('campaign_sends')->cascadeOnDelete();
            $table->enum('event_type', ['opened', 'clicked', 'unsubscribed', 'complained']);
            $table->json('event_data')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->index(['campaign_send_id', 'event_type']);
            $table->index('occurred_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campaign_events');
    }
};

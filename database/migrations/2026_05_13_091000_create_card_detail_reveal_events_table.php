<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('card_detail_reveal_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('virtual_card_id')->constrained()->cascadeOnDelete();
            $table->string('provider');
            $table->boolean('successful')->default(false);
            $table->string('ip_address')->nullable();
            $table->string('user_agent_hash')->nullable();
            $table->timestamp('revealed_at');
            $table->timestamps();

            $table->index(['user_id', 'virtual_card_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('card_detail_reveal_events');
    }
};

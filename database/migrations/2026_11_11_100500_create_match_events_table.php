<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('match_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('match_id')->constrained()->cascadeOnDelete();
            $table->foreignId('player_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->unsignedTinyInteger('minute');
            $table->foreignId('assist_player_id')->nullable()->constrained('players')->nullOnDelete();
            $table->foreignId('secondary_player_id')->nullable()->constrained('players')->nullOnDelete();
            $table->timestamps();

            $table->index(['match_id', 'minute']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('match_events');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('round');
            $table->unsignedInteger('bracket_slot')->nullable();
            $table->string('group_name')->nullable();
            $table->foreignId('home_team_id')->nullable()->constrained('teams')->nullOnDelete();
            $table->foreignId('away_team_id')->nullable()->constrained('teams')->nullOnDelete();
            $table->foreignId('referee_id')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('kickoff_at')->nullable();
            $table->string('venue')->nullable();
            $table->string('status')->default('scheduled');
            $table->unsignedTinyInteger('home_score')->default(0);
            $table->unsignedTinyInteger('away_score')->default(0);
            $table->boolean('is_bye')->default(false);
            $table->foreignId('next_match_id')->nullable()->constrained('matches')->nullOnDelete();
            $table->string('next_slot')->nullable();
            $table->timestamps();

            $table->index(['tournament_id', 'round']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('matches');
    }
};

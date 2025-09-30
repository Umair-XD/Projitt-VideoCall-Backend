<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ai_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meeting_id')->constrained('meetings')->cascadeOnDelete();
            $table->longText('transcript_text');
            $table->json('key_points')->nullable();
            $table->string('sentiment')->default('neutral'); // positive|neutral|negative
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_notes');
    }
};

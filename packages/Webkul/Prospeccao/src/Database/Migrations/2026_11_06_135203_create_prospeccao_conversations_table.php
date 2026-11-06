<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prospeccao_conversations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('property_id')
                ->constrained('prospeccao_properties')
                ->cascadeOnDelete();

            $table->string('channel', 20);
            $table->string('status', 30)->default('active');
            $table->string('subject')->nullable();

            $table->timestamp('last_message_at')->nullable();

            // AI conversation analysis
            $table->unsignedTinyInteger('temperature_score')->nullable();
            $table->string('temperature_label', 30)->nullable();
            $table->text('temperature_reason')->nullable();
            $table->json('temperature_factors')->nullable();
            $table->timestamp('temperature_updated_at')->nullable();

            $table->longText('ai_summary')->nullable();
            $table->timestamp('ai_summary_updated_at')->nullable();

            $table->timestamps();

            $table->unique(
                ['property_id', 'channel'],
                'prospeccao_conversations_property_channel_unique'
            );

            $table->index('status');
            $table->index('last_message_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prospeccao_conversations');
    }
};

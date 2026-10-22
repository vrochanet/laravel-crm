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
        Schema::create('fsbo_contact_logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('property_id')
                ->constrained('fsbo_properties')
                ->cascadeOnDelete();

            $table->foreignId('template_id')
                ->nullable()
                ->constrained('fsbo_communication_templates')
                ->nullOnDelete();

            $table->unsignedInteger('user_id')->nullable();
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->string('channel'); // whatsapp, email
            $table->string('subject')->nullable();
            $table->text('body'); // cópia estática do texto preparado
            $table->timestamps();

            $table->index(['property_id', 'created_at']);
            $table->index('channel');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fsbo_contact_logs');
    }
};

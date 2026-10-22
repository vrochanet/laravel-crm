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
        Schema::create('fsbo_communication_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('stage');
            $table->string('channel'); // whatsapp, email, both
            $table->string('subject')->nullable();
            $table->text('body');
            $table->boolean('is_active')->default(1);
            $table->timestamps();

            $table->index(['is_active', 'channel']);
            $table->index('stage');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fsbo_communication_templates');
    }
};

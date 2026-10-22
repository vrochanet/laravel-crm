
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prospeccao_contact_logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('property_id')
                ->constrained('prospeccao_properties')
                ->cascadeOnDelete();

            $table->foreignId('template_id')
                ->nullable()
                ->constrained('prospeccao_communication_templates')
                ->nullOnDelete();

            $table->unsignedInteger('user_id')->nullable();
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->string('channel'); // whatsapp, email
            $table->string('subject')->nullable();
            $table->text('body');
            $table->timestamps();

            $table->index(['property_id', 'created_at']);
            $table->index('channel');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prospeccao_contact_logs');
    }
};

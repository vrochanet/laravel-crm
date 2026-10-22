<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('prospeccao_properties', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('person_id');
            $table->foreign('person_id')
                ->references('id')
                ->on('persons')
                ->cascadeOnDelete();

            $table->unsignedInteger('lead_id')->nullable();
            $table->foreign('lead_id')
                ->references('id')
                ->on('leads')
                ->nullOnDelete();
            $table->string('property_type');
            $table->string('locality');
            $table->decimal('asking_price', 12, 2)->nullable();
            $table->text('listing_url')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prospeccao_properties');
    }
};

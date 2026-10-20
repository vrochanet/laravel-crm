<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fsbo_properties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')
                ->constrained('fsbo_owners')
                ->cascadeOnDelete();
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
        Schema::dropIfExists('fsbo_properties');
    }
};

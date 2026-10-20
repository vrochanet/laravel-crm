<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fsbo_properties', function (Blueprint $table) {
            $table->dropForeign(['owner_id']);
            $table->dropColumn('owner_id');

            $table->unsignedInteger('person_id')->after('id');

            $table->foreign('person_id')
                ->references('id')
                ->on('persons')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('fsbo_properties', function (Blueprint $table) {
            $table->dropForeign(['person_id']);
            $table->dropColumn('person_id');

            $table->unsignedBigInteger('owner_id')->after('id');

            $table->foreign('owner_id')
                ->references('id')
                ->on('fsbo_owners')
                ->cascadeOnDelete();
        });
    }
};
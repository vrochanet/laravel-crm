<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::rename('fsbo_properties', 'prospeccao_properties');
        Schema::rename('fsbo_communication_templates', 'prospeccao_communication_templates');
        Schema::rename('fsbo_contact_logs', 'prospeccao_contact_logs');

        Schema::table('prospeccao_contact_logs', function (Blueprint $table) {
            $table->dropForeign('fsbo_contact_logs_property_id_foreign');
            $table->dropForeign('fsbo_contact_logs_template_id_foreign');
        });

        Schema::table('prospeccao_properties', function (Blueprint $table) {
            $table->dropForeign('fsbo_properties_person_id_foreign');
            $table->dropForeign('fsbo_properties_lead_id_foreign');
        });

        Schema::table('prospeccao_properties', function (Blueprint $table) {
            $table->foreign('person_id', 'prospeccao_properties_person_id_foreign')
                ->references('id')->on('persons')->cascadeOnDelete();

            $table->foreign('lead_id', 'prospeccao_properties_lead_id_foreign')
                ->references('id')->on('leads')->cascadeOnDelete();
        });

        Schema::table('prospeccao_contact_logs', function (Blueprint $table) {
            $table->foreign('property_id', 'prospeccao_contact_logs_property_id_foreign')
                ->references('id')->on('prospeccao_properties')->cascadeOnDelete();

            $table->foreign('template_id', 'prospeccao_contact_logs_template_id_foreign')
                ->references('id')->on('prospeccao_communication_templates')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('prospeccao_contact_logs', function (Blueprint $table) {
            $table->dropForeign('prospeccao_contact_logs_property_id_foreign');
            $table->dropForeign('prospeccao_contact_logs_template_id_foreign');
        });

        Schema::table('prospeccao_properties', function (Blueprint $table) {
            $table->dropForeign('prospeccao_properties_person_id_foreign');
            $table->dropForeign('prospeccao_properties_lead_id_foreign');
        });

        Schema::rename('prospeccao_contact_logs', 'fsbo_contact_logs');
        Schema::rename('prospeccao_communication_templates', 'fsbo_communication_templates');
        Schema::rename('prospeccao_properties', 'fsbo_properties');
    }
};

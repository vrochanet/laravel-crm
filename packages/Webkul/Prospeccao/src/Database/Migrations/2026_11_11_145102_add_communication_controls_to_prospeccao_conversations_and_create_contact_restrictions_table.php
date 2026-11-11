
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prospeccao_conversations', function (Blueprint $table) {
            // Communication rule overrides
            $table->string('mode_override', 20)->nullable();
            $table->boolean('approval_required_override')->nullable();
            $table->unsignedInteger('min_interval_hours_override')->nullable();
            $table->unsignedInteger('follow_up_interval_days_override')->nullable();
            $table->unsignedInteger('max_follow_ups_override')->nullable();
            $table->time('start_time_override')->nullable();
            $table->time('end_time_override')->nullable();

            // Pause management
            $table->timestamp('paused_at')->nullable();
            $table->timestamp('resume_at')->nullable();
            $table->string('pause_reason')->nullable();

            $table->index('resume_at');
        });

        Schema::create('prospeccao_contact_restrictions', function (Blueprint $table) {
            $table->id();

            $table->unsignedInteger('person_id');

            // "all" blocks every channel; otherwise the restriction
            // applies only to the selected communication channel.
            $table->string('channel', 20)->default('all');

            $table->string('reason')->nullable();
            $table->timestamp('blocked_at');
            $table->unsignedInteger('blocked_by')->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->foreign('person_id', 'prospeccao_restrictions_person_fk')
                ->references('id')
                ->on('persons')
                ->cascadeOnDelete();

            $table->foreign('blocked_by', 'prospeccao_restrictions_user_fk')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->index(
                ['person_id', 'channel', 'is_active'],
                'prospeccao_restrictions_person_channel_active_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prospeccao_contact_restrictions');

        Schema::table('prospeccao_conversations', function (Blueprint $table) {
            $table->dropIndex(['resume_at']);

            $table->dropColumn([
                'mode_override',
                'approval_required_override',
                'min_interval_hours_override',
                'follow_up_interval_days_override',
                'max_follow_ups_override',
                'start_time_override',
                'end_time_override',
                'paused_at',
                'resume_at',
                'pause_reason',
            ]);
        });
    }
};

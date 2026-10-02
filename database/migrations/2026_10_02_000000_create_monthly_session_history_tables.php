<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('monthly_record_snapshots', function (Blueprint $table): void {
            if (! Schema::hasColumn('monthly_record_snapshots', 'session_generated')) {
                $table->boolean('session_generated')->default(false);
            }
        });

        Schema::create('monthly_movement_sessions', function (Blueprint $table): void {
            $table->id();
            $table->string('active_key')->nullable()->unique();
            $table->string('status', 20)->default('active');
            $table->string('started_by', 255)->nullable();
            $table->timestamp('started_at');
            $table->string('completed_by', 255)->nullable();
            $table->timestamp('completed_at')->nullable();
        });

        Schema::create('monthly_session_movements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('session_id')->constrained('monthly_movement_sessions')->cascadeOnDelete();
            $table->unsignedInteger('record_id')->nullable()->index();
            $table->string('action', 20);
            $table->string('full_name', 150);
            $table->string('registry', 100)->nullable();
            $table->string('gender', 20)->nullable();
            $table->string('kapisanan', 50)->nullable();
            $table->date('date_of_baptism')->nullable();
            $table->string('evangelist', 150)->nullable();
            $table->string('status', 50)->nullable();
            $table->string('monthly_code', 50)->nullable();
            $table->string('previous_purok', 50)->nullable();
            $table->string('previous_grupo', 50)->nullable();
            $table->string('destination_description', 255)->nullable();
            $table->json('previous_values');
            $table->json('next_values');
            $table->text('description')->nullable();
            $table->string('changed_by', 255)->nullable();
            $table->timestamp('changed_at');
            $table->index(['session_id', 'changed_at']);
        });

        Schema::create('permanent_history', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('session_id')
                ->nullable()
                ->constrained('monthly_movement_sessions')
                ->nullOnDelete();
            $table->unsignedInteger('record_id')->nullable()->index();
            $table->string('action', 20);
            $table->string('full_name', 150);
            $table->string('registry', 100)->nullable();
            $table->string('gender', 20)->nullable();
            $table->string('kapisanan', 50)->nullable();
            $table->string('status', 50)->nullable();
            $table->string('monthly_code', 50)->nullable();
            $table->string('destination_description', 255)->nullable();
            $table->text('description')->nullable();
            $table->string('changed_by', 255)->nullable();
            $table->timestamp('changed_at');
            $table->date('date_of_baptism')->nullable();
            $table->string('evangelist', 150)->nullable();
            $table->json('previous_values')->nullable();
            $table->json('next_values')->nullable();
            $table->index(['session_id', 'changed_at']);
        });

        Schema::create('system_maintenance_audits', function (Blueprint $table): void {
            $table->id();
            $table->string('action', 50);
            $table->unsignedInteger('affected_count')->default(0);
            $table->string('changed_by', 255)->nullable();
            $table->timestamp('changed_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_maintenance_audits');
        Schema::dropIfExists('permanent_history');
        Schema::dropIfExists('monthly_session_movements');
        Schema::dropIfExists('monthly_movement_sessions');
        if (Schema::hasColumn('monthly_record_snapshots', 'session_generated')) {
            Schema::table('monthly_record_snapshots', function (Blueprint $table): void {
                $table->dropColumn('session_generated');
            });
        }
    }
};

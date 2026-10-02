<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monthly_session_activities', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('session_id')->constrained('monthly_movement_sessions')->cascadeOnDelete();
            $table->foreignId('session_movement_id')->nullable()
                ->constrained('monthly_session_movements')->cascadeOnDelete();
            $table->unsignedInteger('record_id')->nullable()->index();
            $table->string('action', 20);
            $table->string('direction', 12);
            $table->string('full_name', 150);
            $table->string('registry', 100)->nullable();
            $table->string('gender', 20)->nullable();
            $table->string('kapisanan', 50)->nullable();
            $table->date('date_of_baptism')->nullable();
            $table->string('evangelist', 150)->nullable();
            $table->string('status', 20);
            $table->string('monthly_code', 50);
            $table->string('purok', 50)->nullable();
            $table->string('grupo', 50)->nullable();
            $table->text('description')->nullable();
            $table->string('changed_by', 255)->nullable();
            $table->timestamp('changed_at');
            $table->index(['session_id', 'changed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monthly_session_activities');
    }
};

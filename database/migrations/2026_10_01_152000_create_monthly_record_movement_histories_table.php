<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monthly_record_movement_histories', function (Blueprint $table): void {
            $table->id();
            $table->integer('record_id')->nullable()->index();
            $table->string('full_name', 150);
            $table->string('registry', 100)->nullable();
            $table->string('purok', 50)->nullable();
            $table->string('grupo', 50)->nullable();
            $table->string('status', 50);
            $table->string('monthly_code', 50);
            $table->string('destination_purok', 50)->nullable();
            $table->string('destination_grupo', 50)->nullable();
            $table->string('destination_status', 50)->nullable();
            $table->string('changed_by', 255)->nullable();
            $table->timestamp('changed_at');
            $table->index(['record_id', 'changed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monthly_record_movement_histories');
    }
};

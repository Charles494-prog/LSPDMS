<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('global_variables', function (Blueprint $table) {
            $table->id();
            $table->string('locale')->nullable();
            $table->string('s_number')->nullable();
            $table->string('pastor_destinado')->nullable();
            $table->string('ksp_pd')->nullable();
            $table->string('kalihim_ms')->nullable();
            $table->string('kalihim_uwp')->nullable();
            $table->date('baptism_requirement')->nullable();
            $table->unsignedInteger('entry_years_requirement')->nullable();
            $table->boolean('dateofbaptism_requirement_boolean')->default(false);
            $table->boolean('dateofentryrequirement_boolean')->default(false);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('global_variables');
    }
};

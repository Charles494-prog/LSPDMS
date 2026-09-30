<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('puroks', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('purok_number')->unique();
            $table->string('purok_name')->nullable();
            $table->foreignId('secretary')->nullable()->constrained('login_user')->nullOnDelete();
            $table->foreignId('manggagawa')->nullable()->constrained('login_user')->nullOnDelete();
            $table->string('ministerial_id', 50)->nullable();
            $table->unsignedInteger('grupo_start');
            $table->unsignedInteger('grupo_end');
            $table->boolean('full_purok')->default(false);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('puroks');
    }
};

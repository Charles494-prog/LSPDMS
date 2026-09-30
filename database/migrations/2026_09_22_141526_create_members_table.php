<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('members', function (Blueprint $table) {
            $table->id(); // ID - auto-incrementing primary key
            $table->string('full_name');
            $table->string('registry')->nullable();
            $table->string('purok')->nullable();
            $table->string('grupo')->nullable();
            $table->string('kapisanan')->nullable();
            $table->date('date_of_baptism')->nullable();
            $table->string('evangelist')->nullable();
            $table->date('date_of_entry')->nullable();
            $table->boolean('r2_10_eligibility')->default(false);
            $table->string('kalagayan')->nullable();
            $table->boolean('r2_10_endorsement')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('members');
    }
};
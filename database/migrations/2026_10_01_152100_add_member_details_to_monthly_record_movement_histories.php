<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('monthly_record_movement_histories', function (Blueprint $table): void {
            $table->string('gender', 20)->nullable();
            $table->string('kapisanan', 50)->nullable();
            $table->date('date_of_baptism')->nullable();
            $table->string('evangelist', 150)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('monthly_record_movement_histories', function (Blueprint $table): void {
            $table->dropColumn([
                'gender',
                'kapisanan',
                'date_of_baptism',
                'evangelist',
            ]);
        });
    }
};

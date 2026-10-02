<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('monthly_session_activities', function (Blueprint $table): void {
            $table->json('previous_values')->nullable();
            $table->json('next_values')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('monthly_session_activities', function (Blueprint $table): void {
            $table->dropColumn(['previous_values', 'next_values']);
        });
    }
};

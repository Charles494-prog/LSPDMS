<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('r4_01_snapshots', function (Blueprint $table): void {
            $table->foreignId('session_id')
                ->nullable()
                ->unique()
                ->constrained('monthly_movement_sessions')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('r4_01_snapshots', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('session_id');
        });
    }
};

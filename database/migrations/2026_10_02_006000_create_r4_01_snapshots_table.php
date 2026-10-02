<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('r4_01_snapshots', function (Blueprint $table): void {
            $table->id();
            $table->date('period_month');
            $table->string('kalagayan', 3);
            $table->string('purok_selection', 30);
            $table->string('format', 10);
            $table->string('file_path');
            $table->string('file_name');
            $table->string('generated_by')->nullable();
            $table->timestamps();
            $table->index(['period_month', 'kalagayan']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('r4_01_snapshots');
    }
};

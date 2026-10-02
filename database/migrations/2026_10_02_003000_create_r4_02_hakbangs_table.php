<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('r4_02_hakbangs', function (Blueprint $table): void {
            $table->id();
            $table->string('status', 3);
            $table->string('code', 30);
            $table->text('description');
            $table->timestamps();
            $table->unique(['status', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('r4_02_hakbangs');
    }
};

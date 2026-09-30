<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('secretary_todos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('login_user')->cascadeOnDelete();
            $table->string('scope', 10)->index();
            $table->string('title', 500);
            $table->date('deadline')->nullable();
            $table->timestamp('completed_at')->nullable()->index();
            $table->timestamps();
            $table->index(['scope', 'completed_at', 'deadline']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('secretary_todos');
    }
};

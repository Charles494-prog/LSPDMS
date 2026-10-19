<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('record_audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedInteger('record_id')->nullable();
            $table->string('record_name')->nullable();
            $table->string('actor_email')->nullable();
            $table->string('action', 20);
            $table->text('summary');
            $table->json('changes');
            $table->timestamp('created_at');
            $table->index('created_at');
            $table->index(['record_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('record_audit_logs');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('login_user')) {
            return;
        }

        Schema::create('login_user', function (Blueprint $table) {
            $table->id();
            $table->string('gmail');
            $table->string('password');
            $table->string('tungkulin')->nullable();
            $table->boolean('is_verified')->default(false);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('login_user');
    }
};

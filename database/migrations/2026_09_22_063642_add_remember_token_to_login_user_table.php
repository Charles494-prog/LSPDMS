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
    Schema::table('login_user', function (Blueprint $table) {
        $table->string('remember_token', 100)->nullable();
        $table->timestamp('remember_token_expires_at')->nullable();
    });
}

public function down(): void
{
    Schema::table('login_user', function (Blueprint $table) {
        $table->dropColumn(['remember_token', 'remember_token_expires_at']);
    });
}
};

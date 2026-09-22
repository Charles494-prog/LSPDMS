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
        $table->string('first_name')->nullable();
        $table->string('last_name')->nullable();
        $table->string('gender')->nullable();
        $table->string('profile_picture')->nullable();
        $table->boolean('dark_mode')->default(false);
    });
}

public function down(): void
{
    Schema::table('login_user', function (Blueprint $table) {
        $table->dropColumn(['first_name', 'last_name', 'gender', 'profile_picture', 'dark_mode']);
    });
}
};

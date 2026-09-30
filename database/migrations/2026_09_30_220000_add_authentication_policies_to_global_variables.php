<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('global_variables', function (Blueprint $table): void {
            if (! Schema::hasColumn('global_variables', 'require_otp')) {
                $table->boolean('require_otp')->default(true);
            }
            if (! Schema::hasColumn('global_variables', 'allow_forgot_password')) {
                $table->boolean('allow_forgot_password')->default(true);
            }
        });

        DB::table('login_user')
            ->whereRaw('LOWER(TRIM(tungkulin)) = ?', ['secretary head'])
            ->update(['tungkulin' => 'Head Secretary']);
    }

    public function down(): void
    {
        Schema::table('global_variables', function (Blueprint $table): void {
            $columns = array_values(array_filter(
                ['require_otp', 'allow_forgot_password'],
                fn (string $column): bool => Schema::hasColumn('global_variables', $column)
            ));
            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};

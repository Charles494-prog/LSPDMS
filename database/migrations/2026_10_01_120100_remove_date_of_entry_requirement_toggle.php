<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('global_variables', 'dateofentryrequirement_boolean')) {
            Schema::table('global_variables', function (Blueprint $table): void {
                $table->dropColumn('dateofentryrequirement_boolean');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('global_variables', 'dateofentryrequirement_boolean')) {
            Schema::table('global_variables', function (Blueprint $table): void {
                $table->boolean('dateofentryrequirement_boolean')->default(false);
            });
        }
    }
};

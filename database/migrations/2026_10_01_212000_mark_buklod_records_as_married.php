<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumns('records', ['kapisanan', 'is_married'])) {
            return;
        }

        DB::table('records')
            ->whereRaw("LOWER(TRIM(kapisanan)) IN ('b', 'buklod')")
            ->update(['is_married' => true]);
    }

    public function down(): void
    {
        // Do not revert marital data; it may have been corrected independently after migration.
    }
};

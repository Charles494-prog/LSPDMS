<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('records', 'is_married')) {
            return;
        }

        DB::table('records')
            ->whereNull('is_married')
            ->update(['is_married' => false]);

        Schema::table('records', function (Blueprint $table): void {
            $table->boolean('is_married')->default(false)->nullable(false)->change();
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('records', 'is_married')) {
            Schema::table('records', function (Blueprint $table): void {
                $table->boolean('is_married')->nullable()->default(null)->change();
            });
        }
    }
};

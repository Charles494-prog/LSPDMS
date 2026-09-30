<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('puroks')) {
            throw new RuntimeException('The puroks table must exist before its schema can be updated.');
        }

        if (! Schema::hasColumn('puroks', 'purok_number')) {
            throw new RuntimeException('The puroks table must contain a purok_number column.');
        }

        if (! Schema::hasColumn('puroks', 'purok_name')) {
            Schema::table('puroks', function (Blueprint $table) {
                $table->string('purok_name')->nullable()->after('purok_number');
            });
        }

        $indexes = Schema::getIndexes('puroks');
        $purokNumberIsPrimary = collect($indexes)->contains(
            fn (array $index): bool => $index['primary'] && $index['columns'] === ['purok_number']
        );
        $hasId = Schema::hasColumn('puroks', 'id');

        if ($purokNumberIsPrimary && ! $hasId) {
            Schema::table('puroks', function (Blueprint $table) {
                $table->dropPrimary();
            });
        }

        if (! $hasId) {
            Schema::table('puroks', function (Blueprint $table) {
                $table->id()->first();
            });
        }

        $indexes = Schema::getIndexes('puroks');
        $purokNumberIsUnique = collect($indexes)->contains(
            fn (array $index): bool => $index['unique']
                && ! $index['primary']
                && $index['columns'] === ['purok_number']
        );

        if (! $purokNumberIsUnique) {
            Schema::table('puroks', function (Blueprint $table) {
                $table->unique('purok_number');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('puroks')) {
            return;
        }

        $indexes = Schema::getIndexes('puroks');
        $purokNumberIsUnique = collect($indexes)->contains(
            fn (array $index): bool => $index['unique']
                && ! $index['primary']
                && $index['columns'] === ['purok_number']
        );

        if ($purokNumberIsUnique) {
            Schema::table('puroks', function (Blueprint $table) {
                $table->dropUnique(['purok_number']);
            });
        }

        $hasId = Schema::hasColumn('puroks', 'id');

        if ($hasId) {
            Schema::table('puroks', function (Blueprint $table) {
                $table->dropPrimary();
            });
            Schema::table('puroks', function (Blueprint $table) {
                $table->dropColumn('id');
                $table->primary('purok_number');
            });
        }

        if (Schema::hasColumn('puroks', 'purok_name')) {
            Schema::table('puroks', function (Blueprint $table) {
                $table->dropColumn('purok_name');
            });
        }
    }
};

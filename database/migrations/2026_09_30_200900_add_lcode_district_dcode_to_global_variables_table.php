<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $columns = [
            'lcode' => fn (Blueprint $table) => $table->string('lcode')->nullable()->after('locale'),
            'district' => fn (Blueprint $table) => $table->string('district')->nullable()->after('lcode'),
            'dcode' => fn (Blueprint $table) => $table->string('dcode')->nullable()->after('district'),
        ];

        foreach ($columns as $column => $addColumn) {
            if (! Schema::hasColumn('global_variables', $column)) {
                Schema::table('global_variables', $addColumn);
            }
        }
    }

    public function down(): void
    {
        $columns = array_values(array_filter(
            ['lcode', 'district', 'dcode'],
            fn (string $column): bool => Schema::hasColumn('global_variables', $column)
        ));

        if ($columns !== []) {
            Schema::table('global_variables', function (Blueprint $table) use ($columns) {
                $table->dropColumn($columns);
            });
        }
    }
};

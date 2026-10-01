<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $uniqueNumberIndex = collect(Schema::getIndexes('puroks'))->first(
            fn (array $index): bool => $index['unique']
                && ! $index['primary']
                && $index['columns'] === ['purok_number']
        );

        if ($uniqueNumberIndex !== null) {
            Schema::table('puroks', function (Blueprint $table) use ($uniqueNumberIndex): void {
                $table->dropUnique($uniqueNumberIndex['name']);
            });
        }
    }

    public function down(): void
    {
        // Shared header numbers cannot be safely constrained to be unique again.
    }
};

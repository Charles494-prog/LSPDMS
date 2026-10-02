<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('global_variables', 'head_lsp')) {
            Schema::table('global_variables', function (Blueprint $table): void {
                $table->string('head_lsp')->nullable()->after('ksp_pd');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('global_variables', 'head_lsp')) {
            Schema::table('global_variables', function (Blueprint $table): void {
                $table->dropColumn('head_lsp');
            });
        }
    }
};

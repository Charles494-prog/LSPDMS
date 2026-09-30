<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('records')) {
            Schema::create('records', function (Blueprint $table) {
                $table->increments('id');
                $table->string('full_name', 150)->nullable();
                $table->string('registry', 100)->nullable();
                $table->string('gender', 20)->nullable();
                $table->string('purok', 50)->nullable();
                $table->string('grupo', 50)->nullable();
                $table->string('kapisanan', 50)->nullable();
                $table->date('date_of_baptism')->nullable();
                $table->string('evangelist', 150)->nullable();
                $table->date('date_of_entry')->nullable();
                $table->string('r2_10_eligibility', 50)->nullable();
                $table->string('status', 50)->nullable();
                $table->string('tarheta_status', 50)->nullable();
                $table->string('monthly_code', 50)->nullable();
                $table->date('date_of_birth')->nullable();
                $table->string('filing_status', 50)->nullable();
                $table->string('r2_10_endorsement', 50)->nullable();
                $table->longText('reason_of_delistment')->nullable();
                $table->longText('log_section')->nullable();
            });
        } else {
            $requiredColumns = [
                'id',
                'full_name',
                'registry',
                'gender',
                'purok',
                'grupo',
                'kapisanan',
                'date_of_baptism',
                'evangelist',
                'date_of_entry',
                'r2_10_eligibility',
                'status',
                'tarheta_status',
                'monthly_code',
                'date_of_birth',
                'filing_status',
                'r2_10_endorsement',
                'reason_of_delistment',
                'log_section',
            ];

            if (! Schema::hasColumns('records', $requiredColumns)) {
                throw new RuntimeException('The existing records table does not match the expected records schema.');
            }
        }

        if (Schema::hasTable('members')) {
            if (DB::table('members')->exists()) {
                throw new RuntimeException('The members table contains data. Back it up or migrate those rows before removing the table.');
            }

            Schema::drop('members');
        }

        if (Schema::hasTable('puroks')) {
            $timestamps = array_values(array_filter(
                ['created_at', 'updated_at'],
                fn (string $column): bool => Schema::hasColumn('puroks', $column)
            ));

            if ($timestamps !== []) {
                Schema::table('puroks', function (Blueprint $table) use ($timestamps) {
                    $table->dropColumn($timestamps);
                });
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('members')) {
            Schema::create('members', function (Blueprint $table) {
                $table->id();
                $table->string('full_name');
                $table->string('registry')->nullable();
                $table->string('purok')->nullable();
                $table->string('grupo')->nullable();
                $table->string('kapisanan')->nullable();
                $table->date('date_of_baptism')->nullable();
                $table->string('evangelist')->nullable();
                $table->date('date_of_entry')->nullable();
                $table->boolean('r2_10_eligibility')->default(false);
                $table->string('kalagayan')->nullable();
                $table->boolean('r2_10_endorsement')->default(false);
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('puroks')) {
            $timestamps = array_values(array_filter(
                ['created_at', 'updated_at'],
                fn (string $column): bool => ! Schema::hasColumn('puroks', $column)
            ));

            if ($timestamps !== []) {
                Schema::table('puroks', function (Blueprint $table) use ($timestamps) {
                    foreach ($timestamps as $column) {
                        $table->timestamp($column)->nullable();
                    }
                });
            }
        }
    }
};

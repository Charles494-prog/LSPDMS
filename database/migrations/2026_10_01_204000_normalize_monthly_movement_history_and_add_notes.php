<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('monthly_record_movement_histories', function (Blueprint $table): void {
            if (Schema::hasColumn('monthly_record_movement_histories', 'purok')) {
                $table->renameColumn('purok', 'previous_purok');
            }
            if (Schema::hasColumn('monthly_record_movement_histories', 'grupo')) {
                $table->renameColumn('grupo', 'previous_grupo');
            }
        });

        $destinationColumns = array_values(array_filter(
            ['destination_purok', 'destination_grupo'],
            fn (string $column): bool => Schema::hasColumn('monthly_record_movement_histories', $column)
        ));
        if ($destinationColumns !== []) {
            Schema::table('monthly_record_movement_histories', function (Blueprint $table) use ($destinationColumns): void {
                $table->dropColumn($destinationColumns);
            });
        }

        Schema::create('monthly_record_movement_notes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('movement_history_id')
                ->constrained('monthly_record_movement_histories')
                ->cascadeOnDelete();
            $table->integer('record_id')->nullable()->index();
            $table->text('note');
            $table->date('noted_at');
            $table->string('noted_by', 255)->nullable();
            $table->index(['record_id', 'noted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monthly_record_movement_notes');

        $destinationColumns = [];
        foreach (['destination_purok', 'destination_grupo'] as $column) {
            if (! Schema::hasColumn('monthly_record_movement_histories', $column)) {
                $destinationColumns[] = $column;
            }
        }
        if ($destinationColumns !== []) {
            Schema::table('monthly_record_movement_histories', function (Blueprint $table) use ($destinationColumns): void {
                foreach ($destinationColumns as $column) {
                    $table->string($column, 50)->nullable();
                }
            });
        }

        Schema::table('monthly_record_movement_histories', function (Blueprint $table): void {
            if (Schema::hasColumn('monthly_record_movement_histories', 'previous_purok')) {
                $table->renameColumn('previous_purok', 'purok');
            }
            if (Schema::hasColumn('monthly_record_movement_histories', 'previous_grupo')) {
                $table->renameColumn('previous_grupo', 'grupo');
            }
        });
    }
};

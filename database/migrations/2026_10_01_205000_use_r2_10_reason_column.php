<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $hasDelistmentReason = Schema::hasColumn('records', 'reason_of_delistment');
        $hasR210Reason = Schema::hasColumn('records', 'reason_of_r2_10_endorsement');

        if ($hasDelistmentReason && ! $hasR210Reason) {
            Schema::table('records', function (Blueprint $table): void {
                $table->renameColumn('reason_of_delistment', 'reason_of_r2_10_endorsement');
            });
        } elseif (! $hasR210Reason) {
            Schema::table('records', function (Blueprint $table): void {
                $table->longText('reason_of_r2_10_endorsement')->nullable();
            });
        } elseif ($hasDelistmentReason) {
            $this->mergeRecordReasonColumn('reason_of_delistment');
            Schema::table('records', function (Blueprint $table): void {
                $table->dropColumn('reason_of_delistment');
            });
        }

        if (Schema::hasColumn('records', 'r2_10_endorsement_reason')) {
            $this->mergeRecordReasonColumn('r2_10_endorsement_reason');
            Schema::table('records', function (Blueprint $table): void {
                $table->dropColumn('r2_10_endorsement_reason');
            });
        }

        if (Schema::hasTable('monthly_record_movement_notes')) {
            DB::table('monthly_record_movement_notes')
                ->orderBy('id')
                ->get(['movement_history_id', 'record_id', 'note', 'noted_at'])
                ->each(function (object $note): void {
                    $date = Carbon::parse((string) $note->noted_at)->toDateString();
                    $recordId = (int) $note->record_id;
                    if ($recordId < 1) {
                        $recordId = (int) DB::table('monthly_record_movement_histories')
                            ->where('id', $note->movement_history_id)
                            ->value('record_id');
                    }
                    if ($recordId < 1) {
                        throw new RuntimeException(
                            "Cannot migrate movement note {$note->movement_history_id}: its record no longer exists."
                        );
                    }
                    $this->appendReason(
                        $recordId,
                        $date.': ('.trim((string) $note->note).')'
                    );
                });

            Schema::drop('monthly_record_movement_notes');
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('records', 'reason_of_r2_10_endorsement')
            && ! Schema::hasColumn('records', 'reason_of_delistment')) {
            Schema::table('records', function (Blueprint $table): void {
                $table->renameColumn('reason_of_r2_10_endorsement', 'reason_of_delistment');
            });
        }

        if (! Schema::hasColumn('records', 'r2_10_endorsement_reason')) {
            Schema::table('records', function (Blueprint $table): void {
                $table->longText('r2_10_endorsement_reason')->nullable();
            });
        }
    }

    private function mergeRecordReasonColumn(string $sourceColumn): void
    {
        DB::table('records')
            ->whereNotNull($sourceColumn)
            ->orderBy('id')
            ->get(['id', $sourceColumn])
            ->each(function (object $record) use ($sourceColumn): void {
                $this->appendReason((int) $record->id, trim((string) $record->{$sourceColumn}));
            });
    }

    private function appendReason(int $recordId, string $reason): void
    {
        if ($reason === '') {
            return;
        }

        $record = DB::table('records')->where('id', $recordId)->first(['reason_of_r2_10_endorsement']);
        if ($record === null) {
            return;
        }

        $existing = trim((string) $record->reason_of_r2_10_endorsement);
        if ($existing !== '' && str_contains($existing, $reason)) {
            return;
        }

        DB::table('records')
            ->where('id', $recordId)
            ->update([
                'reason_of_r2_10_endorsement' => $existing === ''
                    ? $reason
                    : $existing.PHP_EOL.$reason,
            ]);
    }
};

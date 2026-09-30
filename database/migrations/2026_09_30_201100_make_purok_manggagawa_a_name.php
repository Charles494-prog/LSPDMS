<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $assignments = DB::table('puroks')
            ->leftJoin('login_user', 'puroks.manggagawa', '=', 'login_user.id')
            ->whereNotNull('puroks.manggagawa')
            ->select(
                'puroks.id',
                'login_user.first_name',
                'login_user.last_name',
                'login_user.gmail'
            )
            ->get()
            ->mapWithKeys(fn (object $row): array => [
                $row->id => trim(($row->first_name ?? '').' '.($row->last_name ?? '')) ?: $row->gmail,
            ]);

        Schema::table('puroks', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('manggagawa');
            $table->string('manggagawa', 255)->nullable()->after('secretary');
        });

        foreach ($assignments as $purokId => $name) {
            DB::table('puroks')->where('id', $purokId)->update(['manggagawa' => $name]);
        }
    }

    public function down(): void
    {
        $assignments = DB::table('puroks')
            ->whereNotNull('manggagawa')
            ->get(['id', 'manggagawa']);

        $idsByName = DB::table('login_user')
            ->select('id', 'first_name', 'last_name', 'gmail')
            ->get()
            ->groupBy(fn (object $user): string => trim(($user->first_name ?? '').' '.($user->last_name ?? '')) ?: $user->gmail);

        $userIds = [];
        foreach ($assignments as $assignment) {
            $users = $idsByName->get($assignment->manggagawa, collect());
            if ($users->count() !== 1) {
                throw new RuntimeException(
                    "Cannot roll back Manggagawa name '{$assignment->manggagawa}' because it does not uniquely match a login user."
                );
            }
            $userIds[$assignment->id] = $users->first()->id;
        }

        Schema::table('puroks', function (Blueprint $table): void {
            $table->dropColumn('manggagawa');
            $table->foreignId('manggagawa')->nullable()->after('secretary')->constrained('login_user')->nullOnDelete();
        });

        foreach ($userIds as $purokId => $userId) {
            DB::table('puroks')->where('id', $purokId)->update(['manggagawa' => $userId]);
        }
    }
};

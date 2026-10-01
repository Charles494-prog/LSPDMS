<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monthly_record_snapshots', function (Blueprint $table): void {
            $table->id();
            $table->date('month');
            $table->unsignedTinyInteger('purok_number');
            $table->unsignedInteger('ms_count')->default(0);
            $table->unsignedInteger('uwp_count')->default(0);
            $table->unsignedInteger('mk_count')->default(0);
            $table->json('movement_counts');
            $table->timestamps();
            $table->unique(['month', 'purok_number']);
        });

        $monthlyTotals = [
            '2025-01-01' => [[67, 11, 5], [27, 10, 3], [84, 12, 11], [92, 28, 7], [39, 9, 1], [114, 13, 12]],
            '2025-02-01' => [[67, 11, 5], [27, 10, 3], [89, 12, 11], [90, 28, 7], [49, 9, 1], [111, 13, 12]],
            '2025-03-01' => [[65, 11, 5], [25, 10, 3], [77, 12, 11], [87, 28, 7], [44, 9, 1], [108, 13, 12]],
            '2025-04-01' => [[62, 11, 5], [20, 8, 3], [70, 12, 11], [75, 28, 7], [39, 8, 1], [87, 13, 12]],
            '2025-05-01' => [[58, 11, 5], [20, 4, 3], [65, 12, 11], [74, 28, 7], [41, 7, 1], [87, 13, 12]],
            '2025-06-01' => [[58, 11, 5], [20, 4, 3], [64, 12, 11], [67, 28, 7], [41, 7, 1], [87, 13, 12]],
            '2025-07-01' => [[56, 11, 5], [20, 3, 3], [57, 11, 11], [67, 28, 7], [38, 7, 1], [84, 13, 12]],
            '2025-08-01' => [[53, 11, 5], [24, 3, 3], [59, 11, 11], [63, 28, 7], [37, 7, 1], [81, 13, 13]],
            '2025-09-01' => [[52, 11, 5], [28, 3, 3], [64, 11, 11], [69, 28, 7], [35, 6, 1], [81, 13, 13]],
            '2025-10-01' => [[51, 11, 5], [24, 3, 3], [66, 11, 11], [71, 28, 7], [35, 6, 1], [84, 12, 13]],
        ];

        $movementCounts = [
            '2025-07-01' => [
                1 => ['MS' => ['D' => 1, 'E' => 1]],
            ],
            '2025-08-01' => [
                1 => ['MS' => ['B' => 1, 'D' => 3, 'DD' => 1]],
                2 => ['MS' => ['B' => 4]],
                3 => ['MS' => ['B' => 4, 'D' => 2]],
                4 => ['MS' => ['D' => 4]],
                5 => ['MS' => ['D' => 1]],
                6 => ['MS' => ['E' => 3]],
            ],
            '2025-09-01' => [
                1 => ['MS' => ['B' => 1, 'D' => 2]],
                2 => ['MS' => ['B' => 4]],
                3 => ['MS' => ['B' => 5]],
                4 => ['MS' => ['B' => 14, 'D' => 8]],
                5 => ['MS' => ['D' => 2], 'UWP' => ['DD' => 1]],
                6 => ['MS' => ['B' => 2, 'D' => 1, 'E' => 1]],
            ],
            '2025-10-01' => [
                1 => ['MS' => ['B' => 2, 'D' => 3]],
                2 => ['MS' => ['B' => 1, 'D' => 4, 'DD' => 1]],
                3 => ['MS' => ['B' => 3, 'D' => 1]],
                4 => ['MS' => ['B' => 6, 'D' => 4]],
                6 => ['MS' => ['B' => 4, 'D' => 1]],
            ],
        ];

        $rows = [];
        foreach ($monthlyTotals as $month => $puroks) {
            foreach ($puroks as $index => [$msCount, $uwpCount, $mkCount]) {
                $purokNumber = $index + 1;
                $rows[] = [
                    'month' => $month,
                    'purok_number' => $purokNumber,
                    'ms_count' => $msCount,
                    'uwp_count' => $uwpCount,
                    'mk_count' => $mkCount,
                    'movement_counts' => json_encode(
                        $movementCounts[$month][$purokNumber] ?? [],
                        JSON_THROW_ON_ERROR
                    ),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        DB::table('monthly_record_snapshots')->insert($rows);
    }

    public function down(): void
    {
        Schema::dropIfExists('monthly_record_snapshots');
    }
};

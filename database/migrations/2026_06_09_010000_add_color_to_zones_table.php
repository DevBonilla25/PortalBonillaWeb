<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const PALETTE = [
        '#2563EB',
        '#059669',
        '#DC2626',
        '#9333EA',
        '#0891B2',
        '#CA8A04',
        '#DB2777',
        '#16A34A',
        '#7C3AED',
        '#EA580C',
    ];

    public function up(): void
    {
        Schema::table('zones', function (Blueprint $table): void {
            $table->string('color', 7)->default('#2563EB')->after('description');
        });

        DB::table('zones')
            ->select(['id', 'code', 'name'])
            ->orderBy('id')
            ->chunkById(100, function ($zones): void {
                foreach ($zones as $zone) {
                    DB::table('zones')
                        ->where('id', $zone->id)
                        ->update([
                            'color' => self::PALETTE[abs(crc32((string) ($zone->code ?: $zone->name))) % count(self::PALETTE)],
                        ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('zones', function (Blueprint $table): void {
            $table->dropColumn('color');
        });
    }
};

<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use App\Models\Branch;
use Illuminate\Support\Str;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('network:cleanup-logs', function () {
    $deleted = DB::table('monitoring_logs')->where('checked_at', '<', now()->subDays((int) env('MONITORING_LOG_RETENTION_DAYS', 30)))->delete();
    $this->info("Deleted {$deleted} expired monitoring logs.");
})->purpose('Remove raw monitoring logs outside the retention window');

Artisan::command('sopiga:import-branches', function () {
    $table = pgIdentifier((string) env('SOPIGA_BRANCH_TABLE', 'master.branch'));
    $codeColumn = pgIdentifier((string) env('SOPIGA_BRANCH_CODE_COLUMN', 'branch_code'));
    $nameColumn = pgIdentifier((string) env('SOPIGA_BRANCH_NAME_COLUMN', 'branch_name'));
    $cityColumn = pgIdentifier((string) env('SOPIGA_BRANCH_CITY_COLUMN', 'c.city_name'));

    // No status predicate: active and inactive branches are both imported.
    try {
        $rows = DB::connection('sopiga')->select("SELECT b.{$codeColumn} AS branch_code, b.{$nameColumn} AS branch_name, {$cityColumn} AS branch_city FROM {$table} b LEFT JOIN \"master\".\"village\" v ON v.village_id = b.village_id LEFT JOIN \"master\".\"district\" d ON d.district_id = v.district_id LEFT JOIN \"master\".\"city\" c ON c.city_id = d.city_id");
    } catch (\Throwable $exception) {
        $this->error('Gagal konek/query PostgreSQL Sopiga Pro: '.$exception->getMessage());
        return 1;
    }

    $imported = 0;
    $skipped = 0;

    foreach ($rows as $row) {
        $code = strtoupper(trim((string) ($row->branch_code ?? '')));
        $name = trim((string) ($row->branch_name ?? ''));
        $city = trim((string) ($row->branch_city ?? $name));

        if ($code === '' || $name === '') {
            $skipped++;
            continue;
        }

        Branch::updateOrCreate(
            ['code' => Str::limit($code, 30, '')],
            [
                'name' => Str::limit($name, 120, ''),
                'city' => Str::limit($city ?: $name, 120, ''),
                'status' => 'unreachable',
            ]
        );
        $imported++;
    }

    $this->info("Imported/updated {$imported} branches; skipped {$skipped} rows.");
})->purpose('Import branch identity data from PostgreSQL Sopiga master.branch');

if (! function_exists('pgIdentifier')) {
    function pgIdentifier(string $identifier): string
    {
        $identifier = strtolower(trim($identifier));
        abort_unless((bool) preg_match('/^[a-z0-9_$]+(?:\.[a-z0-9_$]+)?$/', $identifier), 422, "Invalid PostgreSQL identifier: {$identifier}");
        return implode('.', array_map(fn ($part) => '"'.$part.'"', explode('.', $identifier)));
    }
}

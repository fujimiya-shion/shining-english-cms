<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ResetOrderValues extends Command
{
    protected $signature = 'app:reset-order-values
                            {--table= : Only process a specific table}
                            {--dry-run : Preview changes without updating}';

    protected $description = 'Reset order column values for tables that have an order column. Records with null/0 order get sequential values: newest created_at gets order = 1.';

    public function handle(): int
    {
        $tables = [
            'courses', 'blogs', 'categories', 'quizzes',
            'contacts', 'admins', 'users', 'orders', 'lessons',
        ];

        $onlyTable = $this->option('table');
        $isDryRun = (bool) $this->option('dry-run');

        foreach ($tables as $table) {
            if ($onlyTable && $table !== $onlyTable) {
                continue;
            }

            if (! Schema::hasColumn($table, 'order') || ! Schema::hasColumn($table, 'created_at')) {
                continue;
            }

            $this->components->task($table, function () use ($table, $isDryRun): void {
                $nullCount = DB::table($table)
                    ->whereNull('order')
                    ->orWhere('order', 0)
                    ->count();

                if ($nullCount === 0) {
                    $this->components->twoColumnDetail('No records to fix', '<fg=green>✓</>');

                    return;
                }

                $rows = DB::table($table)
                    ->whereNull('order')
                    ->orWhere('order', 0)
                    ->orderByDesc('created_at')
                    ->orderByDesc('id')
                    ->get(['id', 'created_at']);

                $caseStmts = [];
                $ids = [];

                foreach ($rows as $index => $row) {
                    $order = $index + 1;
                    $caseStmts[] = "WHEN {$row->id} THEN {$order}";
                    $ids[] = $row->id;
                }

                if ($isDryRun) {
                    $this->components->twoColumnDetail("Found {$nullCount} records", 'DRY RUN');
                    $this->table(
                        ['New Order', 'ID', 'Created At'],
                        collect($rows)->map(fn ($row, $i) => [$i + 1, $row->id, $row->created_at])
                    );

                    return;
                }

                $idsList = implode(',', $ids);
                $cases = implode(' ', $caseStmts);

                DB::statement("
                    UPDATE {$table}
                    SET `order` = CASE `id` {$cases} END
                    WHERE `id` IN ({$idsList})
                ");
            });
        }

        $this->components->info($isDryRun ? 'Dry run completed. No changes were made.' : 'Done.');

        return self::SUCCESS;
    }
}

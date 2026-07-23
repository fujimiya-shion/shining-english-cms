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

    protected $description = 'Reset order column values for tables that have an order column. Sets order = id for all records.';

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

            if (! Schema::hasColumn($table, 'order')) {
                continue;
            }

            $this->components->task($table, function () use ($table, $isDryRun): void {
                if (! Schema::hasColumn($table, 'id')) {
                    $this->components->twoColumnDetail('No id column', '<fg=yellow>Skipped</>');

                    return;
                }

                $totalCount = DB::table($table)->count();

                if ($totalCount === 0) {
                    $this->components->twoColumnDetail('No records', '<fg=green>✓</>');

                    return;
                }

                if ($isDryRun) {
                    $this->components->twoColumnDetail("Found {$totalCount} records", 'DRY RUN');
                    $this->table(
                        ['ID', 'New Order'],
                        DB::table($table)->select('id')->get()->map(fn ($row) => [$row->id, $row->id])
                    );

                    return;
                }

                DB::statement("
                    UPDATE {$table}
                    SET `order` = `id`
                ");

                $this->components->twoColumnDetail("Updated {$totalCount} records", '<fg=green>✓</>');
            });
        }

        $this->components->info($isDryRun ? 'Dry run completed. No changes were made.' : 'Done.');

        return self::SUCCESS;
    }
}

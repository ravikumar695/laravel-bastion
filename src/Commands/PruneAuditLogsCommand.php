<?php

declare(strict_types=1);

namespace JustSteveKing\Bastion\Commands;

use Illuminate\Console\Command;
use JustSteveKing\Bastion\Models\AuditLog;

class PruneAuditLogsCommand extends Command
{
    protected $signature = 'bastion:prune-logs
                            {--days= : Number of days to keep (default from config)}';

    protected $description = 'Prune old audit logs';

    public function handle(): int
    {
        $daysOption = $this->option('days');
        /** @var int $days */
        $days = 90; // Default value

        if (null !== $daysOption) {
            $days = (int) $daysOption;
        } else {
            $configDays = config('bastion.audit_log_retention_days');
            if (is_numeric($configDays)) {
                $days = (int) $configDays;
            }
        }

        /** @var int $count */
        $count = AuditLog::query()
            ->where('created_at', '<', now()->subDays($days))
            ->delete();

        $this->info("✅ Pruned {$count} audit logs older than {$days} days.");

        return self::SUCCESS;
    }
}

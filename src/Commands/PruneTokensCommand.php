<?php

declare(strict_types=1);

namespace JustSteveKing\Bastion\Commands;

use Illuminate\Console\Command;
use JustSteveKing\Bastion\Models\BastionToken;

class PruneTokensCommand extends Command
{
    protected $signature = 'bastion:prune-tokens
                            {--expired : Only prune expired tokens}
                            {--days= : Prune tokens not used in X days}';

    protected $description = 'Prune unused or expired tokens';

    public function handle(): int
    {
        $expired = $this->option('expired');

        /** @var int|null $days */
        $days = $this->option('days') ? (int) $this->option('days') : null;

        $query = BastionToken::query();

        if ($expired) {
            $query->where('expires_at', '<', now());
            $this->info('Pruning expired tokens...');
        } elseif ($days) {
            $query->where('last_used_at', '<', now()->subDays($days))
                ->orWhereNull('last_used_at');
            $this->info("Pruning tokens not used in {$days} days...");
        } else {
            $this->error('Please specify --expired or --days=X');
            return self::FAILURE;
        }

        /** @var int $count */
        $count = $query->count();

        if (0 === $count) {
            $this->info('No tokens to prune.');
            return self::SUCCESS;
        }

        if ( ! $this->confirm("About to delete {$count} tokens. Continue?", true)) {
            $this->info('Cancelled.');
            return self::SUCCESS;
        }

        $query->each(fn(BastionToken $token) => $token->revoke('Pruned by command'));

        $this->info("✅ Pruned {$count} tokens.");

        return self::SUCCESS;
    }
}

<?php

declare(strict_types=1);

namespace JustSteveKing\Bastion\Commands;

use Exception;
use Illuminate\Console\Command;
use JustSteveKing\Bastion\Models\BastionToken;

class RevokeTokenCommand extends Command
{
    protected $signature = 'bastion:revoke
                            {token : The token ID or prefix}
                            {--reason= : Reason for revocation}
                            {--all-user= : Revoke all tokens for a user ID}';

    protected $description = 'Revoke an API token';

    public function handle(): int
    {
        $tokenIdentifier = $this->argument('token');

        /** @var string|null $reason */
        $reason = $this->option('reason') ?: null;

        $allUser = $this->option('all-user');

        if ($allUser) {
            return $this->revokeAllForUser((int) $allUser, $reason);
        }

        try {
            // Try to find by ID first
            if (is_numeric($tokenIdentifier)) {
                /** @var BastionToken|null $token */
                $token = BastionToken::query()->find((int) $tokenIdentifier);
            } else {
                // Try to find by prefix
                /** @var BastionToken|null $token */
                $token = BastionToken::query()->where('token_prefix', $tokenIdentifier)->first();
            }

            if ( ! $token) {
                $this->error('Token not found.');
                return self::FAILURE;
            }

            $tokenName = $token->name;
            $token->revoke($reason);

            $this->info("✅ Token '{$tokenName}' has been revoked.");

            if ($reason) {
                $this->line("Reason: {$reason}");
            }

            return self::SUCCESS;
        } catch (Exception $e) {
            $this->error("Error revoking token: {$e->getMessage()}");
            return self::FAILURE;
        }
    }

    private function revokeAllForUser(int $userId, ?string $reason): int
    {
        /** @var int $count */
        $count = BastionToken::query()
            ->where('user_id', $userId)
            ->get()
            ->each(fn(BastionToken $token) => $token->revoke($reason))
            ->count();

        $this->info("✅ Revoked {$count} tokens for user {$userId}.");

        if ($reason) {
            $this->line("Reason: {$reason}");
        }

        return self::SUCCESS;
    }
}

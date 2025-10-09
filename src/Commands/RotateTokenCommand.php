<?php

declare(strict_types=1);

namespace JustSteveKing\Bastion\Commands;

use Exception;
use Illuminate\Console\Command;
use JustSteveKing\Bastion\Models\BastionToken;

class RotateTokenCommand extends Command
{
    protected $signature = 'bastion:rotate
                            {token : The token ID or prefix}';

    protected $description = 'Rotate an API token (create new, revoke old)';

    public function handle(): int
    {
        $tokenIdentifier = $this->argument('token');

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

            $this->line("Rotating token '{$token->name}'...");
            $this->newLine();

            $result = $token->rotate();

            /** @var string $plainTextToken */
            $plainTextToken = $result['plainTextToken'];

            $this->info('✅ Token rotated successfully!');
            $this->newLine();
            $this->line('New token (store securely):');
            $this->line($plainTextToken);
            $this->newLine();
            $this->warn('⚠️  This is the only time the token will be displayed.');
            $this->warn('⚠️  The old token has been revoked and is no longer valid.');

            return self::SUCCESS;
        } catch (Exception $e) {
            $this->error("Error rotating token: {$e->getMessage()}");
            return self::FAILURE;
        }
    }
}

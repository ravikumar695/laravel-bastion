<?php

declare(strict_types=1);

namespace JustSteveKing\Bastion\Commands;

use Exception;
use Illuminate\Console\Command;
use JustSteveKing\Bastion\Enums\{TokenEnvironment, TokenType};
use JustSteveKing\Bastion\Facades\Bastion;
use ValueError;

class GenerateTokenCommand extends Command
{
    protected $signature = 'bastion:generate
                            {user : The user ID}
                            {name : Token name}
                            {--environment=test : Environment (test or live)}
                            {--type=restricted : Type (public, secret, or restricted)}
                            {--scopes=* : Scopes to grant}';

    protected $description = 'Generate a new API token';

    public function handle(): int
    {
        /** @var class-string $userModel */
        $userModel = config('bastion.user_model');

        try {
            /** @var \Illuminate\Contracts\Auth\Authenticatable $user */
            $user = $userModel::findOrFail($this->argument('user'));
        } catch (Exception $e) {
            $this->error('User not found.');
            return self::FAILURE;
        }

        try {
            /** @var string $environmentOption */
            $environmentOption = $this->option('environment');
            $environment = TokenEnvironment::from($environmentOption);

            /** @var string $typeOption */
            $typeOption = $this->option('type');
            $type = TokenType::from($typeOption);
        } catch (ValueError $e) {
            $this->error('Invalid environment or type.');
            return self::FAILURE;
        }

        /** @var array<int, string> $scopes */
        $scopes = $this->option('scopes');

        /** @var string $name */
        $name = $this->argument('name');

        /** @var array{token: \JustSteveKing\Bastion\Models\BastionToken, plainTextToken: string} $result */
        $result = Bastion::generate(
            user: $user,
            name: $name,
            environment: $environment,
            type: $type,
            scopes: $scopes,
        );

        $this->newLine();
        $this->info('✅ Token generated successfully!');
        $this->newLine();

        $this->line('Token Details:');
        $this->table(
            ['Property', 'Value'],
            [
                ['ID', $result['token']->id],
                ['Name', $result['token']->name],
                ['Environment', $result['token']->environment->value],
                ['Type', $result['token']->type->value],
                ['Scopes', implode(', ', $result['token']->scopes ?? [])],
            ],
        );

        $this->newLine();
        $this->line('Plain Text Token:');
        $this->line($result['plainTextToken']);
        $this->newLine();
        $this->warn('⚠️  Save this token now - you won\'t be able to see it again!');
        $this->newLine();

        return self::SUCCESS;
    }
}

<?php

declare(strict_types=1);

namespace JustSteveKing\Bastion\Commands;

use Illuminate\Console\Command;

class InstallCommand extends Command
{
    protected $signature = 'bastion:install
                            {--force : Overwrite existing files}';

    protected $description = 'Install Laravel Bastion';

    public function handle(): int
    {
        $this->info('Installing Laravel Bastion...');
        $this->newLine();

        // Publish configuration
        $this->comment('Publishing configuration...');
        $this->call('vendor:publish', [
            '--tag' => 'bastion-config',
            '--force' => $this->option('force'),
        ]);

        // Publish migrations
        $this->comment('Publishing migrations...');
        $this->call('vendor:publish', [
            '--tag' => 'bastion-migrations',
            '--force' => $this->option('force'),
        ]);

        $this->newLine();

        // Run migrations
        if ($this->confirm('Run migrations now?', true)) {
            $this->call('migrate');
        }

        $this->newLine();
        $this->info('✅ Laravel Bastion installed successfully!');
        $this->newLine();

        $this->line('Next steps:');
        $this->line('1. Add the HasBastionTokens trait to your User model:');
        $this->line('   use Bastion\Concerns\HasBastionTokens;');
        $this->newLine();
        $this->line('2. Generate your first token:');
        $this->line('   php artisan bastion:generate {user-id} "My Token"');
        $this->newLine();
        $this->line('3. Protect your routes:');
        $this->line('   Route::middleware(\'bastion\')->group(function () { ... });');
        $this->newLine();

        return self::SUCCESS;
    }
}

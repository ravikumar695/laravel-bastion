<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        $tokensTable = config('bastion.tables.tokens', 'bastion_tokens');

        Schema::create('bastion_audit_logs', function (Blueprint $table): void {
            $table->id();

            // Who performed the action
            $table
                ->foreignId('user_id')
                ->nullable()
                ->index()
                ->constrained()
                ->nullOnDelete();
            $table
                ->foreignId('bastion_token_id')
                ->index()
                ->references('bastion_tokens')
                ->cascadeOnDelete();

            // What happened
            $table->string('action');
            $table->string('resource_type')->nullable();
            $table->unsignedBigInteger('resource_id')->nullable();

            // Request details
            $table->string('method', 10);
            $table->text('endpoint');
            $table->integer('status_code');

            // Context
            $table->ipAddress('ip_address');
            $table->text('user_agent')->nullable();
            $table->string('environment', 20);

            // What changed
            $table->json('changes')->nullable();
            $table->json('metadata')->nullable();

            // Response details
            $table->integer('response_time_ms')->nullable();
            $table->text('error_message')->nullable();

            $table->timestamp('created_at');

            // Indexes
            $table->index(['user_id', 'created_at']);
            $table->index(['bastion_token_id', 'created_at']);
            $table->index(['resource_type', 'resource_id']);
            $table->index('action');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bastion_audit_logs');
    }
};

<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('bastion_tokens', static function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Token identification
            $table->string('name');
            $table->string('token_prefix', 20)->unique();
            $table->string('token_hash')->unique();

            // Token properties
            $table->string('environment', 20)->default('test');
            $table->string('type', 20)->default('restricted');

            // Scopes and permissions
            $table->json('scopes')->nullable();
            $table->json('metadata')->nullable();

            // Security
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['user_id', 'environment']);
            $table->index('last_used_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bastion_tokens');
    }
};

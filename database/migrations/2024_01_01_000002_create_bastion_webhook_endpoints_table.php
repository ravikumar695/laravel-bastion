<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('bastion_webhook_endpoints', static function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->string('url');
            $table->string('secret_hash');
            $table->string('secret_prefix', 16);

            $table->json('events')->nullable();
            $table->string('environment', 20)->default('test');
            $table->boolean('is_active')->default(true);

            $table->integer('failure_count')->default(0);
            $table->timestamp('last_success_at')->nullable();
            $table->timestamp('disabled_at')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'environment', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bastion_webhook_endpoints');
    }
};

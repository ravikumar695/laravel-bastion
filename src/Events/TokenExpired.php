<?php

declare(strict_types=1);

namespace JustSteveKing\Bastion\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use JustSteveKing\Bastion\Models\BastionToken;

final class TokenExpired
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly BastionToken $token,
    ) {}
}

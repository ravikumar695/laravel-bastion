<?php

declare(strict_types=1);

namespace JustSteveKing\Bastion\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use JustSteveKing\Bastion\Enums\TokenEnvironment;
use JustSteveKing\Bastion\Events\TokenUsed;
use JustSteveKing\Bastion\Models\BastionToken;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateToken
{
    /**
     * @param Request $request
     * @param Closure(Request): Response $next
     * @param string|null $scope
     * @return Response
     */
    public function handle(Request $request, Closure $next, ?string $scope = null): Response
    {
        $token = $this->extractToken($request);

        if ( ! $token) {
            return $this->errorResponse(
                status: 401,
                title: 'Unauthenticated',
                detail: 'API token required. Please provide a valid token via the Authorization header or api_key query parameter.',
                type: 'token_missing',
                instance: $request->getRequestUri(),
            );
        }

        $bastionToken = BastionToken::findByToken($token);

        if ( ! $bastionToken || ! $bastionToken->isValid()) {
            return $this->errorResponse(
                status: 401,
                title: 'Unauthenticated',
                detail: 'Invalid or expired API token',
                type: 'token_invalid',
                instance: $request->getRequestUri(),
            );
        }

        // Check environment matches
        if ( ! $this->isEnvironmentValid($request, $bastionToken)) {
            return $this->errorResponse(
                status: 403,
                title: 'Forbidden',
                detail: 'Token environment mismatch. This token cannot be used in the current environment.',
                type: 'environment_mismatch',
                instance: $request->getRequestUri(),
            );
        }

        // Check required scope
        if ($scope && ! $bastionToken->hasScope($scope)) {
            return $this->errorResponse(
                status: 403,
                title: 'Forbidden',
                detail: "Missing required scope: {$scope}",
                type: 'insufficient_scope',
                instance: $request->getRequestUri(),
                extensions: ['required_scope' => $scope],
            );
        }

        // Attach token and user to request
        /** @var TokenEnvironment $environment */
        $environment = $bastionToken->getAttribute('environment');

        $request->merge([
            'bastionToken' => $bastionToken,
            'bastionEnvironment' => $environment->value,
        ]);

        /** @var \Illuminate\Contracts\Auth\Authenticatable $user */
        $user = $bastionToken->user()->first();
        auth()->guard()->setUser($user);

        // Mark token as used (synchronously to avoid serialization issues in tests)
        $bastionToken->markAsUsed();

        // Dispatch event if audit logging is enabled
        if (config('bastion.security.enable_audit_logging', true)) {
            Event::dispatch(new TokenUsed(
                token: $bastionToken,
                ipAddress: $request->ip() ?? 'unknown',
                userAgent: $request->userAgent() ?? 'unknown',
                endpoint: $request->getRequestUri(),
            ));
        }

        return $next($request);
    }

    /**
     * @param Request $request
     * @return string|null
     */
    private function extractToken(Request $request): ?string
    {
        $bearerToken = $request->bearerToken();
        if ($bearerToken) {
            return $bearerToken;
        }

        $apiKey = $request->query('api_key');
        return is_string($apiKey) ? $apiKey : null;
    }

    /**
     * Check if token environment is valid for current app environment
     *
     * @param Request $request
     * @param BastionToken $token
     * @return bool
     */
    private function isEnvironmentValid(Request $request, BastionToken $token): bool
    {
        if ( ! config('bastion.security.prevent_test_tokens_in_production', true)) {
            return true;
        }

        /** @var TokenEnvironment $tokenEnv */
        $tokenEnv = $token->getAttribute('environment');

        // Test tokens can be used anywhere
        if (TokenEnvironment::Test === $tokenEnv) {
            return true;
        }

        // Live tokens only in production
        if (TokenEnvironment::Live === $tokenEnv) {
            return (bool) app()->environment('production');
        }

        return true;
    }

    /**
     * Generate error response (RFC 7807 Problem Details format)
     *
     * @param int $status
     * @param string $title
     * @param string $detail
     * @param string $type Short error code (e.g., token_missing)
     * @param string $instance
     * @param array<string, mixed> $extensions
     * @return Response
     */
    private function errorResponse(
        int $status,
        string $title,
        string $detail,
        string $type,
        string $instance,
        array $extensions = [],
    ): Response {
        $useProblemDetails = config('bastion.errors.use_rfc7807', true);

        if ($useProblemDetails) {
            $base = config()->string('bastion.errors.base_url');
            $base = mb_rtrim($base, '/') . '/';

            $problem = [
                'type' => $base . $type,
                'title' => $title,
                'status' => $status,
                'detail' => $detail,
                'instance' => $instance,
                ...$extensions,
            ];

            return response()->json($problem, $status, [
                'Content-Type' => 'application/problem+json',
            ]);
        }

        // Legacy format
        return response()->json([
            'error' => $title,
            'message' => $detail,
            ...$extensions,
        ], $status);
    }
}

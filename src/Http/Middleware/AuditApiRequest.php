<?php

declare(strict_types=1);

namespace JustSteveKing\Bastion\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use JustSteveKing\Bastion\Enums\TokenEnvironment;
use JustSteveKing\Bastion\Models\AuditLog;
use JustSteveKing\Bastion\Models\BastionToken;
use Symfony\Component\HttpFoundation\Response;

class AuditApiRequest
{
    /**
     * @param Request $request
     * @param Closure(Request): Response $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip audit logging if disabled in config
        if ( ! config('bastion.security.enable_audit_logging', true)) {
            return $next($request);
        }

        $startTime = microtime(true);

        // Process the request
        /** @var Response $response */
        $response = $next($request);

        // Calculate response time
        $responseTime = round((microtime(true) - $startTime) * 1000);

        // Get the authenticated user and token if available
        $userId = Auth::id();

        /** @var BastionToken|null $bastionToken */
        $bastionToken = $request->bastionToken ?? null;

        $tokenId = $bastionToken?->getKey();

        $environment = TokenEnvironment::Live;
        if ($bastionToken) {
            /** @var TokenEnvironment $tokenEnvironment */
            $tokenEnvironment = $bastionToken->getAttribute('environment');
            $environment = $tokenEnvironment;
        }

        // Determine the action based on the HTTP method
        $action = match ($request->method()) {
            'GET' => 'read',
            'POST' => 'create',
            'PUT', 'PATCH' => 'update',
            'DELETE' => 'delete',
            default => 'other',
        };

        // Extract resource type and ID if available
        $resourceType = null;
        $resourceId = null;

        // Get route parameters that might indicate resource type and ID
        $route = $request->route();
        $routeParams = [];

        if (is_object($route) && method_exists($route, 'parameters')) {
            /** @var array<string, mixed> $parameters */
            $parameters = $route->parameters();
            $routeParams = $parameters;
        }

        if (count($routeParams) > 0) {
            // Try to determine resource type and ID from route parameters
            foreach ($routeParams as $key => $value) {
                if (is_string($key) && is_numeric($value) && str_ends_with($key, '_id')) {
                    $resourceType = str_replace('_id', '', $key);
                    $resourceId = (int) $value;
                    break;
                }
            }
        }

        // Create the audit log
        AuditLog::query()->create([
            'user_id' => $userId,
            'bastion_token_id' => $tokenId,
            'action' => $action,
            'resource_type' => $resourceType,
            'resource_id' => $resourceId,
            'method' => $request->method(),
            'endpoint' => $request->path(),
            'status_code' => $response->getStatusCode(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'environment' => $environment,
            'changes' => $this->extractChanges($request),
            'metadata' => $this->extractMetadata($request, $response),
            'response_time_ms' => $responseTime,
            'error_message' => $this->extractErrorMessage($response),
        ]);

        return $response;
    }

    private function extractChanges(Request $request): array
    {
        if (in_array($request->method(), ['POST', 'PUT', 'PATCH'])) {
            return $request->except(['password', 'password_confirmation', 'token', 'api_key']);
        }

        return [];
    }

    private function extractMetadata(Request $request, Response $response): array
    {
        $metadata = [
            'headers' => $this->sanitizeHeaders($request->headers->all()),
            'query' => $request->query(),
        ];

        // Add response info for errors
        if ($response->getStatusCode() >= 400) {
            $metadata['response'] = [
                'status' => $response->getStatusCode(),
                'statusText' => Response::$statusTexts[$response->getStatusCode()] ?? 'Unknown',
            ];

            // Add response content for JSON responses
            if ('application/json' === $response->headers->get('Content-Type')) {
                $decoded = json_decode((string) $response->getContent(), true);
                if (JSON_ERROR_NONE === json_last_error() && is_array($decoded)) {
                    $metadata['response']['content'] = $decoded;
                }
            }
        }

        return $metadata;
    }

    /**
     * @param array<string, list<string|null>> $headers
     * @return array<string, list<string|null>>
     */
    private function sanitizeHeaders(array $headers): array
    {
        $sensitiveHeaders = [
            'authorization',
            'cookie',
            'x-xsrf-token',
            'x-csrf-token',
        ];

        foreach ($sensitiveHeaders as $header) {
            if (isset($headers[$header])) {
                $headers[$header] = ['[REDACTED]'];
            }
        }

        return $headers;
    }

    private function extractErrorMessage(Response $response): ?string
    {
        if ($response->getStatusCode() < 400) {
            return null;
        }

        if ('application/json' === $response->headers->get('Content-Type')) {
            $decoded = json_decode((string) $response->getContent(), true);
            if (JSON_ERROR_NONE === json_last_error() && is_array($decoded)) {
                if (isset($decoded['message']) && is_string($decoded['message'])) {
                    return $decoded['message'];
                }
                if (isset($decoded['error']) && is_string($decoded['error'])) {
                    return $decoded['error'];
                }
                return null;
            }
        }

        return Response::$statusTexts[$response->getStatusCode()] ?? 'Unknown error';
    }
}

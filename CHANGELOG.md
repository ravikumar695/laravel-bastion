# Changelog

All notable changes to `laravel-bastion` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [0.0.1] - 2025-10-09

### Added
- **Core Authentication System**
  - Stripe-inspired API token authentication
  - Token prefixing with environment and type indicators (e.g., `app_test_rk_*`, `app_live_sk_*`)
  - HMAC-SHA256 token hashing with application key
  - Constant-time comparison to prevent timing attacks
  - `AuthenticateToken` middleware for route protection
  - Support for Bearer token and query parameter authentication

- **Token Types**
  - Public Keys (`pk`) - Limited access, safe for client-side use
  - Secret Keys (`sk`) - Full access to permitted scopes
  - Restricted Keys (`rk`) - Scoped access with specific permissions

- **Environment Isolation**
  - Test environment for development and testing
  - Live environment for production traffic
  - Configurable prevention of test tokens in production
  - Environment-specific rate limits

- **Granular Scopes System**
  - Fine-grained permission control with `resource:action` pattern
  - Wildcard support (e.g., `users:*`, `*`)
  - Built-in `ApiScope` enum with example scopes
  - Scope validation middleware

- **Security Features**
  - Token expiration dates
  - Soft deletes for token revocation
  - Token rotation with automatic old token revocation
  - Cryptographically secure token generation using `random_bytes()`

- **Audit Logging**
  - `AuditApiRequest` middleware for comprehensive request tracking
  - Captures request/response details, IP addresses, user agents
  - Response time tracking
  - Configurable audit log retention
  - `AuditLog` model with queryable logs

- **Webhook Support**
  - `WebhookEndpoint` model for webhook configuration
  - Webhook signing secrets with HMAC-SHA256 signatures
  - Signature verification with replay attack prevention
  - Environment-specific webhook endpoints
  - Event subscription system

- **Events System**
  - `TokenCreated` - Dispatched when a new token is generated
  - `TokenUsed` - Dispatched on successful token authentication
  - `TokenRevoked` - Dispatched when a token is revoked
  - `TokenRotated` - Dispatched when a token is rotated
  - `TokenExpired` - Dispatched when an expired token is used

- **Artisan Commands**
  - `bastion:install` - Package installation and setup
  - `bastion:generate` - Create tokens via CLI with scopes and options
  - `bastion:revoke` - Revoke tokens by ID, prefix, or all for a user
  - `bastion:rotate` - Rotate tokens securely
  - `bastion:prune-tokens` - Clean up expired or unused tokens
  - `bastion:prune-logs` - Clean up old audit logs

- **Models**
  - `BastionToken` - Core token model with relationships and validation
  - `AuditLog` - Audit log storage and querying
  - `WebhookEndpoint` - Webhook configuration and verification

- **Traits**
  - `HasBastionTokens` - Add to User model for token generation

- **Configuration**
  - Comprehensive config file with sensible defaults
  - Customizable table names
  - Rate limiting configuration
  - Security settings (audit logging, alerting, environment restrictions)
  - RFC 7807 Problem Details error responses
  - Configurable user model

- **Database Migrations**
  - `bastion_tokens` table with soft deletes
  - `bastion_audit_logs` table
  - `bastion_webhook_endpoints` table
  - Proper indexes for performance

### Security
- Tokens are never stored in plain text, only HMAC-SHA256 hashes
- Protection against timing attacks during token verification
- Replay attack prevention for webhooks (300-second window)
- Environment isolation to prevent test tokens in production

[Unreleased]: https://github.com/juststeveking/laravel-bastion/compare/v0.0.1...HEAD
[0.0.1]: https://github.com/juststeveking/laravel-bastion/releases/tag/v0.0.1

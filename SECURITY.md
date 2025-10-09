# Security Policy

## Supported Versions

We actively support the following versions of Laravel Bastion with security updates:

| Version | Supported          |
| ------- | ------------------ |
| 1.x     | :white_check_mark: |

## Reporting a Vulnerability

**Please do not report security vulnerabilities through public GitHub issues.**

If you discover a security vulnerability within Laravel Bastion, please send an email to Steve McDougall at juststevemcd@gmail.com. All security vulnerabilities will be promptly addressed.

### What to Include

When reporting a vulnerability, please include:

- Type of issue (e.g., buffer overflow, SQL injection, cross-site scripting, etc.)
- Full paths of source file(s) related to the manifestation of the issue
- The location of the affected source code (tag/branch/commit or direct URL)
- Any special configuration required to reproduce the issue
- Step-by-step instructions to reproduce the issue
- Proof-of-concept or exploit code (if possible)
- Impact of the issue, including how an attacker might exploit it

### Response Timeline

- **Initial Response**: Within 48 hours
- **Status Update**: Within 7 days
- **Fix Timeline**: Critical issues will be patched within 14 days

## Security Best Practices

### Token Management

1. **Never log tokens** - Only store hashed versions in the database
2. **Display tokens once** - Show the plain text token only at creation time
3. **Use HTTPS only** - Always transmit tokens over encrypted connections
4. **Implement token rotation** - Rotate tokens regularly, especially after suspected compromise
5. **Set expiration dates** - Use short-lived tokens when possible
6. **Revoke unused tokens** - Remove tokens that are no longer needed

### Token Storage

- Store tokens in environment variables or secure vaults (e.g., AWS Secrets Manager, HashiCorp Vault)
- Never commit tokens to version control
- Use separate tokens for different environments (test vs live)
- Restrict token scopes to minimum necessary permissions

### Access Control

1. **Scope Limitation** - Always use restricted tokens with specific scopes instead of wildcard access

### Monitoring & Auditing

- Enable audit logging in production environments
- Monitor for unusual token usage patterns
- Set up alerts for failed authentication attempts
- Review audit logs regularly for suspicious activity
- Track token usage metrics and last-used timestamps

### Rate Limiting

- Configure appropriate rate limits for your API
- Use different limits for test vs live environments
- Implement exponential backoff for repeated failures
- Monitor for rate limit abuse

### Encryption

- Laravel Bastion uses HMAC-SHA256 with your application key for token hashing
- Ensure your `APP_KEY` is properly set and kept secure
- Rotate your application key following Laravel's key rotation procedures
- Use strong, randomly generated application keys

### Environment Isolation

- Use test tokens (`app_test_*`) in development/staging
- Use live tokens (`app_live_*`) only in production
- Enable `prevent_test_tokens_in_production` in config
- Maintain separate databases for test and production

## Known Security Considerations

### Constant-Time Comparison

Laravel Bastion uses `hash_equals()` for token comparison to prevent timing attacks.

### Token Format

Tokens follow the format: `app_{environment}_{type}_{random}`

This format makes it easy to identify token types and prevents accidental misuse across environments.

### HMAC Validation

All tokens are hashed using HMAC with your application key, providing an additional layer of security beyond standard SHA-256 hashing.

## Disclosure Policy

- Security issues are disclosed publicly only after a fix is available
- We follow responsible disclosure practices
- Credit will be given to security researchers who report issues responsibly
- A security advisory will be published for critical vulnerabilities

## Security Updates

Security updates are released as patch versions (e.g., 1.0.1) and announced via:

- GitHub Security Advisories
- Package changelog
- Email notification to maintainers of known installations

## Compliance

Laravel Bastion is designed to support:

- PCI DSS requirements for API key management
- GDPR requirements for audit logging and data access controls
- SOC 2 Type II audit requirements
- HIPAA compliance for access control and audit trails

## Contact

For security concerns, contact:
- Email: juststevemcd@gmail.com
- GitHub: @juststeveking

For general support, use GitHub Issues.

## Acknowledgments

We thank the security researchers who have responsibly disclosed vulnerabilities to us. Contributors will be acknowledged in release notes (unless they prefer to remain anonymous).


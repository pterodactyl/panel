# HTTP Header Authentication Feature

## Overview

This implementation adds support for authentication via HTTP headers, which enables Pterodactyl Panel to work behind SSO proxies like Authelia, Authentik, or OIDC providers.

## Feature Description

When running behind a reverse proxy that handles authentication (such as Authelia, Authentik, LDAP, or OIDC), the proxy can pass authenticated user credentials to Pterodactyl via HTTP headers. This feature allows Pterodactyl to automatically authenticate users based on those headers.

## Implementation Details

### Files Added

1. **`app/Http/Middleware/AuthenticateHeader.php`** - Middleware that handles HTTP header authentication
2. **`tests/Unit/Http/Middleware/AuthenticateHeaderTest.php`** - Comprehensive test suite

### Files Modified

1. **`.env.example`** - Added environment variables for header authentication configuration
2. **`config/auth.php`** - Added header authentication configuration section
3. **`app/Http/Kernel.php`** - Registered middleware in the web middleware group

## Configuration

Add the following environment variables to your `.env` file:

```env
# Enable authentication via HTTP headers
AUTH_HEADER_ENABLED=true

# The HTTP header containing the username
AUTH_HEADER_USERNAME_HEADER=X-Auth-Username

# The HTTP header containing the email
AUTH_HEADER_EMAIL_HEADER=X-Auth-Email

# Automatically create users if they don't exist
AUTH_HEADER_AUTO_CREATE_USER=true
```

## Usage Example

### Nginx Proxy Configuration

```nginx
server {
    listen 443 ssl;
    server_name panel.example.com;

    location / {
        # Authentik/SSO proxy adds these headers after successful authentication
        proxy_set_header X-Auth-Username $remote_user;
        proxy_set_header X-Auth-Email $remote_user_email;

        proxy_pass http://pterodactyl-panel;
    }
}
```

### Authelia Configuration Example

In Authelia configuration, you can set headers to pass to the backend:

```yaml
access_control:
  default_policy: one_factor

session:
  cookies:
    - name: authelia_session
      domain: example.com
```

The proxy will automatically add the configured headers after successful authentication.

## Security Considerations

1. **Proxy Trust**: This feature should only be used when the application is behind a trusted reverse proxy. The proxy should be configured to strip these headers from client requests.

2. **Header Validation**: The middleware checks for the presence of configured headers and only processes authentication when both username and email are available for user creation.

3. **Auto-Creation Control**: User auto-creation can be disabled by setting `AUTH_HEADER_AUTO_CREATE_USER=false` if you prefer manual user management.

4. **Existing Users**: The middleware first attempts to find existing users by username or email before attempting to create new ones.

## How It Works

1. Middleware intercepts all web requests
2. If header authentication is enabled and user is not already authenticated:
   - Checks for configured HTTP headers
   - Attempts to find existing user by username or email
   - If user not found and auto-creation is enabled:
     - Creates new user with provided credentials
     - Generates a random password (not used for header auth)
   - Logs in the user
3. Request continues to the application with authenticated user

## Test Coverage

The implementation includes comprehensive tests covering:
- Header authentication disabled scenario
- Authentication with valid headers
- User lookup by username
- User lookup by email
- Automatic user creation
- User auto-creation disabled
- Already authenticated user handling
- Requirements for both headers for auto-creation

Run tests with:
```bash
vendor/bin/phpunit tests/Unit/Http/Middleware/AuthenticateHeaderTest.php
```

## Benefits

1. **SSO Integration**: Enables seamless integration with SSO providers
2. **Flexibility**: Supports various SSO solutions (Authelia, Authentik, LDAP, OIDC)
3. **Security**: Leverages proxy authentication for centralized user management
4. **Ease of Use**: Simple configuration via environment variables
5. **Automatic User Provisioning**: Optional auto-creation of users on first login

## Backward Compatibility

This feature is fully backward compatible:
- Disabled by default
- Does not affect existing authentication methods
- Only activates when explicitly configured

## References

- Issue: https://github.com/pterodactyl/panel/issues/4026
- Related services using similar approach:
  - Paperless: https://docs.paperless-ngx.com/configuration/
  - Firefly III: https://firefly-iii.readthedocs.io/en/latest/configuration/authentication/
## [Unreleased] - 2026-02-12

### Added
- HTTP Header Authentication support for SSO proxy integration (#4026)
  - New middleware `AuthenticateHeader` for authenticating users via HTTP headers
  - Configuration options in `.env` for enabling and customizing header authentication
  - Automatic user creation feature for new authenticated users
  - Comprehensive test coverage for header authentication

### Changed
- `config/auth.php` - Added `header` configuration section
- `.env.example` - Added header authentication environment variables
- `app/Http/Kernel.php` - Added `AuthenticateHeader` to web middleware group
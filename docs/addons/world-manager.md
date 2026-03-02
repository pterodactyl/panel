# World Manager Addon (CurseForge)

## Overview
This addon adds a modular **World Manager** system to Pterodactyl Panel v1.11+ for searching, installing, and managing Minecraft worlds from CurseForge.

## Folder Structure

- `app/Http/Controllers/Api/Client/Servers/WorldManagerController.php`
- `app/Http/Requests/Api/Client/Servers/Worlds/*`
- `app/Services/WorldManager/*`
- `app/Jobs/WorldManager/InstallWorldFromCurseForgeJob.php`
- `app/Models/InstalledWorld.php`
- `app/Models/InstallationLog.php`
- `app/Models/UserWorldPreference.php`
- `database/migrations/2026_03_02_00000*_create_*`
- `resources/views/addons/world-manager/index.blade.php` (includes example UI CSS/JS)

## Architecture Decisions

1. **Service Layer**
   - `CurseForgeService`: API abstraction with response caching.
   - `WorldFileService`: Wings file operations, validation, backup, extraction helpers.
   - `WorldInstallationService`: queue + transactional state updates.

2. **Asynchronous Installations**
   - Installation requests create an `installation_logs` record.
   - `InstallWorldFromCurseForgeJob` runs in queue worker and tracks progress stages.

3. **Persistent World State**
   - `installed_worlds` tracks installed world metadata and active world selection.
   - `user_preferences` stores per-user filter/sort preferences.

## Security Model

- Uses existing client API auth middleware and server access middleware.
- Request classes implement permission gates.
- Server/world association checks on all mutable world operations.
- Rate limiting (`throttle:30,1`) on world routes.
- Overwrite protection via remote directory existence check.
- Extraction operations execute only against known archive names and managed paths.

## Installation Workflow

1. User searches worlds (`/worlds/search`) with optional version and sort.
2. User selects a world file version from `/worlds/projects/{projectId}/versions`.
3. User submits install request (`/worlds/install`).
4. Controller queues `InstallWorldFromCurseForgeJob` and returns log id.
5. Job resolves CurseForge download URL.
6. Job pulls ZIP through Wings file API to server.
7. Job extracts archive, registers `installed_worlds`, updates log to complete.
8. UI polls `/worlds/logs` for progress updates.

## Error Handling Strategy

- HTTP client calls use `->throw()` to normalize failures.
- Job wraps installation in try/catch and persists failure reason to `installation_logs`.
- Exceptions are reported for observability while returning safe error payloads.

## Registering in Pterodactyl

1. Set environment variable:
   - `CURSEFORGE_API_KEY=your_token_here`
2. Run migrations:
   - `php artisan migrate`
3. Run queue worker:
   - `php artisan queue:work --queue=default`
4. Ensure API routes are loaded from `routes/api-client.php` (already added).
5. Optionally wire the Blade view into a panel route/tab in your theme integration.

## Future Improvements

- Websocket-driven live installation progress.
- Checksum validation and ZIP content policy scanner.
- World import/export templates.
- Multi-node CDN caching for popular worlds.
- Deep UI integration in React client tab + granular permission scope.

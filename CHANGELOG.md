# Changelog
This file is a running track of new features and fixes to each version of the panel released starting with `v0.4.0`.

This project follows [Semantic Versioning](http://semver.org) guidelines.

## [Unreleased]
### Changed
* Changed minimum PHP version is now 8.0 instead of `7.4`.
* Upgraded from Laravel 8 to Laravel 9.

## v1.10.4
### Fixed
* Fixed an issue where subusers could be given permissions that are not actually registered or used.
* Fixed an issue where node FQDNs could not just be IP addresses.

### Changed
* Change maximum number of API keys per user from `10` to `25`.
* Change byte unit prefix from `B` to `iB` to better reflect our usage of base 2 (multiples of 1024).

## v1.10.3
### Fixed
* S3 Backup driver now supports Cloudflare R2.
* Node FQDNs can now be used with AAAA records with no A records present.
* Server transfers can no longer be initiated if the server is being installed, transferred, or restoring a backup.
* Fixed an issue relating to the use of arrays in the `config_files` field with eggs.
* Fixed `oom_disabled` not being mapped in the Application API when creating a new server.

### Added
* File manager now supports selecting multiple files for upload (when using the upload button).
* Added a configuration option for specifying the S3 storage class for backups.

### Changed
* Servers will now show the current uptime when the server is starting rather than only showing when the server is marked as online.

## v1.10.2
### Fixed
* Fixes a rendering issue with egg descriptions in the admin area
* Fixes the page title on the SSH Keys page

### Changed
* Additional validation rules will now show a toggle switch rather than an input when editing server variables
* The eggs endpoint will now always return an empty JSON object for the `config_files` field, even if the field is completely empty

### Added
* Adds a `Force Outgoing IP` option for eggs that can be used to ensure servers making outgoing connections use their allocation IP rather than the node's primary ip
* Adds options to configure sending of email (re)install notifications
* Add an option to configure the part size for backups uploaded to S3

## v1.10.1
### Fixed
* Fixes a surprise `clock()` function that was used for debugging and should not have made it into the release. This was causing activity events to not properly sync between the Panel and Wings.

## v1.10.0
### Fixed
* Fixes improper cache key naming on the frontend causing server activity logs to be duplicated across server page views.
* Fixes overflow issues on dialogs when the internal content is too long.
* Fixes spinner overlay on console improperly taking up the entire page making it impossible to use navigation controls.
* Fixes 2FA QR code background being too dark for some phones to properly scan.
* File manager now properly displays an error message if a user attempts to upload a folder rather than files.
* Fixes the "Create Directory" dialog persisting the previously entered value when it is re-opened.

### Changed
* IP addresses in activity logs are now always displayed to administrators, regardless of if they own the server or not.
* Scroll down indicator on the console has been changed to a down arrow to be clearer.
* Docker builds have been updated to use `PHP 8.1`.
* Recaptcha validation domain is now configurable using the `RECAPTCHA_DOMAIN` environment variable.
* Drag and drop overlay on the file manager has been tweaked to be slightly more consistent with the frontend style and be a little easier to read.

### Added
* Adds support for the `user_uuid` claim on all generated JWTs which allows Wings to properly identify the user performing each action.
* Adds support for recieving external activity log events from Wings instances (power state, commands, SFTP, and uploads).
* Adds support for tracking failed password-based SFTP logins.
* Server name and description are now passed along to Wings making them available in egg variables for parsing and including.
* Adds support for displaying all active file uploads in the file manager.

## v1.9.2
### Fixed
* Fixes rouding in sidebar of CPU usage graph that was causing an excessive number of zeros to be rendered.
* Fixes the Java Version selector modal having the wrong default value selected initially.
* Fixes console rendering in Safari that was causing the console to resize excessively and graphs to overlay content.
* Fixes missing "Starting"/"Stopping" status display in the server uptime block.
* Fixes incorrect formatting of activity log when viewing certain file actions.

### Changed
* Updated the UI for the two-step authorization setup on accounts to use new Dialog UI and provide better clarity to new users.

### Added
* Added missing `<DOCTYPE html>` tag to template output to avoid entering quirks mode in browsers.
* Added password requirement when enabling TOTP on an account.

## v1.9.1
### Fixed
* Fixes missing "Click to Copy" for server address on the console data blocks.
* Fixes data points on the graphs not being properly rounded to two decimal places.
* Returns byte formatting logic to use `1024` as the base value, rather than `1000`.
* Fixes permission error occurring when a server is marked as installing and an admin navigates to the console screen.
* Fixes improper display of install/transfer warning on the server console page.
* Fixes permission matching for the server settings page to correctly allow access when a user has _any_ of the needed permissions.

### Changed
* Moves the server data blocks to the right-hand side of the console, rather than the left.
* Rather than defaulting graph values at `0` when resetting or refreshing the page, their values are now hidden entirely.
* **[security]** Hides IP addresses from all activity log entries that are not directly associated with the currently signed in user.

### Added
* Adds the current resource limits for a server next to each data block on the console screen.

## v1.9.0
### Added
* Added support for using Tailwind classes inside components using `className={}` rather than having to use `twin.macro` with the `css={}` prop.
* Added HeadlessUI and Heroicons packages.
* Added new `Tooltip.tsx` component to support displaying tooltips within the Panel.
* Adds a new activity log view for both user accounts and individual servers. This builds upon data collected in previous releases.
* Added a new column `api_key_id` to the `activity_logs` table to indicate if the user performed the action while using an API key.
* Adds initial support for language translations on the front-end. The underlying implementation details are working, however work has not yet begun on actually translating all of the strings yet. Expect this to continue in future releases.
* Improved accessibility for navigation icons by adding a tooltip on hover to indicate what each one does.
* Adds logging for API keys that are blocked from performing an API action due to IP address limiting.
* Adds support for `?filter[description]=foo` when querying servers on both the client and application API.

### Changed
* Updated how release assets are generated to perform more logical bundle splitting. This should help reduce the amount of data users have to download at once in order to render the UI.
* Upgraded From TailwindCSS 2 to 3 — for most people this should have minimal if any impact.
* Chart.js updated from v2 to v3.
* Reduced the number of custom colors in use — by default we now use Tailwind's default color pallet, with the exception of a custom gray scheme.
* **[deprecated]** The use of `neutral` and `primary` have been deprecated in class names, prefer `gray` and `blue` respectively.
* Begins the process of dropping the use of Gravatars for user avatars and replaces them with dynamically generated SVG images.
* Improved front-end route definitions to make it easier for external modifications to inject their routes and components into the codebase without having to modify as many core files.
* Redesigned the server console screen to better display data users might be looking for, and increase the height of the console itself.
* Merged the two network data graphs into a single dual-line graph to better display incoming and outgoing data volumes.
* Updated all byte formatting logic to use `1000` as the divisor rather than `1024` to be more consistent with what users most likely expect.
* Changed the underlying `eslint` rules applied to the front-end codebase to simplify them dramatically. We now utilize `prettier` in combination with some basic default rulesets to make it easier to understand the expected formatting.

### Fixed
* Fixes a bug causing a 404 error when attempting to delete a database from a server in the admin control panel.
* Fixes console input auto-capitalizing and auto-correcting when entering text on some mobile devices.
* Fixes SES service configuration using a hard-coded `us-east-1` region.
* Fixes a bug causing a 404 error when attempting to delete an SSH key from your account when the SHA256 hash includes a slash.
* Fixes mobile keyboards automatically attempting to capitalize and spellcheck typing on the server console.
* Fixes improper support for IP address CIDR ranges when creating API keys for the client area.
* Fixes a bug preventing additional included details from being returned from the application API when utilizing a client API key as an administrator.

## v1.8.1
### Fixed
* Fixes a bug causing mounts to return a 404 error when adding them to a server.
* Fixes a bug causing the Egg Image dropdown to not display properly when creating a new server.
* Fixes a bug causing an error when attemping to create a new server via the API.

## v1.8.0
**Important:** this version updates the `version` field on generated Eggs to be `PTDL_v2` due to formatting changes. This
should be completely seamless for most installations as the Panel is able to convert between the two. Custom solutions
using these eggs should be updated to account for the new format.

This release also changes API key behavior — "client" keys belonging to admin users can now be used to access
the `/api/application` endpoints in their entirety. Existing "application" keys generated in the admin area should
be considered deprecated, but will continue to work. Application keys _will not_ work with the client API.

### Fixed
* Schedules are no longer run when a server is suspended or marked as installing.
* The remote field when creating a database is no longer limited to an IP address and `%` wildcard — all expected MySQL remote host values are allowed.
* Allocations cannot be deleted from a server by a user if the server is configured with an `allocation_limit` set to `0`.
* The Java Version modal no longer shows a dropdown and update option to users that do not have permission to make those changes.
* The Java Version modal now correctly returns only the images available to the server's selected Egg.
* Fixes leading and trailing spaces being removed from variable values on file manager endpoints, causing errors when trying to perform actions against certain files and folders.

### Changed
* Forces HTTPS on URLs when the `APP_URL` value is set and includes `https://` within the URL. This addresses proxy misconfiguration issues that would cause URLs to be generated incorrectly.
* Lowers the default timeout values for requests to Wings instances from 10 seconds to 5 seconds.
* Additional permissions (`CREATE TEMPORARY TABLES`, `CREATE VIEW`, `SHOW VIEW`, `EVENT`, and `TRIGGER`) are granted to users when creating new databases for servers.
* development: removed Laravel Debugbar in favor of Clockwork for debugging.
* The 2FA input field when logging in is now correctly identified as `one-time-password` to help browser autofill capabilities.
* Changed API authentication mechanisms to make use of Laravel Sanctum to significantly clean up our internal handling of sessions.
* API keys generated by the system now set a prefix to identify them as Pterodactyl API keys, and if they are client or application keys. This prefix looks like `ptlc_` for client keys, and `ptla_` for application keys. Existing API keys are unaffected by this change.

### Added
* Added support for PHP 8.1 in addition to PHP 8.0 and 7.4.
* Adds more support for catching potential PID exhaustion errors in different games.
* It is now possible to create a new node on the Panel using an artisan command.
* A new cron cheatsheet has been added which appears when creating a schedule.
* Adds support for filtering the `/api/application/nodes/:id/allocations` endpoint using `?filter[server_id]=0` to only return allocations that are not currently assigned to a server on that node.
* Adds support for naming docker image values in an Egg to improve front-end display capabilities.
* Adds command to return the configuration for a specific node in both YAML and JSON format (`php artisan p:node:configuration`).
* Adds command to return a list of all nodes available on the Panel in both table and JSON format (`php artisan p:node:list`).
* Adds server network (inbound/outbound) usage graphs to the console screen.
* Adds support for configuring CORS on the API by setting the `APP_CORS_ALLOWED_ORIGINS=example.com,dashboard.example.com` environment variable. By default all instances are configured with this set to `*` which allows any origin.
* Adds proper activity logging for the following areas of the Panel: authentication, user account modifications, server modification. This is an initial test implementation before further roll-out in the software. Events are logged into the database but are not currently exposed in the UI — they will be displayed in a future update.

### Removed
* Removes Google Analytics from the front end code.
* Removes multiple middleware that were previously used for configuring API access and controlling model fetching. This has all been replaced with Laravel Sanctum and standard Laravel API tooling. This should make codebase discovery significantly more simple.
* **DEPRECATED**: The use of `Pterodactyl\Models\AuditLog` is deprecated and all references to this model have been removed from the codebase. In the next major release this model and table will be fully dropped.

## v1.7.0
### Fixed
* Fixes typo in message shown to user when deleting a database.
* Fixes formatting of IPv6 addresses when displaying allocations to users.
* Fixes an exception thrown while trying to return error messages from API endpoints that inproperly masked the true underlying error.
* Fixes SSL certificate path generation for Let's Encrypt by ensuring they are always transformed to lowercase.
* Removes duplicate entries when creating a nested folder in the file manager.
* Fixes missing validation of Egg Author email addresses during the setup process that could cause unexpected failures later on.
* Fixes font rendering issues of the console on Firefox due to an outdated version of xterm.js being used.
* Fixes display overlap issues of the two-factor configuration form in a user's settings.
* **[security]** When authenticating using an API key a user session is now only persisted for the duration of the request before being destroyed.

### Changed
* CPU graph changed to show the maximum amount of CPU available to a server to better match how the memory graph is displayed.

### Added
* Adds support for `DB_PORT` environment variable in the Docker enterpoint for the Panel image.
* Adds suport for ARM environments in the Docker image.
* Adds a new warning modal for Steam servers shown when an invalid Game Server Login Token (GSL Token) is detected.
* Adds a new warning modal for Steam servers shown when the installation process runs out of available disk space.
* Adds a new warning modal for Minecraft servers shown when a server exceeds the maximum number of child processes.
* Adds support for displaying certain server variable fields as a checkbox when they're detected as using `boolean` or `in:0,1` validation rules.
* Adds support for Pug and Jade in the file editor.
* Adds an entry to the `robots.txt` file to correctly disallow all bot indexing.


## v1.6.6
### Fixed
* **[security]** Fixes a CSRF vulnerability for both the administrative test email endpoint and node auto-deployment token generation endpoint. [GHSA-wwgq-9jhf-qgw6](https://github.com/pterodactyl/panel/security/advisories/GHSA-wwgq-9jhf-qgw6)

### Changed
* Updates Minecraft eggs to include latest Java 17 yolk by default.

## v1.6.5
### Fixed
* Fixes broken application API endpoints due to changes introduced with session management in 1.6.4.

## v1.6.4
_This release should not be used, please use `1.6.5`. It has been pulled from our releases._

### Fixed
* Fixes a session management bug that would cause a user who signs out of one browser to be unintentionally logged out of other browser sessions when using the client API.

## v1.6.3
### Fixed
* **[Security]** Changes logout endpoint to be a POST request with CSRF-token validation to prevent a malicious actor from triggering a user logout.
* Fixes Wings receiving the wrong server suspension state when syncing servers.

### Added
* Adds additional throttling to login and password reset endpoints.
* Adds server uptime display when viewing a server console.

## v1.6.2
### Fixed
* **[Security]** Fixes an authentication bypass vulerability that could allow a malicious actor to login as another user in the Panel without knowing that user's email or password.

## v1.6.1
### Fixed
* Fixes server build modifications not being properly persisted to the database when edited.
* Correctly exposes the `oom_disabled` field in the `build` limits block for a server build so that Wings can pick it up.
* 
## v1.6.0
### Fixed
* Fixes array merging logic for server transfers that would cause a 500 error to occur in some scenarios.
* Fixes user password updates not correctly logging the user out and returning a failure message even upon successful update.
* Fixes the count of used backups when browsing a paginated backup list for a server.
* Fixes an error being triggered when API endpoints are called with no `User-Agent` header and an audit log is generated for the action.
* Fixes state management on the frontend not properly resetting the loading indicator when adding subusers to a server.
* Fixes extraneous API calls being made to Wings for the server file listing when not on a file manager screen.

### Added
* Adds foreign key relationship on the `mount_node`, `mount_server` and `egg_mount` tables.
* Adds environment variable `PER_SCHEDULE_TASK_LIMIT` to allow manual overrides for the number of tasks that can exist on a single schedule. This is currently defaulted to `10`.
* OOM killer can now be configured at the time of server creation.

### Changed
* Server updates are not dependent on a successful call to Wings occurring — if the API call fails internally the error will be logged but the server update will still be persisted.

### Removed
* Removed `WingsServerRepository::update()` function — if you were previously using this to modify server elements on Wings please replace calls to it with `::sync()` after updating Wings.

## v1.5.1
### Fixed
* Fixes Docker image 404ing instead of being able to access the Panel.
* Fixes Java version feature being only loaded when the `eula` feature is specified.
* Fixes `php artisan p:upgrade` not forcing and seeding while running migrations.
* Fixes spinner overlays overlapping on the server console page.
* Fixes Wings being unable to update backup statuses.

## v1.5.0
### Fixed
* Fixes deleting a locked backup that has also been marked as failed to allow deletion rather than returning an error about being locked.
* Fixes server creation process not correctly sending `start_on_completion` to Wings instance.
* Fixes `z-index` on file mass delete modal so it is displayed on top of all elements, rather than hidden under some.
* Supports re-sending requests to the Panel API for backups that are currently marked as failed, allowing a previously failed backup to be marked as successful.
* Minor updates to multiple default eggs for improved error handling and more accurate field-level validation.

### Updated
* Updates help text for CPU limiting when creating a new server to properly indicate virtual threads are included, rather than only physical threads.
* Updates all of the default eggs shipped with the Panel to reference new [`ghcr.io` yolks repository](https://github.com/pterodactyl/yolks).
* When adding 2FA to an account the key used to generate the token is now displayed to the user allowing them to manually input into their app if necessary.

### Added
* Adds SSL/TLS options for MySQL and Redis in line with most recent Laravel updates.
* New users created for server MySQL instances will now have the correct permissions for creating foreign keys on tables.
* Adds new automatic popup feature to allow users to quickly update their Minecraft servers to the latest Java® eggs as necessary if unsupported versions are detected.

### Removed
* Removes legacy `userInteraction` key from eggs which was unused.

## v1.4.2
### Fixed
* Fixes logic to disallow creating a backup schedule if the server's backup limit is set to 0.
* Fixes bug preventing a database host from being updated if the linked node is set to "none".
* Fixes files and menus under the "Mass Actions Bar" being unclickable when it is visible.
* Fixes issues with the Teamspeak and Mumble eggs causing installs to fail.
* Fixes automated query to avoid pruning backups that are still running unintentionally.
* Fixes "Delete Server" confirmation modal on the admin screen to actually show up when deleting rather than immediately deleting the server.

### Added
* Adds support for locking individual server backups to prevent deletion by users or automated backup processes.
* List of files to be deleted is now shown on the delete file confirmation modal.
* Adds support for using `IF` statements in database queries when a database user is created through the Panel.
* Adds support for using a custom mailgun API endpoint rather than only the US based endpoint.
* Adds CPU limit display next to the current CPU usage to match disk and memory usage reporting.
* Adds a "Scroll to Bottom" helper element to the server console when not scrolled to the bottom currently.
* Adds support for querying the API for servers by using the `uuidShort` field rather than only the `uuid` field.

### Changed
* Updates codebase to use TypeScript 4.
* **[security]**: removes the external dependency for loading QRCode images. They're now generated directly on the frontend using JavaScript.

## v1.4.1
### Added
* Adds support for only running a schedule if the server is currently in an online state.
* Adds support for ignoring errors during task execution and continuing on to the next item in the sequence. For example, continuing to a server restart even if sending a command beforehand failed.
* Adds the ability to specify the group to use for file permissions when using the `p:upgrade` command.
* Adds the ability to manually run a schedule even if it is currently disabled.

## v1.4.0
### Fixed
* Removes the use of tagging when storing server resource usage in the cache. This addresses errors encountered when using the `file` driver.
* Fixes Wings response handling if Wings returns an error response with a 200-level status code that would improperly be passed back to the client as a successful request.
* Fixes use of JSON specific functions in SQL queries to better support MariaDB users.
* Fixes a migration that could fail on some MySQL/MariaDB setups when trying to encrypt node token values.

### Changed
* Increases the maximum length allowed for a server name using the Rust egg.
* Updated server resource utilization API call to Wings to use new API response format used by `Wings@1.4.0`.

## v1.3.2
### Fixed
* Fixes self-upgrade incorrectly executing the command to un-tar downloaded archives.
* Fixes the checkbox to delete all files when restoring a backup not actually passing that along in the API call. Files will now properly be deleted when restoring if selected.
* Fixes some keybindings not working correctly in the server console on Windows machines.
* Fixes mobile UI incorrectly squishing the Docker image selector on the server settings page.
* Fixes recovery tokens not having a `created_at` value set on them properly when they are created.
* Fixes flawed migration that would not correctly set the month value into schedule crons.
* Fixes incorrect mounting for Docker compose file that would cause error logs to be missing.

### Changed
* Server resource lookups are now cached on the Panel for 20 seconds at a time to reduce the load from multiple clients requesting the same server's stats.
* Bungeecord egg no longer force-enables the query listener.
* Adds page to the dashboard URL to allow easy loading of a specific pagination page rather than resetting back to the first page when refreshing.
* All application API endpoints now correctly support the `?per_page=N` query parameter to specify how many resources to return at once.

## v1.3.1
### Fixed
* Fixes the Rust egg not properly seeding during the upgrade & installation process.
* Fixes backups not being downloadable via the frontend.
* Fixes backup listing showing the wrong number of existing backups based on the current page you're on.

## v1.3.0
### Fixed
* Fixes administrator "Other Servers" toggle being persisted wrongly when signing out and signing into a non-administrator account on the server dashboard.
* Fixes composer failing to run properly in local environments where there is no database connection available once configured.
* Fixes SQL exception caused by the Panel attempting to store null values in the database.
* Fixes validation errors caused by improper defaults when trying to edit system settings in the admin area.
* Fixes console overflow when using smaller-than-default font sizes in Firefox.
* Fixes console text input field having a white background when manually building new assets from the release build due to a missing `babel-macros` definition file.
* Fixes database improperly using a signed `smallint` field rather than an unsigned field which restricted SFTP ports to 32767 or less.
* Fixes server console resize handler to no longer encounter an exception at random that breaks the entire UI.
* Fixes unhandled error caused by entering an invalid IP address or FQDN when creating a new node allocation.
* Fixes unhandled error when Wings would fetch a server configuration from the Panel that uses an Egg with invalid JSON data for the configuration fields.
* Fixes email not being sent to a user when their server is done installing.

### Added
* Adds support for automatically copying SFTP connection details when clicking into the text field.
* Messaging about a node not having any allocations available for deployment has been adjusted to be more understandable by users.
* Adds automated self-upgrade process for Pterodactyl Panel once this version is installed on servers. This allows users to update by using a single command.
* Adds support for specifying a month when creating or modifying a server schedule.
* Adds support for restoring backups (including those in S3 buckets) to a server and optionally deleting all existing files when doing so.
* Adds underlying support for audit logging on servers. Currently this is only used by some internal functionality but will be slowly expanded as time permits to allow more robust logging.
* Adds logic to automatically reset failed server states when Wings is rebooted. This will kick servers out of "installing" and "restoring from backup" states automatically.

### Changed
* Updated to `Laravel 8` and bumped minimum PHP version from `7.3` to `7.4` with PHP `8.0` being the recommended.
* Server state is now stored in a single `status` column within the database rather than multiple different `tinyint` columns.

## v1.2.2
### Fixed
* **[security]** Fixes authentication bypass allowing a user to take control of specific server actions such as executing schedules, rotating database passwords, and viewing or deleting a backup.

## v1.2.1
### Fixed
* Fixes URL-encoding of filenames when working in the filemanager to fix issues when moving, renaming, or deleting files.
* Fixes URL-encoding of email addresses when requesting a password reset.

### Added
* Adds the ability for users to select a base Java Docker image for most Minecraft specific eggs shipped as defaults.

## v1.2.0
### Fixed
* Fixes newest backup being deleted when creating a new one using the schedule tasks, rather than the oldest backup.
* Fixes multiple encoding issues when handling file names in the manager.
* Fixes database password not properly being copied to the clipboard when clicked.
* Fixes failed transfers unintentionally locking a server into a failed state and not properly releasing allocations that were reserved.
* Fixes error box on server pages having an oval refresh button rather than a perfect circle.
* Fixes a bunch of errors and usage issues relating to backups especially when uploading to S3-based systems.
* Fixes HMR breaking navigation in development modes on the frontend.

### Changed
* Updated Paper egg to default to Java 11 as the base docker image.
* Removes the file mode display from the File Manager row listing.
* Updated input UI elements to have thicker borders and more consistent highlighting when active.
* Changed searchbar toggle from `"k"` to `Cmd/Ctrl + "/"` to avoid accidental toggles and be more consistent with other sites.
* Upgrades TailwindCSS to `v2`.

### Added
* Adds support for eggs to define multiple Docker images that can be selected by users (e.g. Java 8 & 11 images for a single egg).
* Adds support for configuring the default interval for failed backups to be pruned from the system to avoid long running backups being incorrectly cleared.
* Adds server transfer output logging to the server console allowing admins to see how a transfer is progressing directly in the UI.
* Adds client API endpoint to download a file from a remote souce. This functionality is not currently expressed in the UI.

## v1.1.3
### Fixed
* Server bulk power actions command will no longer attempt to run commands against installing or suspended servers.
* Fixes the application API throwing an error when attempting to return variables for a server.
* Fixes an error when attempting to install Panel dependencies without specifying an `.env` file due to an unset default timezone.
* Fixes a null value flip in the database migrations.
* Fixes password change endpoint for users allowing a blank value to be provided (even if nothing actually happened).
* Fixes database IP addresses not allowing a `0` in the first octet field.
* Fixes node information being impossible to update if there was a network error during the process. Any errors encountered communicating with Wings are now reported but will not block the actual saving of the changes.
* **[Security]** When 2FA is required on an account the client API endpoints will now properly return an error and the UI will redirect the user to setup 2FA.
* **[Security]** When changing the owner of a server the old owner's JWT is now properly invalidated on Wings.
* Fixes a server error when requesting database information for a server as a subuser and the account is not granted `view_password` permissions.

### Added
* Adds support for basic backup rotation on a server when creating scheduled backup tasks.
* Makes URLs present in the console clickable.
* Adds `chmod` support to the file manager so that users can manually make modifications to file permissions as they need.

### Changed
* UI will no longer show a delete button to users when they're editing themselves.
* Updated logic for bulk power actions to no longer run actions against suspended or installing servers.

## v1.1.2
### Fixed
* Fixes an exception thrown while trying to validate IP access for the client API.
* Fixes command history scrolling not putting the cursor at the end of the line.
* Fixes file manager rows triggering a 404 when middle-clicked to open in a new tab.

## v1.1.1
### Fixed
* Fixes allocation permissions checking on the frontend checking the wrong permission therefore leading to the item never showing up.
* Fixes allocations not updating correctly when added or deleted and switching between pages.

## v1.1.0
This release **requires** `Wings@1.1.0` in order to work properly due to breaking internal API changes.

### Fixed
* Fixes subuser creation/edit modal not submitting correctly when attemping to make modifications.
* Fixes a few remaining issues with multiple egg install scripts.
* Removes the ability for a schedule to have a null name and replaces any existing null names with a randomly generated name.
* Fixes schedules aborting the entire run process if a single schedule encountered an exception. This resolves batches of schedules never running correctly if they occur after a broken schedule.
* Fixes schedules not properly resetting themselves if an exception was encountered during the run.
* Fixes numerous N+1 query run-aways when loading multiple servers via the API.
* Fixes numerous issues with displaying directory and file names in the file manager if they included special characters that could not be decoded properly.
* Fixes CPU pinning not being properly passed along to Wings when updated (this also fixes memory/CPU/disk not passing along correctly as well).
* Fixes spinner not displaying properly when displayed over a modal.

### Added
* Adds ability for users to generate their own additional server allocations via the frontend if enabled.
* Adds the ability for a user to remove un-needed allocations from their server (as long as it is not the primary allocation).
* Adds support for tracking the last 32 sent console commands for a server. Access the history by using the arrow keys when the command field is active.
* Adds S3 specific environment variables allowing for backups to use any S3 compatiable system, not just AWS.
* Adds support for copying a server allocation address to the clipboard when clicked.
* Adds information about the next schedule run time when viewing an individual schedule.
* Adds link to view a server in the admin control panel to the frontend server view when logged in as a root admin.
* Adds support for egg-specific frontend/backend functionality. This is a beta feature meant for internal features at this time.
* Adds back the EULA warning popup when starting a Minecraft server without an accepted EULA.
* Adds missing descriptions for some user permissions on the frontend UI.

### Changed
* Adds Save/Invite button to top of subuser edit/creation modal to reduce the need for scrolling.
* Updated language for server transfers and mounts to be less confusing.
* Wings API endpoint for fetching all servers on a node is now properly paginated to reduce system load when returning hundreds or thousands of servers at once.
* Removes unnecessary Wings API calls when adding/editing/deleting mounts.
* Primary allocation for a server is now always returned, even if the subuser does not have permission to view all of the server allocations.
* Google Analytics frontend code is now only loaded when a valid key is provided.

## v1.0.3
### Fixed
* Fixes bug causing subusers to not be creatable or editable via the frontend for servers.
* Fixes system timezone not being passed along properly to the MySQL connection causing scheduled tasks to run every minute when the MySQL instance and Panel timezone did not line up.
* Fixes listing servers owned by a user in the admin area to actually list their servers.

### Changed
* Adds SameSite `lax` attribute for cookies generated by the Panel.
* Adds better filtering for searching servers in the admin area to better key off name, uuid, or owner username/email.

## v1.0.2
### Added
* Adds support for searching inside the file editor.
* Adds support for manually executing a schedule regardless of if it is currently queued or not.
* Adds an indicator to the schedule UI to show when a schedule is currently processing.
* Adds support for setting the `backup_limit` of a server via the API.
* **[Security]** Adds login throttling to the 2FA verification endpoint.

### Fixed
* Fixes subuser page title missing server name.
* Fixes schedule task `sequence_id` not properly being reset when a schedule's task is deleted.
* Fixes misc. UI bugs throughout the frontend when long text overflows its bounds.
* Fixes user deletion command to properly handle email & ID searching.
* Fixes color issues in the terminal causing certain text & background combinations to be illegible.
* Fixes reCAPTCHA not properly resetting on login failure.
* Fixes error messages not properly resetting between login screens.
* Fixes a UI crash when attempting to create or view a directory or file that contained the `%` somewhere in the name.

### Changed
* Updated the search modal to close itself when the ESC key is pressed.
* Updates the schedule view and editing UI to better display information to users.
* Changed logic powering server searching on the frontend to return more accurate results and return all servers when executing the query as an admin.
* Admin CP link no longer opens in a new tab.
* Mounts will no longer allow a user to mount certain directory combinations. This blocks mounting one server's files into another server, and blocks using the server data directory as a mount destination.
* Cleaned up assorted server build modification code.
* Updates default eggs to have improved install scripts and more consistent container usage.

## v1.0.1
### Fixed
* Fixes 500 error when mounting a mount to a server, and other related errors when handling mounts.
* Ensures that `server_transfers` database is deleted if it already exists to avoid unnecessary error.
* Fixes servers getting marked as "not installed" when modifying their startup arguments.
* Fixes filemanager breadcrumbs being set incorrectly when navigating between files and folders.

### Changed
* Change the requests per minute from 240 to 720 for the client API to avoid unecessarily displaying
"Too Many Requests" errors.
* Added error output to certain commands that will output and terminate the command execution if the database
migrations have not been run correctly for the instance.

## v1.0.0
Pterodactyl 1.0 represents the culmination of over two years of work, almost 2,000 commits, endless bug and feature requests, and a dream that
has been in the making since 2013. 🎉

Due to the sheer size and timeline of this release I've massively truncated the listing below. There are numerous smaller
bug fixes and changes that would simply be too difficult to keep track of here. Please feel free to browse through the releases
tab for this repository to see more specific changes that have been made.

### Added
* Adds a new client-facing API allowing a user to control all aspects of their individual servers, or servers
which they have been granted access to as a subuser.
* Adds the ability for backups to be created for a server both manually and via a scheduled task.
* Adds the ability for users to modify their server allocations on the fly and include notes for each allocation.
* Adds the ability for users to generate recovery tokens for 2FA protected logins which can be used in place of
a code should their device be inaccessible.
* Adds support for transfering servers between Nodes via the Panel.
* Adds the ability to assign specific CPU cores to a server (CPU Pinning) process.
* Server owners can now reinstall their assigned server egg automatically with a button on the frontend.

### Changed
* The entire user frontend has been replaced with a responsive, React backed design implemented using Tailwind CSS.
* Replaces a large amount of complex daemon authentication logic by funneling most API calls through the Panel, and using
JSON Web Tokens where necessary to handle one-time direct authentication with Wings.
* Frontend server listing now includes a toggle to show or hide servers which an administrator has access to, rather
than always showing all servers on the system when logged into an admin account.
* We've replaced Ace Editor on the frontend with a better solution to allow lighter builds and more end-user functionality.
* Server permissions have been overhauled to be both easier to understand in the codebase, and allows plugins to better
hook into the permission system.

### Removed
* Removes large swaths of code complexity and confusing interface designs that caused a lot of pain to new developers
trying to jump into the codebase. We've simplified this to stick to more established Laravel design standards to make
it easy to parse through the project and make contributions.

## v0.7.19 (Derelict Dermodactylus)
### Fixed
* **[Security]** Fixes XSS in the admin area's server owner selection.

## v0.7.18 (Derelict Dermodactylus)
### Fixed
* **[Security]** Re-addressed missed endpoint that would not properly limit a user account to 5 API keys.
* **[Security]** Addresses a Client API vulnerability that would allow a user to list all servers on the system ([`GHSA-6888-7f3w-92jx`](https://github.com/pterodactyl/panel/security/advisories/GHSA-6888-7f3w-92jx))

## v0.7.17 (Derelict Dermodactylus)
### Fixed
* Limited accounts to 5 API keys at a time.
* Fixes database passwords not being generated with the proper requirements for some MySQL setups.
* Hostnames that are not FQDNs/IP addresses can now be used for connecting to a MySQL host.

## v0.7.16 (Derelict Dermodactylus)
### Fixed
* Fixed the /api/application/servers endpoint erroring when including subusers or egg
* Fixed bug in migration files causing failures when using MySQL 8.
* Fixed missing redirect return when an error occurs while modifying database information.
* Fixes bug in login attempt tracking.
* Fixes a bug where certain URL encoded files would not be editable in the file manager.

### Added
* The application API now includes the egg's name in the egg model's response.
* The /api/application/servers endpoint can now include server's databases and subusers.

## v0.7.15 (Derelict Dermodactylus)
### Fixed
* Fixes support for PHP 7.3 when running `composer install` commands due to a dependency that needed updating.
* Automatic allocation field when creating a new node (or updating one) should now properly remeber its old
value when showing an error state.
* Mass deleting files now executes properly and doesn't result in a JS console error.
* Scrolling on email settings page now works.
* Database host management will now properly display an error message to the user when there is any type of MySQL related
error encountered during creation or update.
* Two-factor tokens generated when a company name has a space in it will now properly be parsed on iOS authenticator devices.
* Fixed 500 error when trying to request subuser's from a server in the application API.
* Creating a node allocation via the API no longer requires an alias field be passed through in the request.
* Bulk power management for servers via the CLI no longer fails when servers span multiple nodes.

### Added
* Server listing view now displays the total used disk space for each server.
* Client API endpoint to list all servers now supports an additional `?filter=subuser-of|all|admin|owner` parameter to
return different groupings of servers. The default value is `subuser-of` which will include all of the user's servers
that they are the owner of, as well as all servers they're a subuser of.
* Added back ability to toggle OOM killer status on a per-server basis.
* Added `LOCK TABLES` permission for generated database users.

### Changed
* Updated Paper egg to not download `server.properties` each time. [parkervcp/eggs#260](https://github.com/parkervcp/eggs/issues/260)
* Insurgency egg now uses the proper dedicated server ID.
* Teamspeak egg updated with improved installation process and grabbing latest versions.
* OOM killer disabled by default on all new servers.
* Passwords generated for MySQL now include special characters and are 24 characters in length.

## v0.7.14 (Derelict Dermodactylus)
### Fixed
* **[SECURITY]** Fixes an XSS vulnerability when performing certain actions in the file manager.
* **[SECURITY]** Attempting to login as a user who has 2FA enabled will no longer request the 2FA token before validating
that their password is correct. This closes a user existence leak that would expose that an account exists if
it had 2FA enabled.

### Changed
* Support for setting a node to listen on ports lower than 1024.
* QR code URLs are now generated without the use of an external library to reduce the dependency tree.
* Regenerated database passwords now respect the same settings that were used when initially created.
* Cleaned up 2FA QR code generation to use a more up-to-date library and API.
* Console charts now properly start at 0 and scale based on server configuration. No more crazy spikes that
are due to a change of one unit.

## v0.7.13 (Derelict Dermodactylus)
### Fixed
* Fixes a bug with the location update API endpoint throwing an error due to an unexected response value.
* Fixes bug where node creation API endpoint was not correctly requiring the `disk_overallocate` key.
* Prevents an exception from being thrown when a database with the same name is created on two different hosts.
* Fixes the redis password not saving correctly when setting up the environment from the command line.
* Fixes a bug with transaction handling in many areas of the application that would cause validation error messages
and other session data to not be persisted properly when using the database as the session driver.
* Fix a bug introduced at some point in the past that causes internal data integrity exceptions to not bubble up to
the user correctly, leading to extraneous and confusing exception messages.
* Fixes a bug causing servers to not be marked as having failed installation in some cases.

### Changed
* `allocation_limit` for servers now defaults to a null value, and is not required in PATCH/POST requests when adding
a server through the API.
* The `PATCH` endpoint for `/api/applications/servers/{server}/build` now accepts an array called `limits` to match
the response from the server `GET` endpoint.

### Added
* The server listing for a node is now paginated to 25 servers per page to improve performance on large nodes.

## v0.7.12 (Derelict Dermodactylus)
### Fixed
* Fixes an issue with the locations API endpoint referencing an invalid namespace.
* Fixes the `store()` function on the locations API not working due to an incorrect return typehint.
* Fixes daemon secrets not being able to be reset on a Node.
* Fixes an issue where files were not editable due to missing URL encoding in the file manager.
* Fixed checking of language changes
* Fixed Spigot egg not building versions other than `latest`.
* Fixed the Forge egg install script.
* Fixes a bug that would ignore the `skip_scripts` setting when creating or editing a server.

### Updated
* Upgraded core to use Laravel `5.7.14`.
* Updated Simplified Chinese translation pack.

### Added
* Added support for opening and editing Python files through the web editor.
* Adds Russian translation.

## v0.7.11 (Derelict Dermodactylus)
### Fixed
* Fixes an issue with certain systems not handling an API folder that was named `API` but referenced as `Api` in the namespace.
* TS3 egg updated to use CLI arguments correctly and have a more minimalistic installation script.
* Terminal was not properly displaying longer lines leading to some visual inconsistency.
* Assorted translation updates.
* Pagination for server listing now properly respects configuration setting.
* Client API now properly respects permissions that are set and allows subusers to access their assigned servers.

### Changed
* Removed PhraseApp integration from Panel code as it is no longer used.
* SFTP login endpoint now returns the permissions for that user rather than requiring additional queries to get that data.

### Added
* You can now test your mail settings from the Admin CP without waiting to see if things are working correctly.

## v0.7.10 (Derelict Dermodactylus)
### Fixed
* Scheduled tasks triggered manually no longer improperly change the `next_run_at` time and do not run twice in a row anymore.
* Changing the maximum web-based file upload size for a node now properly validates and updates.
* Changing configuration values for a node now correctly updates them on the daemon on the first request, rather than requiring a second request to set them.

### Changed
* Egg and server variable values are no longer limited to 191 characters. Turns out some games require a large number of characters in these fields.

### Added
* Users can now select their preferred language in their account settings.

## v0.7.9 (Derelict Dermodactylus)
### Fixed
* Fixes a two-factor authentication bypass present in the password reset process for an account.

## v0.7.8 (Derelict Dermodactylus)
### Added
* Nodes can now be put into maintenance mode to deny access to servers temporarily.
* Basic statistics about your panel are now available in the Admin CP.
* Added support for using a MySQL socket location for connections rather than a TCP connection. Set a `DB_SOCKET` variable in your `.env` file to use this.

### Fixed
* Hitting Ctrl+Z when editing a file on the web now works as expected.
* Logo now links to the correct location on all pages.
* Permissions checking to determine if a user can see the task management page now works correctly.
* Fixed `pterodactyl.environment_variables` to be used correctly for global environment variables. The wrong config variable name was being using previously.
* Fixes tokens being sent to users when their account is created to actually work. Implements Laravel's internal token creation mechanisms rather than trying to do it custom.
* Updates some eggs to ensure they have the correct data and will continue working down the road. Fixes autoupdating on some source servers and MC related download links.
* Emails should send properly now when a server is marked as installed to let the owner know it is ready for action.
* Cancelling a file manager operation should cancel correctly across all browsers now.

### Changed
* Attempting to upload a folder via the web file manager will now display a warning telling the user to use SFTP.
* Changing your account password will now log out all other sessions that currently exist for that user.
* Subusers with no permissions selected can be created.

## v0.7.7 (Derelict Dermodactylus)
### Fixed
* Fixes an issue with the sidebar logo not working correctly in some browsers due to the CSS being assigned.
* Fixes a bunch of typos through the code base.
* Fixes a bug when attempting to load the dropdown menu for server owner in some cases.
* Fixes an exception thrown when the database connection address was not filled out correctly while adding a database to a server.
* Fixes some mistakes in the German translation of the panel.

### Added
* Added a new client API endpoint for gathering the utilization stats for servers including disk, cpu, and memory. `GET /api/client/servers/<id>/utilization`
* Added validation to variable validation rules to validate that the validation rules are valid because we heard you like validating your validation.
* Added German translations for many previously untranslated parts of the panel.

### Changed
* Updated core framework from Laravel 5.5 to Laravel 5.6.
* Improved support for Windows based environments.
* Spigot Egg now builds spigot for you rather than requiring a download location be specified.

## v0.7.6 (Derelict Dermodactylus)
### Fixed
* Fixes a UI error when attempting to change the default Nest and Egg for an existing server.
* Correct permissions check in UI to allow subusers with permission to `view-allocations` the ability to actually see the sidebar link.
* Fixes improper behavior when marking an egg as copying the configuration from another.
* Debug bar is only checked when the app is set to debug mode in the API session handler, rather than when it is in local mode to match the plugin settings.
* Added validation to port allocations to prevent allocation of restricted or invalid ports.
* Fix data integrity exception thrown when attempting to store updated server egg variables.
* Added missing permissions check on 'SFTP Configuration' page to ensure user has permission to access a server's SFTP server before showing a user credentials.

### Added
* Added ability for end users to change the name of their server through the UI. This option is only open to the server owner or an admin.
* Added giant warning message if you attempt to change an encryption key once one has been set.

### Changed
* Panel now throws proper 504: Gateway Timeout errors on server listing when daemon is offline.
* Sessions handled through redis now use a separate database (default `1`) to store session database to avoid logging users out when flushing the cache.
* File manager UI improved to be clearer with buttons and cleaner on mobile.
* reCAPTCHA's secret key position swapped with website key in advanced panel settings to be consistent with Google's reCAPTCHA dashboard.
* Changed DisplayException to handle its own logging correctly and check if the previous exception is marked as one that should not be logged.
* Changed 'New Folder' modal in file manager to include a trailing slash.

## v0.7.5 (Derelict Dermodactylus)
### Fixed
* Fixes application API keys being created as a client API key.
* Search term is now passed through when using paginated result sets.
* Reduces the number of SQL queries executed when rendering the server listing to increase performance.
* Fixes exceptions being thrown for non-existent subuser permissions.
* Fixes exception caused when trying to revoke admin privileges from a user account due to a bad endpoint.

### Changed
* Databases are now properly paginated when viewing a database host.
* No more loading daemon keys for every server model being loaded, some of us value our databases.
* Changed behavior of the subuser middleware to add a daemon access key if one is missing from the database for some reason.
* Server short-codes are now based on the UUID as they were in previous versions of Pterodactyl.

## v0.7.4-h1 (Derelict Dermodactylus)
### Fixed
* Being able to create servers is kind of a core aspect of the software, pushing releases late at night is not a great idea.

## v0.7.4 (Derelict Dermodactylus)
### Fixed
* Fixes a bug when reinstalling a server that would not mark the server as installing, resulting in some UI issues.
* Handle 404 errors from missing models in the application API bindings correctly.
* Fix validation error returned when no environment variables are passed, even if there are no variables required.
* Fix improper permissions on `PATCH /api/servers/<id>/startup` endpoint which was preventing editing any start variables.
* Should fix migration issues from 0.6 when there are more than API key in the database.

### Changed
* Changes order that validation of resource existence occurs in API requests to not try and use a non-existent model when validating data.

### Added
* Adds back client API for sending commands or power toggles to a server though the Panel API: `/api/client/servers/<identifier>`
* Added proper transformer for Packs and re-enabled missing includes on server.
* Added support for using Filesystem as a caching driver, although not recommended.
* Added support for user management of server databases.
* **Added bulk power management CLI interface to send start, stop, kill, restart actions to servers across configurable nodes.**

## v0.7.3 (Derelict Dermodactylus)
### Fixed
* Fixes server creation API endpoint not passing the provided `external_id` to the creation service.
* Fixes a bug causing users to be un-editable on new installations once more than one user exists.
* Fixes default order of buttons in certain parts of the panel that would default to 'Delete' rather than 'Save' when pressing enter.

### Added
* Adds ability to modify the external ID for a server through the API.

## v0.7.2 (Derelict Dermodactylus)
### Fixed
* Fixes an exception thrown when trying to access the `/nests/:id/eggs/:id` API endpoint.
* Fixes search on server listing page.
* Schedules with no names are now clickable to allow editing.
* Fixes broken permissions check that would deny access to API keys that did in fact have permission.

### Added
* Adds ability to include egg variables on an API request.
* Added `external_id` column to servers that allows for easier linking with external services such as WHMCS.
* Added back the sidebar when viewing servers that allows for quick-switching to a different server.
* Added API endpoint to get a server by external ID.

## v0.7.1 (Derelict Dermodactylus)
### Fixed
* Fixes an exception when no token is entered on the 2-Factor enable/disable page and the form is submitted.
* Fixes an exception when trying to perform actions against a User model due to a validator that could not be cast to a string correctly.
* Allow FQDNs in database host creation UI correctly.
* Fixes database naming scheme using `d###_` rather than `s###_` when creating server databases.
* Fix exception thrown when attempting to update an existing database host.

### Changed
* Adjusted exception handler behavior to log more stack information for PDO exceptions while not exposing credentials.

### Added
* Very basic cache busting until asset management can be changed to make use of better systems.

## v0.7.0 (Derelict Dermodactylus)
### Fixed
* `[rc.2]` — Fixes bad API behavior on `/user` routes.
* `[rc.2]` — Fixes Admin CP user editing resetting a password on users unintentionally.
* `[rc.2]` — Fixes bug with server creation API endpoint that would fail to validate `allocation.default` correctly.
* `[rc.2]` — Fix data integrity exception occurring due to invalid data being passed to server creation service on the API.
* `[rc.2]` — Fix data integrity exception that could occur when an email containing non-username characters was passed.
* `[rc.2]` — Fix data integrity exception occurring when no default value is provided for an egg variable.
* `[rc.2]` — Fixes a bug that would cause non-editable variables on the front-end to throw a validation error.
* `[rc.2]` — Fixes a data integrity exception occurring when saving egg variables with no value.
* Fixes a design bug in the database that prevented the storage of negative numbers, thus preventing a server from being assigned unlimited swap.
* Fixes a bug where the 'Assign New Allocations' box would only show IPs that were present in the current pagination block.
* Unable to change the daemon secret for a server via the Admin CP.
* Using default value in rules when creating a new variable if the rules is empty.
* Fixes a design-flaw in the allocation management part of nodes that would run a MySQL query for each port being allocated. This behavior is now changed to only execute one query to add multiple ports at once.
* Attempting to create a server when no nodes are configured now redirects to the node creation page.
* Fixes missing library issue for teamspeak when used with mariadb.
* Fixes inability to change the default port on front-end when viewing a server.
* Fixes bug preventing deletion of nests that have other nests referencing them as children.
* Fixes console sometimes not loading properly on slow connections

### Added
* Added ability to search the following API endpoints: list users, list servers, and list locations.
* Add support for finding a user by external ID using `/api/application/users/external/<id>` or by passing it as the search term when listing all users.
* Added a unique key to the servers table to data integrity issues where an allocation would be assigned to more than one server at once.
* Added support for editing an existing schedule.
* Added support for editing symlinked files on the Panel.
* Added new application specific API to Panel with endpoints at `/api/application`. Includes new Admin CP interface for managing keys and an easier permissions system.
* Nest and Egg listings now show the associated ID in order to make API requests easier.
* Added star indicators to user listing in Admin CP to indicate users who are set as a root admin.
* Creating a new node will now requires a SSL connection if the Panel is configured to use SSL as well.
* Socketio error messages due to permissions are now rendered correctly in the UI rather than causing a silent failure.
* File manager now supports mass deletion option for files and folders.
* Support for CS:GO as a default service option selection.
* Support for GMOD as a default service option selection.
* Added test suite for core aspects of the project (Services, Repositories, Commands, etc.) to lessen the chances for bugs to escape into releases.
* New CLI command to disabled 2-Factor Authentication on an account if necessary.
* Ability to delete users and locations via the CLI.
* You can now require 2FA for all users, admins only, or at will using a simple configuration in the Admin CP.
* **Added ability to export and import service options and their associated settings and environment variables via the Admin CP.**
* Default allocation for a server can be changed on the front-end by users. This includes two new subuser permissions as well.
* Significant improvements to environment variable control for servers. Now ships with built-in abilities to define extra variables in the Panel's configuration file, or in-code for those heavily modifying the Panel.
* Quick link to server edit view in ACP on frontend when viewing servers.
* Databases created in the Panel now include `EXECUTE` privilege.

### Changed
* PHP 7.2 is now the minimum required version for this software.
* Egg variable default values are no longer validated against the ruleset when configuring them. Validation of those rules will only occur when editing or creating a server.
* Changed logger to skip reporting stack-traces on PDO exceptions due to sensitive information being contained within.
* Changed behavior of allocation IP Address/Ports box to automatically store the value entered if a user unfocuses the field without hitting space.
* Changed order in which allocations are displayed to prioritize those with servers attached (in ascending IP & port order) followed by ascending IP & port order where no server is attached.
* Revoking the administrative status for an admin will revoke all authentication tokens currently assigned to their account.
* Updated core framework to Laravel 5.5. This includes many dependency updates.
* Certain AWS specific environment keys were changed, this should have minimal impact on users unless you specifically enabled AWS specific features. The renames are: `AWS_KEY -> AWS_ACCESS_KEY_ID`, `AWS_SECRET -> AWS_SECRET_ACCESS_KEY`, `AWS_REGION -> AWS_DEFAULT_REGION`
* API keys have been changed to only use a single public key passed in a bearer token. All existing keys can continue being used, however only the first 32 characters should be sent.
* Moved Docker image setting to be on the startup management page for a server rather than the details page. This value changes based on the Nest and Egg that are selected.
* Two-Factor authentication tokens are now 32 bytes in length, and are stored encrypted at rest in the database.
* Login page UI has been improved to be more sleek and welcoming to users.
* Changed 2FA login process to be more secure. Previously authentication checking happened on the 2FA post page, now it happens prior and is passed along to the 2FA page to avoid storing any credentials.
* **Services renamed to Nests. Service Options renamed to Eggs.** 🥚
* Theme colors and login pages updated to give a more unique feel to the project.
* Massive overhaul to the backend code that allows for much easier updating of core functionality as well as support for better testing. This overhaul also reduces complex code logic, and allows for faster response times in the application.
* CLI commands updated to be easier to type, now stored in the `p:` namespace.
* Logout icon is now more universal and not just a power icon.
* Administrative logout notice now uses SWAL rather than a generic javascript popup.
* Server creation page now only asks for a node to deploy to, rather than requiring a location and then a node.
* Database passwords are now hidden by default and will only show if clicked on. In addition, database view in ACP now indicates that passwords must be viewed on the front-end.
* Localhost cannot be used as a connection address in the environment configuration script. `127.0.0.1` is allowed.
* Application locale can now be quickly set using an environment variable `APP_LOCALE` rather than having to edit core files.

### Removed
* OOM exceptions can no longer be disabled on servers due to a startling number of users that were using it to avoid allocating proper amounts of resources to servers.
* SFTP settings page now only displays connection address and username. Password setting was removed as it is no longer necessary with Daemon changes.

## v0.7.0-rc.2 (Derelict Dermodactylus)
### Fixed
* `[rc.1]` — Fixes exception thrown when revoking user sessions.
* `[rc.1]` — Fixes exception that would occur when trying to delete allocations from a node.
* `[rc.1]` — Fixes exception thrown when attempting to adjust mail settings as well as a validation error thrown afterwards.
* `[rc.1]` — Fixes bug preventing modification of the default value for an Egg variable.
* `[rc.1]` — Fixed a bug that would occur when attempting to reset the daemon secret for a node.
* `[rc.1]` — Fix exception thrown when attempting to modify an existing database host.
* `[rc.1]` — Fix an auto deployment bug causing a node to be ignored if it had no servers already attached to it.

### Changed
* Changed logger to skip reporting stack-traces on PDO exceptions due to sensitive information being contained within.

### Added
* Added support for editing an existing schedule.

## v0.7.0-rc.1 (Derelict Dermodactylus)
### Fixed
* `[beta.4]` — Fixes some bad search and replace action that happened previously and was throwing errors when validating user permissions.
* `[beta.4]` — Fixes behavior of variable validation to not break the page when no rules are provided.
* `[beta.4]` — Fix bug preventing the editing of files in the file manager.

### Added
* Added support for editing symlinked files on the Panel.
* Added new application specific API to Panel with endpoints at `/api/application`. Includes new Admin CP interface for managing keys and an easier permissions system.

## v0.7.0-beta.4 (Derelict Dermodactylus)
### Fixed
* `[beta.3]` — Fixes a bug with the default environment file that was causing an inability to perform a fresh install when running package discovery.
* `[beta.3]` — Fixes an edge case caused by the Laravel 5.5 upgrade that would try to perform an in_array check against a null value.
* `[beta.3]` — Fixes a bug that would cause an error when attempting to create a new user on the Panel.
* `[beta.3]` — Fixes error handling of the settings service provider when no migrations have been run.
* `[beta.3]` — Fixes validation error when trying to use 'None' as the 'Copy Script From' option for an egg script.
* Fixes a design bug in the database that prevented the storage of negative numbers, thus preventing a server from being assigned unlimited swap.
* Fixes a bug where the 'Assign New Allocations' box would only show IPs that were present in the current pagination block.

### Added
* Nest and Egg listings now show the associated ID in order to make API requests easier.

### Changed
* Changed behavior of allocation IP Address/Ports box to automatically store the value entered if a user unfocuses the field without hitting space.
* Changed order in which allocations are displayed to prioritize those with servers attached (in ascending IP & port order) followed by ascending IP & port order where no server is attached.

### Removed
* OOM exceptions can no longer be disabled on servers due to a startling number of users that were using it to avoid allocating proper amounts of resources to servers.

## v0.7.0-beta.3 (Derelict Dermodactylus)
### Fixed
* `[beta.2]` — Fixes a bug that would cause an endless exception message stream in the console when attempting to setup environment settings in certain instances.
* `[beta.2]` — Fixes a bug causing the dropdown menu for a server's egg to display the wrong selected value.
* `[beta.2]` — Fixes a bug that would throw a red page of death when submitting an invalid egg variable value for a server in the Admin CP.
* `[beta.2]` — Someone found a `@todo` that I never `@todid` and thus database hosts could not be created without being linked to a node. This is fixed...
* `[beta.2]` — Fixes bug that caused incorrect rendering of CPU usage on server graphs due to missing variable.
* `[beta.2]` — Fixes bug causing schedules to be un-deletable.
* `[beta.2]` — Fixes bug that prevented the deletion of nodes due to an allocation deletion cascade issue with the SQL schema.
* `[beta.2]` — Fixes a bug causing eggs not extending other eggs to fail validation.

### Changed
* Revoking the administrative status for an admin will revoke all authentication tokens currently assigned to their account.
* Updated core framework to Laravel 5.5. This includes many dependency updates.
* Certain AWS specific environment keys were changed, this should have minimal impact on users unless you specifically enabled AWS specific features. The renames are: `AWS_KEY -> AWS_ACCESS_KEY_ID`, `AWS_SECRET -> AWS_SECRET_ACCESS_KEY`, `AWS_REGION -> AWS_DEFAULT_REGION`
* API keys have been changed to only use a single public key passed in a bearer token. All existing keys can continue being used, however only the first 32 characters should be sent.

### Added
* Added star indicators to user listing in Admin CP to indicate users who are set as a root admin.
* Creating a new node will now requires a SSL connection if the Panel is configured to use SSL as well.

## v0.7.0-beta.2 (Derelict Dermodactylus)
### Fixed
* `[beta.1]` — Fixes a CORS header issue due to a wrong API endpoint being provided in the administrative node listing.
* `[beta.1]` — Fixes bug that would prevent root admins from accessing servers they were not set as the owner of.
* `[beta.1]` — Fixes wrong URL redirect being provided when creating a subuser.
* `[beta.1]` — Fixes missing check in environment setup that would leave the Hashids salt empty.
* `[beta.1]` — Fixes bug preventing loading of allocations when trying to create a new server.
* `[beta.1]` — Fixes bug causing inability to create new servers on the Panel.
* `[beta.1]` — Fixes bug causing inability to delete an allocation due to misconfigured JS.
* `[beta.1]` — Fixes bug causing inability to set the IP alias for an allocation to an empty value.
* `[beta.1]` — Fixes bug that caused startup changes to not propagate to the server correctly on the first save.
* `[beta.1]` — Fixes bug that prevented subusers from accessing anything over socketio due to a missing permission.

### Changed
* Moved Docker image setting to be on the startup management page for a server rather than the details page. This value changes based on the Nest and Egg that are selected.
* Two-Factor authentication tokens are now 32 bytes in length, and are stored encrypted at rest in the database.
* Login page UI has been improved to be more sleek and welcoming to users.
* Changed 2FA login process to be more secure. Previously authentication checking happened on the 2FA post page, now it happens prior and is passed along to the 2FA page to avoid storing any credentials.

### Added
* Socketio error messages due to permissions are now rendered correctly in the UI rather than causing a silent failure.

## v0.7.0-beta.1 (Derelict Dermodactylus)
### Added
* File manager now supports mass deletion option for files and folders.
* Support for CS:GO as a default service option selection.
* Support for GMOD as a default service option selection.
* Added test suite for core aspects of the project (Services, Repositories, Commands, etc.) to lessen the chances for bugs to escape into releases.
* New CLI command to disabled 2-Factor Authentication on an account if necessary.
* Ability to delete users and locations via the CLI.
* You can now require 2FA for all users, admins only, or at will using a simple configuration in the Admin CP.
* **Added ability to export and import service options and their associated settings and environment variables via the Admin CP.**
* Default allocation for a server can be changed on the front-end by users. This includes two new subuser permissions as well.
* Significant improvements to environment variable control for servers. Now ships with built-in abilities to define extra variables in the Panel's configuration file, or in-code for those heavily modifying the Panel.
* Quick link to server edit view in ACP on frontend when viewing servers.
* Databases created in the Panel now include `EXECUTE` privilege.

### Changed
* **Services renamed to Nests. Service Options renamed to Eggs.** 🥚
* Theme colors and login pages updated to give a more unique feel to the project.
* Massive overhaul to the backend code that allows for much easier updating of core functionality as well as support for better testing. This overhaul also reduces complex code logic, and allows for faster response times in the application.
* CLI commands updated to be easier to type, now stored in the `p:` namespace.
* Logout icon is now more universal and not just a power icon.
* Administrative logout notice now uses SWAL rather than a generic javascript popup.
* Server creation page now only asks for a node to deploy to, rather than requiring a location and then a node.
* Database passwords are now hidden by default and will only show if clicked on. In addition, database view in ACP now indicates that passwords must be viewed on the front-end.
* Localhost cannot be used as a connection address in the environment configuration script. `127.0.0.1` is allowed.
* Application locale can now be quickly set using an environment variable `APP_LOCALE` rather than having to edit core files.

### Fixed
* Unable to change the daemon secret for a server via the Admin CP.
* Using default value in rules when creating a new variable if the rules is empty.
* Fixes a design-flaw in the allocation management part of nodes that would run a MySQL query for each port being allocated. This behavior is now changed to only execute one query to add multiple ports at once.
* Attempting to create a server when no nodes are configured now redirects to the node creation page.
* Fixes missing library issue for teamspeak when used with mariadb.
* Fixes inability to change the default port on front-end when viewing a server.
* Fixes bug preventing deletion of nests that have other nests referencing them as children.
* Fixes console sometimes not loading properly on slow connections

### Removed
* SFTP settings page now only displays connection address and username. Password setting was removed as it is no longer necessary with Daemon changes.

## v0.6.4 (Courageous Carniadactylus)
### Fixed
* Fixed the console rendering on page load, I guess people don't like watching it load line-by-line for 10 minutes. Who would have guessed...
* Re-added support for up/down arrows loading previous commands in the console window.

### Changed
* Panel API for Daemon now responds with a `HTTP/401 Unauthorized` error when unable to locate a node with a given authentication token, rather than a `HTTP/404 Not Found` response.
* Added better colors and styling for the terminal that can be adjusted per-theme.
* Session timeout adjusted to be 7 days by default.

## v0.6.3 (Courageous Carniadactylus)
### Fixed
* **[Security]** — Addresses an oversight in how the terminal rendered information sent from the server feed which allowed a malicious user to execute arbitrary commands on the game-server process itself by using a specifically crafted in-game command.

### Changed
* Removed `jquery.terminal` and replaced it with an in-house developed terminal with less potential for security issues.

## v0.6.2 (Courageous Carniadactylus)
### Fixed
* Fixes a few typos throughout the panel, there are more don't worry.
* Fixes bug when disabling 2FA due to a misnamed route.
* API now returns a 404 error when deleting a user that doesn't exist, rather than saying it was successful.
* Service variables that allow empty input now allow you to empty out the assigned value and set it back to blank.
* Fixes a bug where changing the default allocation for a server would not actually apply that allocation as the default on the daemon.
* Newly created service variables are now backfilled and assigned to existing servers properly.

### Added
* Added a `Vagrantfile` to the repository to help speed up development and testing for those who don't want to do a full dedicated install.
* Added a confirmation dialog to the logout button for admins to prevent misguided clickers from accidentally logging out when they wanted to switch to Admin or Server views.

### Changed
* Blocked out the `Reinstall` button for servers that have failed installation to avoid confusion and bugs causing the daemon to break.
* Updated dependencies, listed below.
```
aws/aws-sdk-php (3.26.5 => 3.29.7)       
laravel/framework (v5.4.21 => v5.4.27)        
barryvdh/laravel-debugbar (v2.3.2 => v2.4.0)     
fideloper/proxy (3.3.0 => 3.3.3)
igaster/laravel-theme (v1.14 => v1.16)    
laravel/tinker (v1.0.0 => v1.0.1)  
spatie/laravel-fractal (4.0.0 => 4.0.1)
```

## v0.6.1 (Courageous Carniadactylus)
### Fixed
* Fixes a bug preventing the use of services that have no variables attached to them.
* Fixes 'Remember Me' checkbox being ignored when using 2FA on an account.
* API now returns a useful error displaying what went wrong rather than an obscure 'An Error was Encountered' message when API issues arise.
* Fixes bug preventing the creation of new files in the file manager due to a missing JS dependency on page load.
* Prevent using a service option tag that contains special characters that are not valid. Now only allows alpha-numeric, no spaces or underscores.
* Fix unhandled exception due to missing `Log` class when using the API and causing an error.

### Changed
* Renamed session cookies from `laravel_session` to `pterodactyl_session`.
* Sessions are now encrypted before being stored as an additional layer of security.
* It is now possible to clear out a server description and have it be blank, rather than throwing an error about the field being required.

## v0.6.0 (Courageous Carniadactylus)
### Fixed
* Bug causing error logs to be spammed if someone timed out on an ajax based page.
* Fixes edge case where specific server names could cause daemon errors due to an invalid SFTP username being created by the panel.
* Fixes sessions being removed on browser close, and set sessions to idle for up to 3 hours before being marked as expired.
* Emails sending with 'Pterodactyl Panel' as the from name. Now configurable by using `php artisan pterodactyl:mail` to update.
* Fixes potential bug with invalid CIDR notation (ex: `192.168.1.1/z`) when adding allocations that could cause over 4 million records to be created at once.
* Fixes bug where daemon was unable to register that certain games had fully booted and were ready to play on.
* Fixes bug causing MySQL user accounts to be corrupted when resetting a password via the panel.
* Fixes remote timing attack vulnerability due to hmac comparison in API middleware.
* `[rc.1]` — Server deletion is fixed, caused by removed download table.
* `[rc.1]` — Server status indication on front-end no longer shows `Error` when server is marked as installing or suspended.
* `[rc.1]` — Fixes issues with SteamCMD not registering and installing games properly.

### Changed
* Admin API and base routes for user management now define the fields that should be passed to repositories rather than passing all fields.
* User model now defines mass assignment fields using `$fillable` rather than `$guarded`.
* 2FA checkpoint on login is now its own page, and not an AJAX based call. Improves security on that front.
* Updated Server model code to be more efficient, as well as make life easier for backend changes and work.
* Reduced the number of database queries being executed when viewing a specific server. This is done by caching the query for up to 15 minutes in memcached.
* User creation emails include more information and are sent by the event listener rather than the repository.
* Account password reset emails now auto-fill the email when clicking the link.
* New theme applied to Admin CP. Many graphical changes were made, some data was moved around and some display data changed. Too much was changed to feasibly log it all in here. Major breaking changes or notable new features will be logged.
* New server creation page now makes significantly less AJAX calls and is much quicker to respond.
* Server and Node view pages wee modified to split tabs into individual pages to make re-themeing and modifications significantly easier, and reduce MySQL query loads on page.
* Most of the backend `UnhandledException` display errors now include a clearer error that directs admins to the program's logs.
* Table seeders for services now can be run during upgrades and will attempt to locate and update, or create new if not found in the database.
* Many structural changes to the database and `Pterodactyl\Models` classes that would flood this changelog if they were all included. All required migrations included to handle database changes.
* Clarified details for database hosts to prevent users entering invalid account details, as well as renamed tables and columns relating to it to keep things clearer.
* Updated all code to be Laravel compliant when using `env()` and moved to using `config()` throughout non `config/*.php` files.
* Subuser permissions are now stored in `Permission::listPermissions()` to make views way cleaner and make adding to views significantly cleaner.
* Attempting to reset a password for an account that does not exist no longer returns an error, rather it displays a success message. Failed resets trigger a `Pterodactyl\Events\Auth\FailedPasswordReset` event that can be caught if needed to perform other actions.
* Servers are no longer queued for deletion due to the general hassle and extra logic required.
* Updated all panel components to run on Laravel v5.4 rather than 5.3 which is EOL.
* Routes are now handled in the `routes/` folder, and use a significantly cleaner syntax. Controller names and methods have been updated as well to be clearer as well as avoid conflicts with PHP reserved keywords.
* API has been completely overhauled to use new permissions system. **Any old API keys will immediately become invalid and fail to operate properly anymore. You will need to generate new keys.**
* Cleaned up dynamic database connection setting to use a single function call from the host model.
* Deleting a server safely now continues even if the daemon reports a `HTTP/404` missing server error (requires `Daemon@0.4.0-beta.2.1`)
* Changed behavior when modifying server allocation information. You can now remove the default allocation assuming you are passing a new allocation at the same time. Reduces the number of steps to change the default allocation for a server.
* Environment setting commands now attempt to auto-quote strings with spaces in them, as well as comment lines that are edited to avoid manual changes being overwritten.
* Version in footer of panel now displays correctly if panel is installed using Git rather than a download from source.
* Mobile views are now more... viewable. Fixes `col-xs-6` usage throughout the Admin CP where it was intended to be `col-md-6`.
* Node Configuration tokens and Download tokens are stored using the cache helpers rather than a database to speed up functions and make use of auto-expiration/deletion functions.
* Old daemon routes using `/remote` have been changed to use `/daemon`, panel changes now reflect this.
* Only display servers that a user is owner of or subuser of in the Admin CP rather than all servers if the user is marked as an admin.
* Panel now sends all non-default allocations as `ALLOC_#__IP` and `ALLOC_#__PORT` to the daemon, as well as the location.

### Added
* Remote routes for daemon to contact in order to allow Daemon to retrieve updated service configuration files on boot. Centralizes services to the panel rather than to each daemon.
* Basic service pack implementation to allow assignment of modpacks or software to a server to pre-install applications and allow users to update.
* Users can now have a username as well as client name assigned to their account.
* Ability to create a node through the CLI using `pterodactyl:node` as well as locations via `pterodactyl:location`.
* New theme (AdminLTE) for front-end with tweaks to backend files to work properly with it.
* Add support for PhraseApp's in-context editor
* Notifications when a user is added or removed as a subuser for a server.
* New cache policy for ServerPolicy to avoid making 15+ queries per page load when confirming if a user has permission to perform an action.
* Ability to assign multiple allocations at once when creating a new server.
* New `humanReadable` macro on `File` facade that accepts a file path and returns a human readable size. (`File::humanReadable(path, precision)`)
* Added ability to edit database host details after creation on the system.
* Login attempts and password reset requests are now protected by invisible ReCaptcha. This feature can be disabled with a `.env` variable.
* Server listing for individual users is now searchable on the front-end.
* Servers that a user is associated with as a subuser are now displayed in addition to owned servers when listing users in the Admin CP.
* Ability to launch the console in a new window as an individual unit. https://s3.kelp.in/IrTyE.png
* Server listing and view in Admin CP now shows the SFTP username/Docker container name.
* Administrative server view includes link in navigation to go to server console/frontend management.
* Added new scripts for service options that allows installation of software in a privileged Docker container on the node prior to marking a server as installed.
* Added ability to reinstall a server using the currently assigned service and option.
* Added ability to change a server's service and service option, as well as change pack assignments and other management services in that regard.
* Added support for using a proxy such as Cloudflare with a node connection. Previously there was no way to tell the panel to connect over SSL without marking the Daemon as also using SSL.

### Removed
* Removed all old theme JS and CSS folders to cleanup and avoid confusion in the future.
* Old API calls to `Server::create` will fail due to changed data structure.
* Many old routes were modified to reflect new standards in panel, and many of the controller functions being called were also modified. This shouldn't really impact anyone unless you have been digging into the code and modifying things.
* `Server::getUserDaemonSecret(Server $server)` was removed and replaced with `User::daemonSecret(Server $server)` in order to clean up models.
* `Server::getByUUID()` was replaced with `Server::byUuid()` as well as various other functions through-out the Server model.
* `Server::getHeaders()` was removed and replaced with `Server::getClient()` which returns a Guzzle Client with the correct headers already assigned.

## v0.6.0-rc.1
### Fixed
* `[beta.2.1]` — Fixed a bug preventing the deletion of a server.
* It is now possible to modify a server's disk limits after the server is created.
* `[beta.2.1]` — Fixes a bug causing login issues and password reset failures when reCAPTCHA is enabled.
* Fixes remote timing attack vulnerability due to hmac comparison in API middleware.
* `[beta.2.1]` — Fixes bug requiring docker image field to be filled out when adding a service option.
* `[beta.2.1]` — Fixes inability to mark a user as a non-admin once they were assigned the role.

### Added
* Added new scripts for service options that allows installation of software in a privileged Docker container on the node prior to marking a server as installed.
* Added ability to reinstall a server using the currently assigned service and option.
* Added ability to change a server's service and service option, as well as change pack assignments and other management services in that regard.
* Added support for using a proxy such as Cloudflare with a node connection. Previously there was no way to tell the panel to connect over SSL without marking the Daemon as also using SSL.

### Changed
* Environment setting commands now attempt to auto-quote strings with spaces in them, as well as comment lines that are edited to avoid manual changes being overwritten.
* Version in footer of panel now displays correctly if panel is installed using Git rather than a download from source.
* Mobile views are now more... viewable. Fixes `col-xs-6` usage throughout the Admin CP where it was intended to be `col-md-6`.
* Node Configuration tokens and Download tokens are stored using the cache helpers rather than a database to speed up functions and make use of auto-expiration/deletion functions.
* Old daemon routes using `/remote` have been changed to use `/daemon`, panel changes now reflect this.
* Only display servers that a user is owner of or subuser of in the Admin CP rather than all servers if the user is marked as an admin.

## v0.6.0-beta.2.1
### Fixed
* `[beta.2]` — Suspended servers now show as suspended.
* `[beta.2]` — Corrected the information when a task has not run yet.
* `[beta.2]` — Fixes filemanager 404 when editing a file within a directory.
* `[beta.2]` — Fixes exception in tasks when deleting a server.
* `[beta.2]` — Fixes bug with Terarria and Voice servers reporting a `TypeError: Service is not a constructor` in the daemon due to a missing service configuration.
* `[beta.2]` — Fixes password reset form throwing a MethodNotAllowed error when accessed.
* `[beta.2]` — Fixes invalid password bug when attempting to change account email address.
* `[beta.2]` — New attempt at fixing the issues when rendering files in the browser file editor on certain browsers.
* `[beta.2]` — Fixes broken auto-deploy time checking causing no tokens to work.
* `[beta.2]` — Fixes display of subusers after creation.
* `[beta.2]` — Fixes bug throwing model not found exception when editing an existing subuser.

### Changed
* Deleting a server safely now continues even if the daemon reports a `HTTP/404` missing server error (requires `Daemon@0.4.0-beta.2.1`)
* Changed behavior when modifying server allocation information. You can now remove the default allocation assuming you are passing a new allocation at the same time. Reduces the number of steps to change the default allocation for a server.

### Added
* Server listing and view in Admin CP now shows the SFTP username/Docker container name.
* Administrative server view includes link in navigation to go to server console/frontend management.

## v0.6.0-beta.2
### Fixed
* `[beta.1]` — Fixes task management system not running correctly.
* `[beta.1]` — Fixes API endpoint for command sending missing the required class definition.
* `[beta.1]` — Fixes panel looking for an old compiled classfile that is no longer used. This was causing errors relating to `missing class DingoAPI` when trying to upgrade the panel.
* `[beta.1]` — Should fix render issues when trying to edit some files via the panel file editor.

### Added
* Ability to launch the console in a new window as an individual unit. https://s3.kelp.in/IrTyE.png

## v0.6.0-beta.1
### Fixed
* `[pre.7]` — Fixes bug with subuser checkbox display.
* `[pre.7]` — Fixes bug with injected JS that was causing `<!DOCTYPE html>` to be ignored in templates.
* `[pre.7]` — Fixes exception thrown when trying to delete a node due to a misnamed model.
* `[pre.7]` — Fixes username vanishing on failed login attempts.
* `[pre.7]` — Terminal is now fixed to actually output all lines, rather than leaving one hanging in neverland until the browser is resized.

### Added
* Login attempts and password reset requests are now protected by invisible ReCaptcha. This feature can be disabled with a `.env` variable.
* Server listing for individual users is now searchable on the front-end.
* Servers that a user is associated with as a subuser are now displayed in addition to owned servers when listing users in the Admin CP.

### Changed
* Subuser permissions are now stored in `Permission::listPermissions()` to make views way cleaner and make adding to views significantly cleaner.
* `[pre.7]` — Sidebar for file manager now is a single link rather than a dropdown.
* Attempting to reset a password for an account that does not exist no longer returns an error, rather it displays a success message. Failed resets trigger a `Pterodactyl\Events\Auth\FailedPasswordReset` event that can be caught if needed to perform other actions.
* Servers are no longer queued for deletion due to the general hassle and extra logic required.
* Updated all panel components to run on Laravel v5.4 rather than 5.3 which is EOL.
* Routes are now handled in the `routes/` folder, and use a significantly cleaner syntax. Controller names and methods have been updated as well to be clearer as well as avoid conflicts with PHP reserved keywords.
* API has been completely overhauled to use new permissions system. **Any old API keys will immediately become invalid and fail to operate properly anymore. You will need to generate new keys.**
* Cleaned up dynamic database connection setting to use a single function call from the host model.
* `[pre.7]` — Corrected a config option for spigot servers to set a boolean value as boolean, and not as a string.

## v0.6.0-pre.7
### Fixed
* `[pre.6]` — Addresses misconfigured console queue that was still sending data way to quickly thus causing the console to explode on some devices when large amounts of data were sent.
* `[pre.6]` — Fixes bug in allocation parsing for a node that prevented adding new allocations.
* `[pre.6]` — Fixes typo in migrations that wouldn't save custom regex for non-required variables.
* `[pre.6]` — Fixes auto-deploy checkbox on server creation causing validation error.

## v0.6.0-pre.6
### Fixed
* `[pre.5]` — Console based server rebuild tool now actually rebuilds the servers with the correct information.
* `[pre.5]` — Fixes typo and wrong docker container for certain applications.

### Changed
* Removed all old theme JS and CSS folders to cleanup and avoid confusion in the future.

### Added
* `[pre.5]` — Added foreign key to `pack_id` to ensure nothing eds up breaking there.

## v0.6.0-pre.5
### Changed
* New theme applied to Admin CP. Many graphical changes were made, some data was moved around and some display data changed. Too much was changed to feasibly log it all in here. Major breaking changes or notable new features will be logged.
* New server creation page now makes significantly less AJAX calls and is much quicker to respond.
* Server and Node view pages wee modified to split tabs into individual pages to make re-themeing and modifications significantly easier, and reduce MySQL query loads on page.
* `[pre.4]` — Service and Pack management overhauled to be faster, cleaner, and more extensible in the future.
* Most of the backend `UnhandledException` display errors now include a clearer error that directs admins to the program's logs.
* Table seeders for services now can be run during upgrades and will attempt to locate and update, or create new if not found in the database.
* Many structural changes to the database and `Pterodactyl\Models` classes that would flood this changelog if they were all included. All required migrations included to handle database changes.
* `[pre.4]` — Service pack files are now stored in the database rather than on the host system to make updates easier.
* Clarified details for database hosts to prevent users entering invalid account details, as well as renamed tables and columns relating to it to keep things clearer.
* Updated all code to be Laravel compliant when using `env()` and moved to using `config()` throughout non `config/*.php` files.

### Fixed
* Fixes potential bug with invalid CIDR notation (ex: `192.168.1.1/z`) when adding allocations that could cause over 4 million records to be created at once.
* `[pre.4]` — Fixes bug preventing server updates from occurring by the system due to undefined `Auth::user()` in the event listener.
* `[pre.4]` — Fixes `Server::byUuid()` caching to actually clear the cache for *all* users, rather than the logged in user by using cache tags.
* `[pre.4]` — Fixes server listing on frontend not displaying a page selector when more than 10 servers exist.
* `[pre.4]` — Fixes non-admin users being unable to create personal API keys.
* Fixes bug where daemon was unable to register that certain games had fully booted and were ready to play on.
* Fixes bug causing MySQL user accounts to be corrupted when resetting a password via the panel.
* `[pre.4]` — Multiple clients refreshing the console no longer clears the console for all parties involved... sorry about that.
* `[pre.4]` — Fixes bug in environment setting script that would not remember defaults and try to re-assign values.

### Added
* Ability to assign multiple allocations at once when creating a new server.
* New `humanReadable` macro on `File` facade that accepts a file path and returns a human readable size. (`File::humanReadable(path, precision)`)
* Added ability to edit database host details after creation on the system.

### Deprecated
* Old API calls to `Server::create` will fail due to changed data structure.
* Many old routes were modified to reflect new standards in panel, and many of the controller functions being called were also modified. This shouldn't really impact anyone unless you have been digging into the code and modifying things.

## v0.6.0-pre.4
### Fixed
* `[pre.3]` — Fixes bug in cache handler that doesn't cache against the user making the request. Would have allowed for users to access servers not belonging to themselves in production.
* `[pre.3]` — Fixes misnamed MySQL column that was causing the inability to delete certain port ranges from the database.
* `[pre.3]` — Fixes bug preventing rebuilding server containers through the Admin CP.

### Added
* New cache policy for ServerPolicy to avoid making 15+ queries per page load when confirming if a user has permission to perform an action.

## v0.6.0-pre.3
### Fixed
* `[pre.2]` — Fixes bug where servers could not be manually deployed to nodes due to a broken SQL call.
* `[pre.2]` — Fixes inability to edit a server due to owner_id issues.
* `[pre.2]` — Fixes bug when trying to add new subusers.
* Emails sending with 'Pterodactyl Panel' as the from name. Now configurable by using `php artisan pterodactyl:mail` to update.
* `[pre.2]` — Fixes inability to delete accounts due to SQL changes.
* `[pre.2]` — Fixes bug with checking power-permissions that showed the wrong buttons. Also adds check back to sidebar to only show options a user can use.
* `[pre.2]` — Fixes allocation listing on node allocations tab as well as bug preventing deletion of port.
* `[pre.2]` — Fixes bug in services that prevented saving updated settings or creating new services.

### Changed
* `[pre.2]` — File Manager now displays relevant information on all screen sizes, and includes better button clicking mechanics for dropdown menu.
* Reduced the number of database queries being executed when viewing a specific server. This is done by caching the query for up to 60 minutes in memcached.
* User creation emails include more information and are sent by the event listener rather than the repository.
* Account password reset emails now auto-fill the email when clicking the link.

### Added
* Notifications when a user is added or removed as a subuser for a server.

## v0.6.0-pre.2
### Fixed
* `[pre.1]` — Fixes bug with database seeders that prevented correctly installing the panel.

### Changed
* `[pre.1]` — Moved around navigation bar on fronted to make it more obvious where logout and admin buttons were, as well as use the right icon for server listing.

## v0.6.0-pre.1
### Added
* Remote routes for daemon to contact in order to allow Daemon to retrieve updated service configuration files on boot. Centralizes services to the panel rather than to each daemon.
* Basic service pack implementation to allow assignment of modpacks or software to a server to pre-install applications and allow users to update.
* Users can now have a username as well as client name assigned to their account.
* Ability to create a node through the CLI using `pterodactyl:node` as well as locations via `pterodactyl:location`.
* New theme (AdminLTE) for front-end with tweaks to backend files to work properly with it.
* Add support for PhraseApp's in-context editor

### Fixed
* Bug causing error logs to be spammed if someone timed out on an ajax based page.
* Fixes edge case where specific server names could cause daemon errors due to an invalid SFTP username being created by the panel.
* Fixes sessions being removed on browser close, and set sessions to idle for up to 3 hours before being marked as expired.

### Changed
* Admin API and base routes for user management now define the fields that should be passed to repositories rather than passing all fields.
* User model now defines mass assignment fields using `$fillable` rather than `$guarded`.
* 2FA checkpoint on login is now its own page, and not an AJAX based call. Improves security on that front.
* Updated Server model code to be more efficient, as well as make life easier for backend changes and work.

### Deprecated
* `Server::getUserDaemonSecret(Server $server)` was removed and replaced with `User::daemonSecret(Server $server)` in order to clean up models.
* `Server::getByUUID()` was replaced with `Server::byUuid()` as well as various other functions through-out the Server model.
* `Server::getHeaders()` was removed and replaced with `Server::getClient()` which returns a Guzzle Client with the correct headers already assigned.

## v0.5.6 (Bodacious Boreopterus)
### Added
* Added the following languages: Estonian `et`, Dutch `nl`, Norwegian `nb` (partial), Romanian `ro`, and Russian `ru`. Interested in helping us translate the panel into more languages, or improving existing translations? Contact us on Discord and let us know.
* Added missing `strings.password` to language file for English.
* Allow listing of users from the API by passing either the user ID or their email.

### Fixed
* Fixes bug where assigning a variable a default value (or valid value) of `0` would cause the panel to reject the value thinking it did not exist.
* Addresses potential for crash by limiting total ports that can be assigned per-range to 2000.
* Fixes server names requiring at minimum 4 characters. Name can now be 1 to 200 characters long. :pencil2:
* Fixes bug that would allow adding the owner of a server as a subuser for that same server.
* Fixes bug that would allow creating multiple subusers with the same email address.
* Fixes bug where Sponge servers were improperly tagged as a spigot server in the daemon causing issues when booting or modifying configuration files.
* Use transpiled ES6 -> ES5 filemanager code in browsers.
* Fixes service option name displaying the name of a newly added variable after the variable is added and until the page is refreshed. (see #208)

### Changed
* Filemanager and EULA checking javascript is now written in pure ES6 code rather than as a blade-syntax template. This allows the use of babel to transpile into ES5 as a minified version.

## v0.5.5 (Bodacious Boreopterus)
### Added
* New API route to return allocations given a server ID. This adds support for a community-driven WHMCS module :rocket: available [here](https://github.com/hammerdawn/Pterodactyl-WHMCS).

### Fixed
* Fixes subuser display when trying to edit an existing subuser.

## v0.5.4 (Bodacious Boreopterus)
### Added
* Changing node configuration values now automatically makes a call to the daemon and updates the configuration there. Changing daemon tokens now does not require any intervention, and takes effect immediately. SSL & Port configurations will still require a daemon reboot.
* New button in file manager that triggers the right click menu to enable support on mobile devices and those who cannot right click (blessed be them).
* Support for filtering users when listing all users on the system.
* Container ID and User ID on the daemon are now shown when viewing a server in the panel.

### Changed
* File uploads now account for a maximum file size that is assigned for the daemon, and gives cleaner errors when that limit is reached.
* File upload limit can now be controlled from the panel.
* Updates regex and default values for some Minecraft services to reflect current technology.

### Fixed
* Fixes potential for generated password to not meet own validation requirements.
* Fixes some regex checking issues with newer versions of Minecraft.

## v0.5.3 (Bodacious Boreopterus)
### Fixed
* Fixed an error that occurred when viewing a node listing when no nodes were created yet due to a mis-declared variable. Also fixes a bug that would have all nodes trying to connect to the daemon using the same secret token on the node listing, causing only the last node to display properly.
* Fixes a bug that displayed the panel version rather than the daemon version when viewing a node.
* Fixes a multiplicator being applied to an overallocation field rather than a storage space field when adding a node.

### Changed
* Added a few new configuration variables for nodes to the default config, as well as a variable that will be used in future versions of the daemon.

## v0.5.2 (Bodacious Boreopterus)
### Fixed
* Time axis on server graphs is corrected to show the minutes rather than the current month.
* Node deletion now works correctly and deletes allocations as well.
* Fixes a bug that would leave orphaned databases on the system if there was an error during creation.
* Fixes an issue that could occur if a UUID contained `#e#` formatting within it when it comes to creating databases.
* Fixed node status display to account for updated daemon security changes.
* Fixes default language being selected as German (defaults to English now).
* Fixes bug preventing the deletion of database servers.

### Changed
* Using `node:<name>` when filtering servers now properly filters the servers by node name, rather than looking for the node ID.
* Using `owner:<email>` when filtering servers now properly filters by the owner's email rather than ID.
* Added some quick help buttons to the admin index page for getting support or checking the documentation.
* Panel now displays `Pterodactyl Panel` as the company name if one is not set.

### Added
* Added basic information about the daemon when viewing a node, including the host OS and version, CPU count, and the daemon version.
* Added version checking for the daemon and panel that alerts admins when daemons or the panel is out of date.
* Added multiplicator support to certain memory and disk fields that allow users to enter `10g` and have it converted to MB automatically.

## v0.5.1 (Bodacious Boreopterus)
### Fixed
* Fixes a bug that allowed a user to bypass 2FA authentication if using the correct username and password for an account.

## v0.5.0 (Bodacious Boreopterus)
After nearly a month in the works, version `v0.5.0` is finally here! 🎉

### Added
* Foreign keys are now enabled on all tables that the panel makes use of to prevent accidental data deletion when associated with other tables.
* Javascript changes to prevent crashing browsers when large quantities of data are sent over the websocket to the console. Includes a small popover message on the console to alert users that it is being throttled.
* Support for 'ARK: Survival Evolved' servers through the panel.
* Support for filtering servers within Admin CP to narrow down results by name, email, allocation, or defined fields.
* Setup scripts (user, mail, env) now support argument flags for use in containers and other non-terminal environments.
* New API endpoints for individual users to control their servers with at `/api/me/*`.
* Typeahead support for owner email when adding a new server.
* Scheduled command to clear out task log every month (configurable timespan).
* Support for allocating a FQDN as an allocation (panel will convert to IP and assign the FQDN as the alias automatically).
* Refresh files button in file manager to reload file listing without full page refresh.
* Added support for file copying through the file manager. [#127](https://github.com/Pterodactyl/Panel/issues/127)
* Creating new files and folders directly from the right-click dropdown menu in the file manager.
* Support for setting custom `user_id` when using the API to create users.
* Support for creating a new server through the API by passing a user ID rather than an email.
* Passing `?daemon=true` flag to [`/api/servers/:id`](https://pterodactyl.readme.io/v0.5.0/reference#single-server) will return the daemon stats as well as the `daemon_token` if using HTTPS.
* Small check for current node status that shows up to the left of the name when viewing a listing of all nodes.
* Support for creating server without having to assign a node and allocation manually. Simply select the checkbox or pass `auto_deploy=true` to the API to auto-select a node and allocation given a location.
* Support for setting IP Aliases through the panel on the node overview page. Also cleaned up allocation removal.
* Support for renaming files through the panel's file manager.

### Changed
* Servers are now queued for deletion to allow for cancellation of deletion, as well as run in the background to speed up page loading.
* Switched to new graphing library to make graphs less... broken.
* Rebuild triggers are only sent to the node if there is actually something changed that requires a rebuild.
* Dependencies are now hard-coded into the `composer.json` file to prevent users installing slightly different versions with different features or bugs.
* Server related tasks now use the lowest priority queue to prevent clogging the pipes when there are more important tasks to be run by the panel.
* Dates displayed in the file manager are now more user friendly.
* Creating a user, server, or node now returns `HTTP/1.1 200` and a JSON element with the user/server/node's ID.
* Environment setting script is much more user friendly and does not require an excessive amount of clicking and typing.
* File upload method switched from BinaryJS to Socket.io implementation to fix bugs as well as be a little speedier and allow upload throttling.
* `Server::getbyUUID()` now accepts either the `uuidShort` or full-length `uuid` for server identification.
* API keys are tied to individual users and no longer created through the Admin CP.
* **ALL** API routes previously returning paginated result sets, or result sets nested inside a descriptive block (e.g. `servers:`) have been changed to return a single array of all associated items. Please see the [updated documentation](https://pterodactyl.readme.io/v0.5.0/reference) for how this change might effect your API use.
* API route for [`/api/users/:id`](https://pterodactyl.readme.io/v0.5.0/reference#single-user) now includes an array of all servers the user is set as the owner of.
* Prevent clicking server start button until server is completely off, not just stopping.
* Upon successful creation of a node it will redirect to the allocation tab and display a clearer message to add allocations.
* Trying to add a new node if no location exists redirects user to location management page and alerts them to add a location first.
* `Server\AjaxController@postSetConnection` is now `Server\AjaxController@postSetPrimary` and accepts one post parameter of `allocation` rather than a combined `ip:port` value.
* Port allocations on server view are now cleaner and should make more sense.
* Improved File Manager
  * Rewritten Javascript to load, rename, and handle other file actions.
  * Uses Ace Editor for editing files rather than a non-formatted textarea
  * File actions that were previously icons to the right are now contained in a menu that appears when right-clicking a file or folder.

### Fixed
* Fixes bug where resetting a user password through the login form would not hold passwords to the same requirements as the rest of the panel (mixed case and at least one numeric character).
* Fixes bug where no error would be displayed when adding a new server with an invalid owner email.
* Fixes a bug that could allow an admin to delete the default allocation for a server causing all sorts of issues.
* Databases assigned to a server are now actually deleted when a server is removed.
* Server overview listing the location short-code as the name of the node.
* Server task manager only sending commands every 5 minutes at the quickest.
* Fixes additional port allocation from removing the wrong row when clicking 'x'.
* Updated Socket.io client file to version `1.5.0` to match the latest release. Correlates with setting hard dependencies in the Daemon.
* Team Fortress named 'Insurgency' in panel in database seeder. ([#96](https://github.com/Pterodactyl/Panel/issues/96), PR by [@MeltedLux](https://github.com/MeltedLux))
* Server allocation listing display now showing the connection IP unless an alias was assigned.
* Fixed bug where node allocation would appear to be successful but actual encounter an error. Made it cleared how to enter ports.
* Fixes display where an extra space was added to the end of SFTP passwords when they were copied from the panel. [#116](https://github.com/Pterodactyl/Panel/issues/116), thanks [@OrangeJuiced](https://github.com/OrangeJuiced)
* Fixes a bug that prevented viewing database servers if not assigned to a node.
* Checkboxes previously not displayed checkmarks are now fixed.

### Fixed (bugs from v0.5.0-rc.2)
* Fixes a bug causing password resets to fail for server databases.
* Fixes a bug during installation that would prevent the 'Ark: Survival Evolved' service option from being added to the panel unless it was an update.
* Fixes constant scrolling to bottom of console; console now only scrolls to the bottom on new data.

### Removed
* Removed active session management table displaying the last location of a session.
* Removed online player listing due to inconsistency in query library and an assortment of query related bugs. This will return in future versions when we get it working correctly.

## v0.5.0-rc.2 (Bodacious Boreopterus)

### Fixed
* Fixes a bug that would cause MySQL errors when attempting to install the panel rather than upgrading.

## v0.5.0-rc.1 (Bodacious Boreopterus)

### Added
* Foreign keys are now enabled on all tables that the panel makes use of to prevent accidental data deletion when associated with other tables.
* Javascript changes to prevent crashing browsers when large quantities of data are sent over the websocket to the console. Includes a small popover message on the console to alert users that it is being throttled.
* Support for 'ARK: Survival Evolved' servers through the panel.

### Fixed
* Fixes bug where resetting a user password through the login form would not hold passwords to the same requirements as the rest of the panel (mixed case and at least one numeric character).
* Fixes misnamed environment variable for Bungeecord Servers (`BUNGE_VERSION` -> `BUNGEE_VERSION`).
* Fixes bug where no error would be displayed when adding a new server with an invalid owner email.
* Fixes a bug that could allow an admin to delete the default allocation for a server causing all sorts of issues.
* Databases assigned to a server are now actually deleted when a server is removed.
* Fixes file uploads being improperly throttled.

### Changed
* Servers are now queued for deletion to allow for cancellation of deletion, as well as run in the background to speed up page loading.
* Switched to new graphing library to make graphs less... broken.
* Rebuild triggers are only sent to the node if there is actually something changed that requires a rebuild.
* Dependencies are now hard-coded into the `composer.json` file to prevent users installing slightly different versions with different features or bugs.
* Server related tasks now use the lowest priority queue to prevent clogging the pipes when there are more important tasks to be run by the panel.
* Decompressing files now shows a pop-over box that does not dismiss until it is complete.
* Dates displayed in the file manager are now more user friendly.

### Removed
* Removed online player listing due to inconsistency in query library and an assortment of query related bugs. This will return in future versions when we get it working correctly.

## v0.5.0-pre.3 (Bodacious Boreopterus)

### Added
* Return node configuration from remote API by using `/api/nodes/{id}/config` endpoint. Only accepts SSL connections.
* Support for filtering servers within Admin CP to narrow down results by name, email, allocation, or defined fields.
* Setup scripts (user, mail, env) now support argument flags for use in containers and other non-terminal environments.
* New API endpoints for individual users to control their servers with at `/api/me/*`.
* Typeahead support for owner email when adding a new server.
* Scheduled command to clear out task log every month (configurable timespan).
* Support for allocating a FQDN as an allocation (panel will convert to IP and assign the FQDN as the alias automatically).
* Refresh files button in file manager to reload file listing without full page refresh.

### Changed
* Creating a user, server, or node now returns `HTTP/1.1 200` and a JSON element with the user/server/node's ID.
* Environment setting script is much more user friendly and does not require an excessive amount of clicking and typing.
* File upload method switched from BinaryJS to Socket.io implementation to fix bugs as well as be a little speedier and allow upload throttling.
* `Server::getbyUUID()` now accepts either the `uuidShort` or full-length `uuid` for server identification.
* API keys are tied to individual users and no longer created through the Admin CP.

### Fixed
* Server overview listing the location short-code as the name of the node.
* Server task manager only sending commands every 5 minutes at the quickest.
* Fixes additional port allocation from removing the wrong row when clicking 'x'.

## v0.5.0-pre.2 (Bodacious Boreopterus)

### Added
* Added support for file copying through the file manager. [#127](https://github.com/Pterodactyl/Panel/issues/127)
* Creating new files and folders directly from the right-click dropdown menu in the file manager.
* Support for setting custom `user_id` when using the API to create users.
* Support for creating a new server through the API by passing a user ID rather than an email.
* Passing `?daemon=true` flag to [`/api/servers/:id`](https://pterodactyl.readme.io/v0.5.0/reference#single-server) will return the daemon stats as well as the `daemon_token` if using HTTPS.
* Small check for current node status that shows up to the left of the name when viewing a listing of all nodes.

### Changed
* Support for sub-folders within the `getJavascript()` route for servers.
* **ALL** API routes previously returning paginated result sets, or result sets nested inside a descriptive block (e.g. `servers:`) have been changed to return a single array of all associated items. Please see the [updated documentation](https://pterodactyl.readme.io/v0.5.0/reference) for how this change might effect your API use.
* API route for [`/api/users/:id`](https://pterodactyl.readme.io/v0.5.0/reference#single-user) now includes an array of all servers the user is set as the owner of.

### Fixed
* File manager would do multiple up-down-up-down loading actions if you escaped renaming a file. Fixed the binding issue. [#122](https://github.com/Pterodactyl/Panel/issues/122)
* File manager actions would not trigger properly if text in a row was used to right-click from.
* File manager rename field would not disappear when pressing the escape key in chrome. [#121](https://github.com/Pterodactyl/Panel/issues/121)
* Fixes bug where server image assigned was not being saved to the database.
* Fixes instances where selecting auto-deploy would not hide the node selection dropdown.
* Fixes bug in auto-deployment that would throw a `ModelNotFoundException` if the location passed was not valid. Not normally an issue in the panel, but caused display issues for the API.
* Updated Socket.io client file to version `1.5.0` to match the latest release. Correlates with setting hard dependencies in the Daemon.

## v0.5.0-pre.1 (Bodacious Boreopterus)

### Added
* Support for creating server without having to assign a node and allocation manually. Simply select the checkbox or pass `auto_deploy=true` to the API to auto-select a node and allocation given a location.
* Support for setting IP Aliases through the panel on the node overview page. Also cleaned up allocation removal.
* Support for renaming files through the panel's file manager.

### Changed
* Prevent clicking server start button until server is completely off, not just stopping.
* Upon successful creation of a node it will redirect to the allocation tab and display a clearer message to add allocations.
* Trying to add a new node if no location exists redirects user to location management page and alerts them to add a location first.
* `Server\AjaxController@postSetConnection` is now `Server\AjaxController@postSetPrimary` and accepts one post parameter of `allocation` rather than a combined `ip:port` value.
* Port allocations on server view are now cleaner and should make more sense.
* Improved File Manager
  * Rewritten Javascript to load, rename, and handle other file actions.
  * Uses Ace Editor for editing files rather than a non-formatted textarea
  * File actions that were previously icons to the right are now contained in a menu that appears when right-clicking a file or folder.

### Fixed
* Team Fortress named 'Insurgency' in panel in database seeder. ([#96](https://github.com/Pterodactyl/Panel/issues/96), PR by [@MeltedLux](https://github.com/MeltedLux))
* Server allocation listing display now showing the connection IP unless an alias was assigned.
* Fixed bug where node allocation would appear to be successful but actual encounter an error. Made it cleared how to enter ports.
* Fixes display where an extra space was added to the end of SFTP passwords when they were copied from the panel. [#116](https://github.com/Pterodactyl/Panel/issues/116), thanks [@OrangeJuiced](https://github.com/OrangeJuiced)

### Removed
* Removed active session management table displaying the last location of a session.

## v0.4.1 (Articulate Aerotitan)

### Changed
* Overallocate fields are now auto-filled with a value of `0`

### Fixed
* Wrong error highlighting of overallocate fields on Node creation ([#90](https://github.com/Pterodactyl/Panel/issues/90), thanks [@schrej](https://github.com/schrej))
* Server link in navbar directed to 404 link (PR by [@Randomfish132](https://github.com/Randomfish132))
* Composer fails to finish ([#92](https://github.com/Pterodactyl/Panel/issues/92), PR by [@schrej](https://github.com/schrej), thanks [@parkervcp](https://github.com/parkervcp))

## v0.4.0 (Arty Aerodactylus)

### Added
* Task scheduler supporting customized CRON syntax or dropdown selected options. (currently only support command and power options)
* Adds support for changing per-server database passwords from the panel.
* Allows for use of IP rather than a FQDN if the node is not using SSL
* Adds support for IP Aliases on display pages for users. This makes it possible to use GRE tunnels and still show the user what IP they should be connecting to.
* Adds support for suspending servers
* Adds support for viewing SFTP password within the panel ([#74](https://github.com/Pterodactyl/Panel/issues/74), thanks [@ET-Bent](https://github.com/ET-Bent))
* Improved API with support for server suspension and build modification.
* Improved service management and setup on first install.
* New terminal that supports ANSI color codes as well as cleaner output. You can also simply type `start` or `boot` to start your server rather than having to use the start button.

### Fixed
* Fixes password auto-generation on 'Manage Server' page. ([#67](https://github.com/Pterodactyl/Panel/issues/67), thanks [@ET-Bent](https://github.com/ET-Bent))
* Fixes some overly verbose user output when an error occurs
* Prevent calling daemon until database call has been confirmed when changing default connection.
* Fixes a few display issues relating to subusers and database management.
* Fixes the server name in the header not linking to the server correctly. ([#79](https://github.com/Pterodactyl/Panel/issues/79), thanks [@xX1bumblebee1Xx](https://github.com/xX1bumblebee1Xx))
* Fixes bug where non-admins could not see command box on servers. ([#83](https://github.com/Pterodactyl/Panel/issues/83), thanks [@xX1bumblebee1Xx](https://github.com/xX1bumblebee1Xx))
* Fixes bug where files could not be uploaded through the "click and select" system, only through "drag and drop." ([#82](https://github.com/Pterodactyl/Panel/issues/83), thanks [@xX1bumblebee1Xx](https://github.com/xX1bumblebee1Xx))
* Fixes a bug where new files could not be created through the panel for a server. ([#85](https://github.com/Pterodactyl/Panel/issues/85), thanks [@xX1bumblebee1Xx](https://github.com/xX1bumblebee1Xx))
* Fixes the exception handler to properly display and log exceptions that might occur rather than leaving a vague error. ([#81](https://github.com/Pterodactyl/Panel/issues/83))

### Changed
* Update Laravel to version `5.3` and update dependencies.

### Deprecated
* Requires Pterodactyl Daemon `v0.2.*`

### Security
* Fixes listing of server variables for server. Previously a bug made it possible to view settings for all servers, even if the user didn't own that server. ([#69](https://github.com/Pterodactyl/Panel/issues/69))

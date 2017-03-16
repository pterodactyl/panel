# Changelog
This file is a running track of new features and fixes to each version of the panel released starting with `v0.4.0`.

This project follows [Semantic Versioning](http://semver.org) guidelines.

## v0.6.0-pre.5 (Courageous Carniadactylus)
### Changed
* New theme applied to Admin CP. Many graphical changes were made, some data was moved around and some display data changed. Too much was changed to feasibly log it all in here. Major breaking changes or notable new features will be logged.
* New server creation page now makes significantly less AJAX calls and is much quicker to respond.
* Server and Node view pages wee modified to split tabs into individual pages to make re-themeing and modifications significantly easier, and reduce MySQL query loads on page.
* `[pre.4]` — Services and Pack magement overhauled to be faster, cleaner, and more extensible in the future.
* Most of the backend `UnhandledException` display errors now include a clearer error that directs admins to the program's logs.
* Table seeders for services now can be run during upgrades and will attempt to locate and update, or create new if not found in the database.
* Many structural changes to the database and `Pterodactyl\Models` classes that would flood this changelog if they were all included. All required migrations included to handle database changes.
* `[pre.4]` — Service pack files are now stored in the database rather than on the host system to make updates easier.
* Clarified details for database hosts to prevent users entering invalid account details, as well as renamed tables and columns relating to it to keep things clearer.

### Fixed
* Fixes potential bug with invalid CIDR notation (ex: `192.168.1.1/z`) when adding allocations that could cause over 4 million records to be created at once.
* `[pre.4]` — Fixes bug preventing server updates from occurring by the system due to undefined `Auth::user()` in the event listener.
* `[pre.4]` — Fixes `Server::byUuid()` caching to actually clear the cache for *all* users, rather than the logged in user by using cache tags.
* `[pre.4]` — Fixes server listing on frontend not displaying a page selector when more than 10 servers exist.
* `[pre.4]` — Fixes non-admin users being unable to create personal API keys.

### Added
* Ability to assign multiple allocations at once when creating a new server.
* New `humanReadable` macro on `File` facade that accepts a file path and returns a human readable size. (`File::humanReadable(path, precision)`)
* Added ability to edit database host details after creation on the system.

### Deprecated
* Old API calls to `Server::create` will fail due to changed data structure.
* Many old routes were modified to reflect new standards in panel, and many of the controller functions being called were also modified. This shouldn't really impact anyone unless you have been digging into the code and modifying things.

## v0.6.0-pre.4 (Courageous Carniadactylus)
### Fixed
* `[pre.3]` — Fixes bug in cache handler that doesn't cache against the user making the request. Would have allowed for users to access servers not belonging to themselves in production.
* `[pre.3]` — Fixes misnamed MySQL column that was causing the inability to delete certain port ranges from the database.
* `[pre.3]` — Fixes bug preventing rebuilding server containers through the Admin CP.

### Added
* New cache policy for ServerPolicy to avoid making 15+ queries per page load when confirming if a user has permission to perform an action.

## v0.6.0-pre.3 (Courageous Carniadactylus)
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

## v0.6.0-pre.2 (Courageous Carniadactylus)
### Fixed
* `[pre.1]` — Fixes bug with database seeders that prevented correctly installing the panel.

### Changed
* `[pre.1]` — Moved around navigation bar on fronted to make it more obvious where logout and admin buttons were, as well as use the right icon for server listing.

## v0.6.0-pre.1 (Courageous Carniadactylus)
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
* Fixes service option name displaying the name of a nwly added variable after the variable is added and until the page is refreshed. (see #208)

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
* Support for renaming files through the panel's file mananger.

### Changed
* Servers are now queued for deletion to allow for cancellation of deletion, as well as run in the background to speed up page loading.
* Switched to new graphing library to make graphs less... broken.
* Rebuild triggers are only sent to the node if there is actually something changed that requires a rebuild.
* Dependencies are now hard-coded into the `composer.json` file to prevent users installing slightly different versions with different features or bugs.
* Server related tasks now use the lowest priorty queue to prevent clogging the pipes when there are more important tasks to be run by the panel.
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
* Server related tasks now use the lowest priorty queue to prevent clogging the pipes when there are more important tasks to be run by the panel.
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
* Support for renaming files through the panel's file mananger.

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

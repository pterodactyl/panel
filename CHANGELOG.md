# Changelog
This file is a running track of new features and fixes to each version of the panel released starting with `v0.4.0`.

This project follows [Semantic Versioning](http://semver.org) guidelines.

## v0.5.0 (Bodacious Boreopterus) [Unreleased]
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

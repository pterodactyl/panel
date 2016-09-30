# Changelog
This file is a running track of new features and fixes to each version of the panel released starting with `v0.4.0`.

This project follows [Semantic Versioning](http://semver.org) guidelines.

## v0.5.0 (Bodacious Boreopterus) [Unreleased]

### Added
* Support for creating server without having to assign a node and allocation manually. Simply select the checkbox or pass `auto_deploy=true` to the API to auto-select a node and allocation given a location.
* Support for setting IP Aliases through the panel on the node overview page. Also cleaned up allocation removal.

### Changed
* Prevent clicking server start button until server is completely off, not just stopping.
* Upon successful creation of a node it will redirect to the allocation tab and display a clearer message to add allocations.
* Trying to add a new node if no location exists redirects user to location management page and alerts them to add a location first.

### Fixed
* Team Fortress named 'Insurgency' in panel in database seeder. ([#96](https://github.com/Pterodactyl/Panel/issues/96), PR by [@MeltedLux](https://github.com/MeltedLux))
* Server allocation listing display now showing the connection IP unless an alias was assigned.
* Fixed bug where node allocation would appear to be successful but actual encounter an error. Made it cleared how to enter ports.

### Deprecated
### Removed
### Security

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

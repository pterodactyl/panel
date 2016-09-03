# Changelog
This file is a running track of new features and fixes to each version of the panel released starting with `v0.4.0`.

## v0.4.0 (release scheduled ~ Mid September)

### New Features
* Task scheduler supporting customized CRON syntax or dropdown selected options. (currently only support command and power options)
* Adds support for changing per-server database passwords from the panel.
* Allows for use of IP rather than a FQDN if the node is not using SSL
* Adds support for IP Aliases on display pages for users. This makes it possible to use GRE tunnels and still show the user what IP they should be connecting to.
* Adds support for suspending servers
* Adds support for viewing SFTP password within the panel (#74, thanks @ET-Bent)

### Bug Fixes
* Fixes password auto-generation on 'Manage Server' page. (#67, thanks @ET-Bent)
* Fixes some overly verbose user output when an error occurs
* [Security Patch] Fixes listing of server variables for server. Previously a bug made it possible to view settings for all servers, even if the user didn't own that server. (#69)
* Prevent calling daemon until database call has been confirmed when changing default connection.
* Fixes a few display issues relating to subusers and database management.

### General
* Update Laravel to version `5.3` and update dependencies. **[BREAKING]** This removes the remote API from the panel due to Dingo API instability. This message will be removed when it is added back.

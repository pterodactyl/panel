Configuring phpMyAdmin
----------------------

There are many configuration settings that can be used to customize the
interface. Those settings are described in
:ref:`config`. There are several layers of the configuration.

The global settings can be configured in :file:`config.inc.php` as described in
:ref:`config`. This is only way to configure connections to databases and other
system wide settings.

On top of this there are user settings which can be persistently stored in
:ref:`linked-tables`, possibly automatically configured through
:ref:`zeroconf`.  If the :ref:`linked-tables` are not configured, the settings
are temporarily stored in the session data; these are valid only until you
logout.

You can also save the user configuration for further use, either download them
as a file or to the browser local storage. You can find both those options in
the :guilabel:`Settings` tab. The settings stored in browser local storage will
be automatically offered for loading upon your login to phpMyAdmin.

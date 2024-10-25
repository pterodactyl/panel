.. _themes:

Custom Themes
=============

phpMyAdmin comes with support for third party themes. You can download
additional themes from our website at <https://www.phpmyadmin.net/themes/>.

Configuration
-------------

Themes are configured with :config:option:`$cfg['ThemeManager']` and
:config:option:`$cfg['ThemeDefault']`.  Under :file:`./themes/`, you should not
delete the directory ``pmahomme`` or its underlying structure, because this is
the system theme used by phpMyAdmin. ``pmahomme`` contains all images and
styles, for backwards compatibility and for all themes that would not include
images or css-files.  If :config:option:`$cfg['ThemeManager']` is enabled, you
can select your favorite theme on the main page. Your selected theme will be
stored in a cookie.

Creating custom theme
---------------------

To create a theme:

* make a new subdirectory (for example "your\_theme\_name") under :file:`./themes/`.
* copy the files and directories from ``pmahomme`` to "your\_theme\_name"
* edit the css-files in "your\_theme\_name/css"
* put your new images in "your\_theme\_name/img"
* edit :file:`_variables.scss` in "your\_theme\_name/scss"
* edit :file:`theme.json` in "your\_theme\_name" to contain theme metadata (see below)
* make a new screenshot of your theme and save it under
  "your\_theme\_name/screen.png"

Theme metadata
++++++++++++++

.. versionchanged:: 4.8.0

    Before 4.8.0 the theme metadata was passed in the :file:`info.inc.php` file.
    It has been replaced by :file:`theme.json` to allow easier parsing (without
    need to handle PHP code) and to support additional features.

In theme directory there is file :file:`theme.json` which contains theme
metadata. Currently it consists of:

.. describe:: name

    Display name of the theme.

    **This field is required.**

.. describe:: version

    Theme version, can be quite arbitrary and does not have to match phpMyAdmin version.

    **This field is required.**

.. describe:: description

    Theme description. this will be shown on the website.

    **This field is required.**

.. describe:: author

    Theme author name.

    **This field is required.**

.. describe:: url

    Link to theme author website. It's good idea to have way for getting
    support there.

.. describe:: supports

    Array of supported phpMyAdmin major versions.

    **This field is required.**

For example, the definition for Original theme shipped with phpMyAdmin 4.8:

.. code-block:: json

    {
        "name": "Original",
        "version": "4.8",
        "description": "Original phpMyAdmin theme",
        "author": "phpMyAdmin developers",
        "url": "https://www.phpmyadmin.net/",
        "supports": ["4.8"]
    }

Sharing images
++++++++++++++

If you do not want to use your own symbols and buttons, remove the
directory "img" in "your\_theme\_name". phpMyAdmin will use the
default icons and buttons (from the system-theme ``pmahomme``).

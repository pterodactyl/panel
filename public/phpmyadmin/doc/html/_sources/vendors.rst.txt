Distributing and packaging phpMyAdmin
=====================================

This document is intended to give pieces of advice to people who want to
redistribute phpMyAdmin inside other software packages such as Linux
distribution or some all in one package including web server and MySQL
server.

Generally, you can customize some basic aspects (paths to some files and
behavior) in :file:`libraries/vendor_config.php`.

For example, if you want setup script to generate a config file in var, change
``SETUP_CONFIG_FILE`` to :file:`/var/lib/phpmyadmin/config.inc.php` and you
will also probably want to skip directory writable check, so set
``SETUP_DIR_WRITABLE`` to false.

External libraries
------------------

phpMyAdmin includes several external libraries, you might want to
replace them with system ones if they are available, but please note
that you should test whether the version you provide is compatible with the
one we ship.

Currently known list of external libraries:

js/vendor
    jQuery js framework libraries and various js libraries.

vendor/
    The download kit includes various Composer packages as
    dependencies.

Specific files LICENSES
-----------------------

phpMyAdmin distributed themes contain some content that is under licenses.

- The icons of the `Original` and `pmahomme` themes are from the `Silk Icons <https://web.archive.org/web/20221201060206/http://www.famfamfam.com/lab/icons/silk/>`_.
- Some icons of the `Metro` theme are from the `Silk Icons <https://web.archive.org/web/20221201060206/http://www.famfamfam.com/lab/icons/silk/>`_.
- `themes/*/img/b_rename.svg` Is a `Icons8 <https://thenounproject.com/Icons8/>`_, icon from the `Android L Icon Pack Collection <https://thenounproject.com/Icons8/collection/android-l-icon-pack/>`_. The icon `rename <https://thenounproject.com/term/rename/61456/>`_.
- `themes/metro/img/user.svg` Is a IcoMoon the `user <https://github.com/Keyamoon/IcoMoon-Free/blob/master/SVG/114-user.svg>`_

CC BY 4.0 or GPL

Licenses for vendors
--------------------

- Silk Icons are under the `CC BY 2.5 or CC BY 3.0 <https://web.archive.org/web/20221201060206/http://www.famfamfam.com/lab/icons/silk/>`_ licenses.
- `rename` from `Icons8` is under the `"public domain" <https://creativecommons.org/publicdomain/zero/1.0/>`_ (CC0 1.0) license.
- IcoMoon Free is under `"CC BY 4.0 or GPL" <https://github.com/Keyamoon/IcoMoon-Free/blob/master/License.txt>`_.

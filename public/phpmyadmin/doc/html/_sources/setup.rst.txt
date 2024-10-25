.. _setup:

Installation
============

phpMyAdmin does not apply any special security methods to the MySQL
database server. It is still the system administrator's job to grant
permissions on the MySQL databases properly. phpMyAdmin's :guilabel:`Users`
page can be used for this.

Linux distributions
+++++++++++++++++++

phpMyAdmin is included in most Linux distributions. It is recommended to use
distribution packages when possible - they usually provide integration to your
distribution and you will automatically get security updates from your distribution.

.. _debian-package:

Debian and Ubuntu
-----------------

Most Debian and Ubuntu versions include a phpMyAdmin package, but be aware that
the configuration file is maintained in ``/etc/phpmyadmin`` and may differ in
some ways from the official phpMyAdmin documentation. Specifically, it does:

* Configuration of a web server (works for Apache and lighttpd).
* Creating of :ref:`linked-tables` using dbconfig-common.
* Securing setup script, see :ref:`debian-setup`.

More specific details about installing Debian or Ubuntu packages are available
`in our wiki <https://github.com/phpmyadmin/phpmyadmin/wiki/DebianUbuntu>`_.

.. seealso::

    More information can be found in `README.Debian <https://salsa.debian.org/phpmyadmin-team/phpmyadmin/blob/debian/latest/debian/README.Debian>`_
    (it is installed as :file:`/usr/share/doc/phpmyadmin/README.Debian` with the package).

OpenSUSE
--------

OpenSUSE already comes with phpMyAdmin package, just install packages from
the `openSUSE Build Service <https://software.opensuse.org/package/phpMyAdmin>`_.

Gentoo
------

Gentoo ships the phpMyAdmin package, both in a near-stock configuration as well
as in a ``webapp-config`` configuration. Use ``emerge dev-db/phpmyadmin`` to
install.

Mandriva
--------

Mandriva ships the phpMyAdmin package in their ``contrib`` branch and can be
installed via the usual Control Center.

Fedora
------

Fedora ships the phpMyAdmin package, but be aware that the configuration file
is maintained in ``/etc/phpMyAdmin/`` and may differ in some ways from the
official phpMyAdmin documentation.

Red Hat Enterprise Linux
------------------------

Red Hat Enterprise Linux itself and thus derivatives like CentOS don't
ship phpMyAdmin, but the Fedora-driven repository
`Extra Packages for Enterprise Linux (EPEL) <https://fedoraproject.org/wiki/EPEL>`_
is doing so, if it's
`enabled <https://fedoraproject.org/wiki/EPEL/FAQ#howtouse>`_.
But be aware that the configuration file is maintained in
``/etc/phpMyAdmin/`` and may differ in some ways from the
official phpMyAdmin documentation.

Installing on Windows
+++++++++++++++++++++

The easiest way to get phpMyAdmin on Windows is using third party products
which include phpMyAdmin together with a database and web server such as
`XAMPP <https://www.apachefriends.org/index.html>`_.

You can find more of such options at `Wikipedia <https://en.wikipedia.org/wiki/List_of_AMP_packages>`_.

Installing from Git
+++++++++++++++++++

In order to install from Git, you'll need a few supporting applications:

* `Git <https://git-scm.com/downloads>`_ to download the source, or you can download the most recent source directly from `Github <https://codeload.github.com/phpmyadmin/phpmyadmin/zip/QA_5_2>`_
* `Composer <https://getcomposer.org/download/>`__
* `Node.js <https://nodejs.org/en/download/>`_ (version 10 or higher)
* `Yarn <https://classic.yarnpkg.com/en/docs/install>`_

You can clone current phpMyAdmin source from
``https://github.com/phpmyadmin/phpmyadmin.git``:

.. code-block:: sh

    git clone https://github.com/phpmyadmin/phpmyadmin.git

Additionally you need to install dependencies using `Composer <https://getcomposer.org>`__:

.. code-block:: sh

    composer update

If you do not intend to develop, you can skip the installation of developer tools
by invoking:

.. code-block:: sh

    composer update --no-dev

Finally, you'll need to use `Yarn`_ to install some JavaScript dependencies:

.. code-block:: sh

    yarn install --production

.. _composer:

Installing using Composer
+++++++++++++++++++++++++

You can install phpMyAdmin using the `Composer tool`_, since 4.7.0 the releases
are automatically mirrored to the default `Packagist`_ repository.

.. note::

    The content of the Composer repository is automatically generated
    separately from the releases, so the content doesn't have to be
    100% same as when you download the tarball. There should be no
    functional differences though.

To install phpMyAdmin simply run:

.. code-block:: sh

    composer create-project phpmyadmin/phpmyadmin

Alternatively you can use our own composer repository, which contains
the release tarballs and is available at
<https://www.phpmyadmin.net/packages.json>:

.. code-block:: sh

    composer create-project phpmyadmin/phpmyadmin --repository-url=https://www.phpmyadmin.net/packages.json --no-dev

.. _docker:

Installing using Docker
+++++++++++++++++++++++

phpMyAdmin comes with a `Docker official image`_, which you can easily deploy. You can
download it using:

.. code-block:: sh

    docker pull phpmyadmin

The phpMyAdmin server will listen on port 80. It supports several ways of
configuring the link to the database server, either by Docker's link feature
by linking your database container to ``db`` for phpMyAdmin (by specifying
``--link your_db_host:db``) or by environment variables (in this case it's up
to you to set up networking in Docker to allow the phpMyAdmin container to access
the database container over the network).

.. _docker-vars:

Docker environment variables
----------------------------

You can configure several phpMyAdmin features using environment variables:

.. envvar:: PMA_ARBITRARY

    Allows you to enter a database server hostname on login form.

    .. seealso:: :config:option:`$cfg['AllowArbitraryServer']`

.. envvar:: PMA_HOST

    Hostname or IP address of the database server to use.

    .. seealso:: :config:option:`$cfg['Servers'][$i]['host']`

.. envvar:: PMA_HOSTS

    Comma-separated hostnames or IP addresses of the database servers to use.

    .. note:: Used only if :envvar:`PMA_HOST` is empty.

.. envvar:: PMA_VERBOSE

    Verbose name of the database server.

    .. seealso:: :config:option:`$cfg['Servers'][$i]['verbose']`

.. envvar:: PMA_VERBOSES

    Comma-separated verbose name of the database servers.

    .. note:: Used only if :envvar:`PMA_VERBOSE` is empty.

.. envvar:: PMA_USER

    User name to use for :ref:`auth_config`.

.. envvar:: PMA_PASSWORD

    Password to use for :ref:`auth_config`.

.. envvar:: PMA_PORT

    Port of the database server to use.

.. envvar:: PMA_PORTS

    Comma-separated ports of the database server to use.

    .. note:: Used only if :envvar:`PMA_PORT` is empty.

.. envvar:: PMA_ABSOLUTE_URI

    The fully-qualified path (``https://pma.example.net/``) where the reverse
    proxy makes phpMyAdmin available.

    .. seealso:: :config:option:`$cfg['PmaAbsoluteUri']`

.. envvar:: PMA_QUERYHISTORYDB

    When set to `true`, enables storing SQL history to :config:option:`$cfg['Servers'][$i]['pmadb']`.
    When `false`, history is stored in the browser and is cleared when logging out.

    .. seealso:: :config:option:`$cfg['Servers'][$i]['history']`
    .. seealso:: :config:option:`$cfg['QueryHistoryDB']`

.. envvar:: PMA_QUERYHISTORYMAX

    When set to an integer, controls the number of history items.

    .. seealso:: :config:option:`$cfg['QueryHistoryMax']`

.. envvar:: PMA_CONTROLHOST

    When set, this points to an alternate database host used for storing the ":ref:`linked-tables`" database.

    .. seealso:: :config:option:`$cfg['Servers'][$i]['controlhost']`

.. envvar:: PMA_CONTROLUSER

    Defines the username for phpMyAdmin to use for the ":ref:`linked-tables`" database.

    .. seealso:: :config:option:`$cfg['Servers'][$i]['controluser']`

.. envvar:: PMA_CONTROLPASS

    Defines the password for phpMyAdmin to use for the ":ref:`linked-tables`" database.

    .. seealso:: :config:option:`$cfg['Servers'][$i]['controlpass']`

.. envvar:: PMA_CONTROLPORT

    When set, will override the default port (`3306`) for connecting to the control host.

    .. seealso:: :config:option:`$cfg['Servers'][$i]['controlport']`

.. envvar:: PMA_PMADB

    When set, define the name of the database to be used for the ":ref:`linked-tables`" database.
    When not set, the advanced features are not enabled by default: they can still potentially be enabled by the user when logging in with the :ref:`zeroconf` feature.

    .. note:: Suggested values: `phpmyadmin` or `pmadb`

    .. seealso:: :config:option:`$cfg['Servers'][$i]['pmadb']`

.. envvar:: HIDE_PHP_VERSION

    If defined, this option will hide the PHP version (`expose_php = Off`).
    Set to any value (such as `HIDE_PHP_VERSION=true`).

.. envvar:: UPLOAD_LIMIT

    If set, this option will override the default value for apache and php-fpm (this will change ``upload_max_filesize`` and ``post_max_size`` values).

    .. note:: Format as `[0-9+](K,M,G)` default value is `2048K`

.. envvar:: MEMORY_LIMIT

    If set, this option will override the phpMyAdmin memory limit :config:option:`$cfg['MemoryLimit']` and PHP's `memory_limit`.

    .. note:: Format as `[0-9+](K,M,G)` where `K` is for Kilobytes, `M` for Megabytes, `G` for Gigabytes and `1K` = 1024 bytes. Default value is `512M`.

.. envvar:: MAX_EXECUTION_TIME

    If set, this option will override the maximum execution time in seconds for phpMyAdmin :config:option:`$cfg['ExecTimeLimit']` and PHP's `max_execution_time`.

    .. note:: Format as `[0-9+]`. Default value is `600`.

.. envvar:: PMA_CONFIG_BASE64

    If set, this option will override the default `config.inc.php` with the base64 decoded contents of the variable.

.. envvar:: PMA_USER_CONFIG_BASE64

    If set, this option will override the default `config.user.inc.php` with the base64 decoded contents of the variable.

.. envvar:: PMA_UPLOADDIR

    If set, this option will set the path where files can be saved to be available to import (:config:option:`$cfg['UploadDir']`)

.. envvar:: PMA_SAVEDIR

    If set, this option will set the path where exported files can be saved (:config:option:`$cfg['SaveDir']`)

.. envvar:: APACHE_PORT

    If set, this option will change the default Apache port from `80` in case you want it to run on a different port like an unprivileged port. Set to any port value (such as `APACHE_PORT=8090`).

By default, :ref:`cookie` is used, but if :envvar:`PMA_USER` and
:envvar:`PMA_PASSWORD` are set, it is switched to :ref:`auth_config`.

.. note::

    The credentials you need to log in are stored in the MySQL server, in case
    of Docker image, there are various ways to set it (for example
    :samp:`MYSQL_ROOT_PASSWORD` when starting the MySQL container). Please check
    documentation for `MariaDB container <https://hub.docker.com/_/mariadb>`_
    or `MySQL container <https://hub.docker.com/_/mysql>`_.

.. _docker-custom:

Customizing configuration
-------------------------

Additionally configuration can be tweaked by :file:`/etc/phpmyadmin/config.user.inc.php`. If
this file exists, it will be loaded after configuration is generated from above
environment variables, so you can override any configuration variable. This
configuration can be added as a volume when invoking docker using
`-v /some/local/directory/config.user.inc.php:/etc/phpmyadmin/config.user.inc.php` parameters.

Note that the supplied configuration file is applied after :ref:`docker-vars`,
but you can override any of the values.

For example to change the default behavior of CSV export you can use the following
configuration file:

.. code-block:: php

    <?php
    $cfg['Export']['csv_columns'] = true;

You can also use it to define server configuration instead of using the
environment variables listed in :ref:`docker-vars`:

.. code-block:: php

    <?php
    /* Override Servers array */
    $cfg['Servers'] = [
        1 => [
            'auth_type' => 'cookie',
            'host' => 'mydb1',
            'port' => 3306,
            'verbose' => 'Verbose name 1',
        ],
        2 => [
            'auth_type' => 'cookie',
            'host' => 'mydb2',
            'port' => 3306,
            'verbose' => 'Verbose name 2',
        ],
    ];

.. seealso::

    See :ref:`config` for detailed description of configuration options.

Docker Volumes
--------------

You can use the following volumes to customize image behavior:

:file:`/etc/phpmyadmin/config.user.inc.php`

    Can be used for additional settings, see the previous chapter for more details.

:file:`/sessions/`

    Directory where PHP sessions are stored. You might want to share this
    for example when using :ref:`auth_signon`.

:file:`/www/themes/`

    Directory where phpMyAdmin looks for themes. By default only those shipped
    with phpMyAdmin are included, but you can include additional phpMyAdmin
    themes (see :ref:`themes`) by using Docker volumes.

Docker Examples
---------------

To connect phpMyAdmin to a given server use:

.. code-block:: sh

    docker run --name phpmyadmin -d -e PMA_HOST=dbhost -p 8080:80 phpmyadmin:latest

To connect phpMyAdmin to more servers use:

.. code-block:: sh

    docker run --name phpmyadmin -d -e PMA_HOSTS=dbhost1,dbhost2,dbhost3 -p 8080:80 phpmyadmin:latest

To use arbitrary server option:

.. code-block:: sh

    docker run --name phpmyadmin -d --link mysql_db_server:db -p 8080:80 -e PMA_ARBITRARY=1 phpmyadmin:latest

You can also link the database container using Docker:

.. code-block:: sh

    docker run --name phpmyadmin -d --link mysql_db_server:db -p 8080:80 phpmyadmin:latest

Running with additional configuration:

.. code-block:: sh

    docker run --name phpmyadmin -d --link mysql_db_server:db -p 8080:80 -v /some/local/directory/config.user.inc.php:/etc/phpmyadmin/config.user.inc.php phpmyadmin:latest

Running with additional themes:

.. code-block:: sh

    docker run --name phpmyadmin -d --link mysql_db_server:db -p 8080:80 -v /some/local/directory/custom/phpmyadmin/themeName/:/var/www/html/themes/themeName/ phpmyadmin:latest

Using docker-compose
--------------------

Alternatively, you can also use docker-compose with the docker-compose.yml from
<https://github.com/phpmyadmin/docker>.  This will run phpMyAdmin with an
arbitrary server - allowing you to specify MySQL/MariaDB server on the login page.

.. code-block:: sh

    docker-compose up -d

Customizing configuration file using docker-compose
---------------------------------------------------

You can use an external file to customize phpMyAdmin configuration and pass it
using the volumes directive:

.. code-block:: yaml

    phpmyadmin:
        image: phpmyadmin:latest
        container_name: phpmyadmin
        environment:
         - PMA_ARBITRARY=1
        restart: always
        ports:
         - 8080:80
        volumes:
         - /sessions
         - ~/docker/phpmyadmin/config.user.inc.php:/etc/phpmyadmin/config.user.inc.php
         - /custom/phpmyadmin/theme/:/www/themes/theme/

.. seealso:: :ref:`docker-custom`

Running behind haproxy in a subdirectory
----------------------------------------

When you want to expose phpMyAdmin running in a Docker container in a
subdirectory, you need to rewrite the request path in the server proxying the
requests.

For example, using haproxy it can be done as:

.. code-block:: text

    frontend http
        bind *:80
        option forwardfor
        option http-server-close

        ### NETWORK restriction
        acl LOCALNET  src 10.0.0.0/8 192.168.0.0/16 172.16.0.0/12

        # /phpmyadmin
        acl phpmyadmin  path_dir /phpmyadmin
        use_backend phpmyadmin if phpmyadmin LOCALNET

    backend phpmyadmin
        mode http

        reqirep  ^(GET|POST|HEAD)\ /phpmyadmin/(.*)     \1\ /\2

        # phpMyAdmin container IP
        server localhost     172.30.21.21:80

When using traefik, something like following should work:

.. code-block:: text

    defaultEntryPoints = ["http"]
    [entryPoints]
      [entryPoints.http]
      address = ":80"
        [entryPoints.http.redirect]
          regex = "(http:\\/\\/[^\\/]+\\/([^\\?\\.]+)[^\\/])$"
          replacement = "$1/"

    [backends]
      [backends.myadmin]
        [backends.myadmin.servers.myadmin]
        url="http://internal.address.to.pma"

    [frontends]
       [frontends.myadmin]
       backend = "myadmin"
       passHostHeader = true
         [frontends.myadmin.routes.default]
         rule="PathPrefixStrip:/phpmyadmin/;AddPrefix:/"

You then should specify :envvar:`PMA_ABSOLUTE_URI` in the docker-compose
configuration:

.. code-block:: yaml

    version: '2'

    services:
      phpmyadmin:
        restart: always
        image: phpmyadmin:latest
        container_name: phpmyadmin
        hostname: phpmyadmin
        domainname: example.com
        ports:
          - 8000:80
        environment:
          - PMA_HOSTS=172.26.36.7,172.26.36.8,172.26.36.9,172.26.36.10
          - PMA_VERBOSES=production-db1,production-db2,dev-db1,dev-db2
          - PMA_USER=root
          - PMA_PASSWORD=
          - PMA_ABSOLUTE_URI=http://example.com/phpmyadmin/

IBM Cloud
+++++++++

One of our users has created a helpful guide for installing phpMyAdmin on the
`IBM Cloud platform <https://github.com/KissConsult/phpmyadmin_tutorial#readme>`_.

.. _quick_install:

Quick Install
+++++++++++++

#. Choose an appropriate distribution kit from the phpmyadmin.net
   Downloads page. Some kits contain only the English messages, others
   contain all languages. We'll assume you chose a kit whose name
   looks like ``phpMyAdmin-x.x.x -all-languages.tar.gz``.
#. Ensure you have downloaded a genuine archive, see :ref:`verify`.
#. Untar or unzip the distribution (be sure to unzip the subdirectories):
   ``tar -xzvf phpMyAdmin_x.x.x-all-languages.tar.gz`` in your
   webserver's document root. If you don't have direct access to your
   document root, put the files in a directory on your local machine,
   and, after step 4, transfer the directory on your web server using,
   for example, FTP.
#. Ensure that all the scripts have the appropriate owner (if PHP is
   running in safe mode, having some scripts with an owner different from
   the owner of other scripts will be a problem). See :ref:`faq4_2` and
   :ref:`faq1_26` for suggestions.
#. Now you must configure your installation. There are two methods that
   can be used. Traditionally, users have hand-edited a copy of
   :file:`config.inc.php`, but now a wizard-style setup script is provided
   for those who prefer a graphical installation. Creating a
   :file:`config.inc.php` is still a quick way to get started and needed for
   some advanced features.

Manually creating the file
--------------------------

To manually create the file, simply use your text editor to create the
file :file:`config.inc.php` (you can copy :file:`config.sample.inc.php` to get
a minimal configuration file) in the main (top-level) phpMyAdmin
directory (the one that contains :file:`index.php`). phpMyAdmin first
loads the default configuration values and then overrides those values
with anything found in :file:`config.inc.php`. If the default value is
okay for a particular setting, there is no need to include it in
:file:`config.inc.php`. You'll probably need only a few directives to get going; a
simple configuration may look like this:

.. code-block:: xml+php

    <?php
    // The string is a hexadecimal representation of a 32-bytes long string of random bytes.
    $cfg['blowfish_secret'] = sodium_hex2bin('f16ce59f45714194371b48fe362072dc3b019da7861558cd4ad29e4d6fb13851');

    $i=0;
    $i++;
    $cfg['Servers'][$i]['auth_type']     = 'cookie';
    // if you insist on "root" having no password:
    // $cfg['Servers'][$i]['AllowNoPassword'] = true;

Or, if you prefer to not be prompted every time you log in:

.. code-block:: xml+php

    <?php

    $i=0;
    $i++;
    $cfg['Servers'][$i]['user']          = 'root';
    $cfg['Servers'][$i]['password']      = 'changeme'; // use here your password
    $cfg['Servers'][$i]['auth_type']     = 'config';

.. warning::

    Storing passwords in the configuration is insecure as anybody can then
    manipulate your database.

For a full explanation of possible configuration values, see the
:ref:`config` of this document.

.. index:: Setup script

.. _setup_script:

Using the Setup script
----------------------

Instead of manually editing :file:`config.inc.php`, you can use phpMyAdmin's
setup feature. The file can be generated using the setup and you can download it
for upload to the server.

Next, open your browser and visit the location where you installed phpMyAdmin,
with the ``/setup`` suffix. The changes are not saved to the server, you need to
use the :guilabel:`Download` button to save them to your computer and then upload
to the server.

Now the file is ready to be used. You can choose to review or edit the
file with your favorite editor, if you prefer to set some advanced
options that the setup script does not provide.

#. If you are using the ``auth_type`` "config", it is suggested that you
   protect the phpMyAdmin installation directory because using config
   does not require a user to enter a password to access the phpMyAdmin
   installation. Use of an alternate authentication method is
   recommended, for example with HTTP–AUTH in a :term:`.htaccess` file or switch to using
   ``auth_type`` cookie or http. See the :ref:`faqmultiuser`
   for additional information, especially :ref:`faq4_4`.
#. Open the main phpMyAdmin directory in your browser.
   phpMyAdmin should now display a welcome screen and your databases, or
   a login dialog if using :term:`HTTP` or
   cookie authentication mode.

.. _debian-setup:

Setup script on Debian, Ubuntu and derivatives
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Debian and Ubuntu have changed the way in which the setup script is enabled and disabled, in a way
that single command has to be executed for either of these.

To allow editing configuration invoke:

.. code-block:: sh

   /usr/sbin/pma-configure

To block editing configuration invoke:

.. code-block:: sh

    /usr/sbin/pma-secure

Setup script on openSUSE
~~~~~~~~~~~~~~~~~~~~~~~~

Some openSUSE releases do not include setup script in the package. In case you
want to generate configuration on these you can either download original
package from <https://www.phpmyadmin.net/> or use setup script on our demo
server: <https://demo.phpmyadmin.net/master/setup/>.

.. _verify:

Verifying phpMyAdmin releases
+++++++++++++++++++++++++++++

Since July 2015 all phpMyAdmin releases are cryptographically signed by the
releasing developer, who through January 2016 was Marc Delisle. His key id is
0xFEFC65D181AF644A, his PGP fingerprint is:

.. code-block:: console

    436F F188 4B1A 0C3F DCBF 0D79 FEFC 65D1 81AF 644A

and you can get more identification information from <https://keybase.io/lem9>.

Beginning in January 2016, the release manager is Isaac Bennetch. His key id is
0xCE752F178259BD92, and his PGP fingerprint is:

.. code-block:: console

    3D06 A59E CE73 0EB7 1B51 1C17 CE75 2F17 8259 BD92

and you can get more identification information from <https://keybase.io/ibennetch>.

Some additional downloads (for example themes) might be signed by Michal Čihař. His key id is
0x9C27B31342B7511D, and his PGP fingerprint is:

.. code-block:: console

    63CB 1DF1 EF12 CF2A C0EE 5A32 9C27 B313 42B7 511D

and you can get more identification information from <https://keybase.io/nijel>.

You should verify that the signature matches the archive you have downloaded.
This way you can be sure that you are using the same code that was released.
You should also verify the date of the signature to make sure that you
downloaded the latest version.

Each archive is accompanied by ``.asc`` files which contain the PGP signature
for it. Once you have both of them in the same folder, you can verify the signature:

.. code-block:: console

    $ gpg --verify phpMyAdmin-4.5.4.1-all-languages.zip.asc
    gpg: Signature made Fri 29 Jan 2016 08:59:37 AM EST using RSA key ID 8259BD92
    gpg: Can't check signature: public key not found

As you can see gpg complains that it does not know the public key. At this
point, you should do one of the following steps:

* Download the keyring from `our download server <https://files.phpmyadmin.net/phpmyadmin.keyring>`_, then import it with:

.. code-block:: console

   $ gpg --import phpmyadmin.keyring

* Download and import the key from one of the key servers:

.. code-block:: console

    $ gpg --keyserver hkp://pgp.mit.edu --recv-keys 3D06A59ECE730EB71B511C17CE752F178259BD92
    gpg: requesting key 8259BD92 from hkp server pgp.mit.edu
    gpg: key 8259BD92: public key "Isaac Bennetch <bennetch@gmail.com>" imported
    gpg: no ultimately trusted keys found
    gpg: Total number processed: 1
    gpg:               imported: 1  (RSA: 1)

This will improve the situation a bit - at this point, you can verify that the
signature from the given key is correct but you still can not trust the name used
in the key:

.. code-block:: console

    $ gpg --verify phpMyAdmin-4.5.4.1-all-languages.zip.asc
    gpg: Signature made Fri 29 Jan 2016 08:59:37 AM EST using RSA key ID 8259BD92
    gpg: Good signature from "Isaac Bennetch <bennetch@gmail.com>"
    gpg:                 aka "Isaac Bennetch <isaac@bennetch.org>"
    gpg: WARNING: This key is not certified with a trusted signature!
    gpg:          There is no indication that the signature belongs to the owner.
    Primary key fingerprint: 3D06 A59E CE73 0EB7 1B51  1C17 CE75 2F17 8259 BD92

The problem here is that anybody could issue the key with this name.  You need to
ensure that the key is actually owned by the mentioned person.  The GNU Privacy
Handbook covers this topic in the chapter `Validating other keys on your public
keyring`_. The most reliable method is to meet the developer in person and
exchange key fingerprints, however, you can also rely on the web of trust. This way
you can trust the key transitively though signatures of others, who have met
the developer in person.

Once the key is trusted, the warning will not occur:

.. code-block:: console

    $ gpg --verify phpMyAdmin-4.5.4.1-all-languages.zip.asc
    gpg: Signature made Fri 29 Jan 2016 08:59:37 AM EST using RSA key ID 8259BD92
    gpg: Good signature from "Isaac Bennetch <bennetch@gmail.com>" [full]

Should the signature be invalid (the archive has been changed), you would get a
clear error regardless of the fact that the key is trusted or not:

.. code-block:: console

    $ gpg --verify phpMyAdmin-4.5.4.1-all-languages.zip.asc
    gpg: Signature made Fri 29 Jan 2016 08:59:37 AM EST using RSA key ID 8259BD92
    gpg: BAD signature from "Isaac Bennetch <bennetch@gmail.com>" [unknown]

.. _Validating other keys on your public keyring: https://www.gnupg.org/gph/en/manual.html#AEN335

.. index::
    single: Configuration storage
    single: phpMyAdmin configuration storage
    single: pmadb

.. _linked-tables:

phpMyAdmin configuration storage
++++++++++++++++++++++++++++++++

.. versionchanged:: 3.4.0

   Prior to phpMyAdmin 3.4.0 this was called Linked Tables Infrastructure, but
   the name was changed due to the extended scope of the storage.

For a whole set of additional features (:ref:`bookmarks`, comments, :term:`SQL`-history,
tracking mechanism, :term:`PDF`-generation, :ref:`transformations`, :ref:`relations`
etc.) you need to create a set of special tables.  Those tables can be located
in your own database, or in a central database for a multi-user installation
(this database would then be accessed by the controluser, so no other user
should have rights to it).

.. _zeroconf:

Zero configuration
------------------

In many cases, this database structure can be automatically created and
configured. This is called “Zero Configuration” mode and can be particularly
useful in shared hosting situations. “Zeroconf” mode is on by default, to
disable set :config:option:`$cfg['ZeroConf']` to false.

The following three scenarios are covered by the Zero Configuration mode:

* When entering a database where the configuration storage tables are not
  present, phpMyAdmin offers to create them from the Operations tab.
* When entering a database where the tables do already exist, the software
  automatically detects this and begins using them. This is the most common
  situation; after the tables are initially created automatically they are
  continually used without disturbing the user; this is also most useful on
  shared hosting where the user is not able to edit :file:`config.inc.php` and
  usually the user only has access to one database.
* When having access to multiple databases, if the user first enters the
  database containing the configuration storage tables then switches to
  another database,
  phpMyAdmin continues to use the tables from the first database; the user is
  not prompted to create more tables in the new database.

Manual configuration
--------------------

Please look at your ``./sql/`` directory, where you should find a
file called *create\_tables.sql*. (If you are using a Windows server,
pay special attention to :ref:`faq1_23`).

If you already had this infrastructure and:

* upgraded to MySQL 4.1.2 or newer, please use
  :file:`sql/upgrade_tables_mysql_4_1_2+.sql`.
* upgraded to phpMyAdmin 4.3.0 or newer from 2.5.0 or newer (<= 4.2.x),
  please use :file:`sql/upgrade_column_info_4_3_0+.sql`.
* upgraded to phpMyAdmin 4.7.0 or newer from 4.3.0 or newer,
  please use :file:`sql/upgrade_tables_4_7_0+.sql`.

and then create new tables by importing :file:`sql/create_tables.sql`.

You can use your phpMyAdmin to create the tables for you. Please be
aware that you may need special (administrator) privileges to create
the database and tables, and that the script may need some tuning,
depending on the database name.

After having imported the :file:`sql/create_tables.sql` file, you
should specify the table names in your :file:`config.inc.php` file. The
directives used for that can be found in the :ref:`config`.

You will also need to have a controluser
(:config:option:`$cfg['Servers'][$i]['controluser']` and
:config:option:`$cfg['Servers'][$i]['controlpass']` settings)
with the proper rights to those tables. For example you can create it
using following statement:

And for any MariaDB version:

.. code-block:: mysql

   CREATE USER 'pma'@'localhost' IDENTIFIED VIA mysql_native_password USING 'pmapass';
   GRANT SELECT, INSERT, UPDATE, DELETE ON `<pma_db>`.* TO 'pma'@'localhost';

For MySQL 8.0 and newer:

.. code-block:: mysql

   CREATE USER 'pma'@'localhost' IDENTIFIED WITH caching_sha2_password BY 'pmapass';
   GRANT SELECT, INSERT, UPDATE, DELETE ON <pma_db>.* TO 'pma'@'localhost';

For MySQL older than 8.0:

.. code-block:: mysql

   CREATE USER 'pma'@'localhost' IDENTIFIED WITH mysql_native_password AS 'pmapass';
   GRANT SELECT, INSERT, UPDATE, DELETE ON <pma_db>.* TO 'pma'@'localhost';

Note that MySQL installations with PHP older than 7.4 and MySQL newer than 8.0 may require
using the mysql_native_password authentication as a workaround, see
:ref:`faq1_45` for details.

.. _upgrading:

Upgrading from an older version
+++++++++++++++++++++++++++++++

.. warning::

    **Never** extract the new version over an existing installation of
    phpMyAdmin, always first remove the old files keeping just the
    configuration.

    This way, you will not leave any old or outdated files in the directory,
    which can have severe security implications or can cause various breakages.

Simply copy :file:`config.inc.php` from your previous installation into
the newly unpacked one. Configuration files from old versions may
require some tweaking as some options have been changed or removed.
For compatibility with PHP 5.3 and later, remove a
``set_magic_quotes_runtime(0);`` statement that you might find near
the end of your configuration file.

The complete upgrade can be performed in a few simple steps:

1. Download the latest phpMyAdmin version from <https://www.phpmyadmin.net/downloads/>.
2. Rename existing phpMyAdmin folder (for example to ``phpmyadmin-old``).
3. Unpack freshly downloaded phpMyAdmin to the desired location (for example ``phpmyadmin``).
4. Copy :file:`config.inc.php`` from old location (``phpmyadmin-old``) to the new one (``phpmyadmin``).
5. Test that everything works properly.
6. Remove backup of a previous version (``phpmyadmin-old``).

If you have upgraded your MySQL server from a version previous to 4.1.2 to
version 5.x or newer and if you use the phpMyAdmin configuration storage, you
should run the :term:`SQL` script found in
:file:`sql/upgrade_tables_mysql_4_1_2+.sql`.

If you have upgraded your phpMyAdmin to 4.3.0 or newer from 2.5.0 or
newer (<= 4.2.x) and if you use the phpMyAdmin configuration storage, you
should run the :term:`SQL` script found in
:file:`sql/upgrade_column_info_4_3_0+.sql`.

Do not forget to clear the browser cache and to empty the old session by
logging out and logging in again.

.. index:: Authentication mode

.. _authentication_modes:

Using authentication modes
++++++++++++++++++++++++++

:term:`HTTP` and cookie authentication modes are recommended in a **multi-user
environment** where you want to give users access to their own database and
don't want them to play around with others. Nevertheless, be aware that MS
Internet Explorer seems to be really buggy about cookies, at least till version
6. Even in a **single-user environment**, you might prefer to use :term:`HTTP`
or cookie mode so that your user/password pair are not in clear in the
configuration file.

:term:`HTTP` and cookie authentication
modes are more secure: the MySQL login information does not need to be
set in the phpMyAdmin configuration file (except possibly for the
:config:option:`$cfg['Servers'][$i]['controluser']`).
However, keep in mind that the password travels in plain text unless
you are using the HTTPS protocol. In cookie mode, the password is
stored, encrypted with the AES algorithm, in a temporary cookie.

Then each of the *true* users should be granted a set of privileges
on a set of particular databases. Normally you shouldn't give global
privileges to an ordinary user unless you understand the impact of those
privileges (for example, you are creating a superuser).
For example, to grant the user *real_user* with all privileges on
the database *user_base*:

.. code-block:: mysql

   GRANT ALL PRIVILEGES ON user_base.* TO 'real_user'@localhost IDENTIFIED BY 'real_password';

What the user may now do is controlled entirely by the MySQL user management
system. With HTTP or cookie authentication mode, you don't need to fill the
user/password fields inside the :config:option:`$cfg['Servers']`.

.. seealso::

    :ref:`faq1_32`,
    :ref:`faq1_35`,
    :ref:`faq4_1`,
    :ref:`faq4_2`,
    :ref:`faq4_3`

.. index:: pair: HTTP; Authentication mode

.. _auth_http:

HTTP authentication mode
------------------------

* Uses :term:`HTTP` Basic authentication
  method and allows you to log in as any valid MySQL user.
* Is supported with most PHP configurations. For :term:`IIS` (:term:`ISAPI`)
  support using :term:`CGI` PHP see :ref:`faq1_32`, for using with Apache
  :term:`CGI` see :ref:`faq1_35`.
* When PHP is running under Apache's :term:`mod_proxy_fcgi` (e.g. with PHP-FPM),
  ``Authorization`` headers are not passed to the underlying FCGI application,
  such that your credentials will not reach the application. In this case, you can
  add the following configuration directive:

  .. code-block:: apache

     SetEnvIf Authorization "(.*)" HTTP_AUTHORIZATION=$1

* See also :ref:`faq4_4` about not using the :term:`.htaccess` mechanism along with
  ':term:`HTTP`' authentication mode.

.. note::

    There is no way to do proper logout in HTTP authentication, most browsers
    will remember credentials until there is no different successful
    authentication. Because of this, this method has a limitation that you can not
    login with the same user after logout.

.. index:: pair: Cookie; Authentication mode

.. _cookie:

Cookie authentication mode
--------------------------

* Username and password are stored in cookies during the session and password
  is deleted when it ends.
* With this mode, the user can truly log out of phpMyAdmin and log
  back in with the same username (this is not possible with :ref:`auth_http`).
* If you want to allow users to enter any hostname to connect (rather than only
  servers that are configured in :file:`config.inc.php`),
  see the :config:option:`$cfg['AllowArbitraryServer']` directive.
* As mentioned in the :ref:`require` section, having the ``openssl`` extension
  will speed up access considerably, but is not required.

.. index:: pair: Signon; Authentication mode

.. _auth_signon:

Signon authentication mode
--------------------------

* This mode is a convenient way of using credentials from another
  application to authenticate to phpMyAdmin to implement a single signon
  solution.
* The other application has to store login information into session
  data (see :config:option:`$cfg['Servers'][$i]['SignonSession']` and
  :config:option:`$cfg['Servers'][$i]['SignonCookieParams']`) or you
  need to implement script to return the credentials (see
  :config:option:`$cfg['Servers'][$i]['SignonScript']`).
* When no credentials are available, the user is being redirected to
  :config:option:`$cfg['Servers'][$i]['SignonURL']`, where you should handle
  the login process.

The very basic example of saving credentials in a session is available as
:file:`examples/signon.php`:

.. literalinclude:: ../examples/signon.php
    :language: php

Alternatively, you can also use this way to integrate with OpenID as shown
in :file:`examples/openid.php`:

.. literalinclude:: ../examples/openid.php
    :language: php

If you intend to pass the credentials using some other means than, you have to
implement wrapper in PHP to get that data and set it to
:config:option:`$cfg['Servers'][$i]['SignonScript']`. There is a very minimal example
in :file:`examples/signon-script.php`:

.. literalinclude:: ../examples/signon-script.php
    :language: php

.. seealso::
    :config:option:`$cfg['Servers'][$i]['auth_type']`,
    :config:option:`$cfg['Servers'][$i]['SignonSession']`,
    :config:option:`$cfg['Servers'][$i]['SignonCookieParams']`,
    :config:option:`$cfg['Servers'][$i]['SignonScript']`,
    :config:option:`$cfg['Servers'][$i]['SignonURL']`,
    :ref:`example-signon`

.. index:: pair: Config; Authentication mode

.. _auth_config:

Config authentication mode
--------------------------

* This mode is sometimes the less secure one because it requires you to fill the
  :config:option:`$cfg['Servers'][$i]['user']` and
  :config:option:`$cfg['Servers'][$i]['password']`
  fields (and as a result, anyone who can read your :file:`config.inc.php`
  can discover your username and password).
* In the :ref:`faqmultiuser` section, there is an entry explaining how
  to protect your configuration file.
* For additional security in this mode, you may wish to consider the
  Host authentication :config:option:`$cfg['Servers'][$i]['AllowDeny']['order']`
  and :config:option:`$cfg['Servers'][$i]['AllowDeny']['rules']` configuration directives.
* Unlike cookie and http, does not require a user to log in when first
  loading the phpMyAdmin site. This is by design but could allow any
  user to access your installation. Use of some restriction method is
  suggested, perhaps a :term:`.htaccess` file with the HTTP-AUTH directive or disallowing
  incoming HTTP requests at one’s router or firewall will suffice (both
  of which are beyond the scope of this manual but easily searchable
  with Google).

.. _securing:

Securing your phpMyAdmin installation
+++++++++++++++++++++++++++++++++++++

The phpMyAdmin team tries hard to make the application secure, however there
are always ways to make your installation more secure:

* Follow our `Security announcements <https://www.phpmyadmin.net/security/>`_ and upgrade
  phpMyAdmin whenever new vulnerability is published.
* Serve phpMyAdmin on HTTPS only. Preferably, you should use HSTS as well, so that
  you're protected from protocol downgrade attacks.
* Ensure your PHP setup follows recommendations for production sites, for example
  `display_errors <https://www.php.net/manual/en/errorfunc.configuration.php#ini.display-errors>`_
  should be disabled.
* Remove the ``test`` directory from phpMyAdmin, unless you are developing and need a test suite.
* Remove the ``setup`` directory from phpMyAdmin, you will probably not
  use it after the initial setup.
* Properly choose an authentication method - :ref:`cookie`
  is probably the best choice for shared hosting.
* Deny access to auxiliary files in :file:`./libraries/` or
  :file:`./templates/` subfolders in your webserver configuration.
  Such configuration prevents from possible path exposure and cross side
  scripting vulnerabilities that might happen to be found in that code. For the
  Apache webserver, this is often accomplished with a :term:`.htaccess` file in
  those directories.
* Deny access to temporary files, see :config:option:`$cfg['TempDir']` (if that
  is placed inside your web root, see also :ref:`web-dirs`.
* It is generally a good idea to protect a public phpMyAdmin installation
  against access by robots as they usually can not do anything good there. You
  can do this using ``robots.txt`` file in the root of your webserver or limit
  access by web server configuration, see :ref:`faq1_42`.
* In case you don't want all MySQL users to be able to access
  phpMyAdmin, you can use :config:option:`$cfg['Servers'][$i]['AllowDeny']['rules']` to limit them
  or :config:option:`$cfg['Servers'][$i]['AllowRoot']` to deny root user access.
* Enable :ref:`2fa` for your account.
* Consider hiding phpMyAdmin behind an authentication proxy, so that
  users need to authenticate prior to providing MySQL credentials
  to phpMyAdmin. You can achieve this by configuring your web server to request
  HTTP authentication. For example in Apache this can be done with:

  .. code-block:: apache

     AuthType Basic
     AuthName "Restricted Access"
     AuthUserFile /usr/share/phpmyadmin/passwd
     Require valid-user

  Once you have changed the configuration, you need to create a list of users which
  can authenticate. This can be done using the :program:`htpasswd` utility:

  .. code-block:: sh

     htpasswd -c /usr/share/phpmyadmin/passwd username

* If you are afraid of automated attacks, enabling Captcha by
  :config:option:`$cfg['CaptchaLoginPublicKey']` and
  :config:option:`$cfg['CaptchaLoginPrivateKey']` might be an option.
* Failed login attempts are logged to syslog (if available, see
  :config:option:`$cfg['AuthLog']`). This can allow using a tool such as
  fail2ban to block brute-force attempts. Note that the log file used by syslog
  is not the same as the Apache error or access log files.
* In case you're running phpMyAdmin together with other PHP applications, it is
  generally advised to use separate session storage for phpMyAdmin to avoid
  possible session-based attacks against it. You can use
  :config:option:`$cfg['SessionSavePath']` to achieve this.

.. _ssl:

Using SSL for connection to database server
+++++++++++++++++++++++++++++++++++++++++++

It is recommended to use SSL when connecting to remote database server. There
are several configuration options involved in the SSL setup:

:config:option:`$cfg['Servers'][$i]['ssl']`
    Defines whether to use SSL at all. If you enable only this, the connection
    will be encrypted, but there is not authentication of the connection - you
    can not verify that you are talking to the right server.
:config:option:`$cfg['Servers'][$i]['ssl_key']` and :config:option:`$cfg['Servers'][$i]['ssl_cert']`
    This is used for authentication of client to the server.
:config:option:`$cfg['Servers'][$i]['ssl_ca']` and :config:option:`$cfg['Servers'][$i]['ssl_ca_path']`
    The certificate authorities you trust for server certificates.
    This is used to ensure that you are talking to a trusted server.
:config:option:`$cfg['Servers'][$i]['ssl_verify']`
    This configuration disables server certificate verification. Use with
    caution.

When the database server is using a local connection or private network and SSL can not be configured
you can use :config:option:`$cfg['MysqlSslWarningSafeHosts']` to explicitly list the hostnames that are considered secure.

.. seealso::

    :ref:`example-google-ssl`,
    :ref:`example-aws-ssl`,
    :config:option:`$cfg['Servers'][$i]['ssl']`,
    :config:option:`$cfg['Servers'][$i]['ssl_key']`,
    :config:option:`$cfg['Servers'][$i]['ssl_cert']`,
    :config:option:`$cfg['Servers'][$i]['ssl_ca']`,
    :config:option:`$cfg['Servers'][$i]['ssl_ca_path']`,
    :config:option:`$cfg['Servers'][$i]['ssl_ciphers']`,
    :config:option:`$cfg['Servers'][$i]['ssl_verify']`

Known issues
++++++++++++

Users with column-specific privileges are unable to "Browse"
------------------------------------------------------------

If a user has only column-specific privileges on some (but not all) columns in a table, "Browse"
will fail with an error message.

As a workaround, a bookmarked query with the same name as the table can be created, this will
run when using the "Browse" link instead. `Issue 11922 <https://github.com/phpmyadmin/phpmyadmin/issues/11922>`_.

Trouble logging back in after logging out using 'http' authentication
----------------------------------------------------------------------

When using the 'http' ``auth_type``, it can be impossible to log back in (when the logout comes
manually or after a period of inactivity). `Issue 11898 <https://github.com/phpmyadmin/phpmyadmin/issues/11898>`_.

.. _Composer tool: https://getcomposer.org/
.. _Packagist: https://packagist.org/
.. _Docker official image: https://hub.docker.com/_/phpmyadmin

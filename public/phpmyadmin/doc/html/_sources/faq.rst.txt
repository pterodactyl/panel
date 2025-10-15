.. _faq:

FAQ - Frequently Asked Questions
================================

Please have a look at our `Link section
<https://www.phpmyadmin.net/docs/>`_ on the official
phpMyAdmin homepage for in-depth coverage of phpMyAdmin's features and
or interface.

.. _faqserver:

Server
++++++

.. _faq1_1:

1.1 My server is crashing each time a specific action is required or phpMyAdmin sends a blank page or a page full of cryptic characters to my browser, what can I do?
---------------------------------------------------------------------------------------------------------------------------------------------------------------------

Try to set the :config:option:`$cfg['OBGzip']` directive to ``false`` in your
:file:`config.inc.php` file and the ``zlib.output_compression`` directive to
``Off`` in your php configuration file.

.. _faq1_2:

1.2 My Apache server crashes when using phpMyAdmin.
---------------------------------------------------

You should first try the latest versions of Apache (and possibly MySQL). If
your server keeps crashing, please ask for help in the various Apache support
groups.

.. seealso:: :ref:`faq1_1`

.. _faq1_3:

1.3 (withdrawn).
----------------

.. _faq1_4:

1.4 Using phpMyAdmin on IIS, I'm displayed the error message: "The specified CGI application misbehaved by not returning a complete set of HTTP headers ...".
-------------------------------------------------------------------------------------------------------------------------------------------------------------

You just forgot to read the *install.txt* file from the PHP
distribution. Have a look at the last message in this `PHP bug report #12061
<https://bugs.php.net/bug.php?id=12061>`_ from the official PHP bug
database.

.. _faq1_5:

1.5 Using phpMyAdmin on IIS, I'm facing crashes and/or many error messages with the HTTP.
-----------------------------------------------------------------------------------------

This is a known problem with the PHP :term:`ISAPI` filter: it's not so stable.
Please use instead the cookie authentication mode.

.. _faq1_6:

1.6 I can't use phpMyAdmin on PWS: nothing is displayed!
--------------------------------------------------------

This seems to be a PWS bug. Filippo Simoncini found a workaround (at
this time there is no better fix): remove or comment the ``DOCTYPE``
declarations (2 lines) from the scripts :file:`libraries/classes/Header.php`
and :file:`index.php`.

.. _faq1_7:

1.7 How can I gzip a dump or a CSV export? It does not seem to work.
--------------------------------------------------------------------

This feature is based on the ``gzencode()``
PHP function to be more independent of the platform (Unix/Windows,
Safe Mode or not, and so on). So, you must have Zlib support
(``--with-zlib``).

.. _faq1_8:

1.8 I cannot insert a text file in a table, and I get an error about safe mode being in effect.
-----------------------------------------------------------------------------------------------

Your uploaded file is saved by PHP in the "upload dir", as defined in
:file:`php.ini` by the variable ``upload_tmp_dir`` (usually the system
default is */tmp*). We recommend the following setup for Apache
servers running in safe mode, to enable uploads of files while being
reasonably secure:

* create a separate directory for uploads: :command:`mkdir /tmp/php`
* give ownership to the Apache server's user.group: :command:`chown
  apache.apache /tmp/php`
* give proper permission: :command:`chmod 600 /tmp/php`
* put ``upload_tmp_dir = /tmp/php`` in :file:`php.ini`
* restart Apache

.. _faq1_9:

1.9 (withdrawn).
----------------

.. _faq1_10:

1.10 I'm having troubles when uploading files with phpMyAdmin running on a secure server. My browser is Internet Explorer and I'm using the Apache server.
----------------------------------------------------------------------------------------------------------------------------------------------------------

As suggested by "Rob M" in the phpWizard forum, add this line to your
*httpd.conf*:

.. code-block:: apache

    SetEnvIf User-Agent ".*MSIE.*" nokeepalive ssl-unclean-shutdown

It seems to clear up many problems between Internet Explorer and SSL.

.. _faq1_11:

1.11 I get an 'open\_basedir restriction' while uploading a file from the import tab.
-------------------------------------------------------------------------------------

Since version 2.2.4, phpMyAdmin supports servers with open\_basedir
restrictions. However you need to create temporary directory and configure it
as :config:option:`$cfg['TempDir']`. The uploaded files will be moved there,
and after execution of your :term:`SQL` commands, removed.

.. _faq1_12:

1.12 I have lost my MySQL root password, what can I do?
-------------------------------------------------------

phpMyAdmin does authenticate against MySQL server you're using, so to recover
from phpMyAdmin password loss, you need to recover at MySQL level.

The MySQL manual explains how to `reset the permissions
<https://dev.mysql.com/doc/refman/5.7/en/resetting-permissions.html>`_.

If you are using MySQL server installed by your hosting provider, please
contact their support to recover the password for you.

.. _faq1_13:

1.13 (withdrawn).
-----------------

.. _faq1_14:

1.14 (withdrawn).
-----------------

.. _faq1_15:

1.15 I have problems with *mysql.user* column names.
----------------------------------------------------

In previous MySQL versions, the ``User`` and ``Password`` columns were
named ``user`` and ``password``. Please modify your column names to
align with current standards.

.. _faq1_16:

1.16 I cannot upload big dump files (memory, HTTP or timeout problems).
-----------------------------------------------------------------------

Starting with version 2.7.0, the import engine has been re–written and
these problems should not occur. If possible, upgrade your phpMyAdmin
to the latest version to take advantage of the new import features.

The first things to check (or ask your host provider to check) are the values
of ``max_execution_time``, ``upload_max_filesize``, ``memory_limit`` and
``post_max_size`` in the :file:`php.ini` configuration file. All of these
settings limit the maximum size of data that can be submitted and handled by
PHP. Please note that ``post_max_size`` needs to be larger than
``upload_max_filesize``. There exist several workarounds if your upload is too
big or your hosting provider is unwilling to change the settings:

* Look at the :config:option:`$cfg['UploadDir']` feature. This allows one to upload a file to the server
  via scp, FTP, or your favorite file transfer method. PhpMyAdmin is
  then able to import the files from the temporary directory. More
  information is available in the :ref:`config`  of this document.
* Using a utility (such as `BigDump
  <https://www.ozerov.de/bigdump/>`_) to split the files before
  uploading. We cannot support this or any third party applications, but
  are aware of users having success with it.
* If you have shell (command line) access, use MySQL to import the files
  directly. You can do this by issuing the "source" command from within
  MySQL:

  .. code-block:: mysql

    source filename.sql;

.. _faq1_17:

1.17 Which Database versions does phpMyAdmin support?
-----------------------------------------------------

For `MySQL <https://www.mysql.com/>`_, versions 5.5 and newer are supported.
For older MySQL versions, our `Downloads <https://www.phpmyadmin.net/downloads/>`_ page offers older phpMyAdmin versions
(which may have become unsupported).

For `MariaDB <https://mariadb.org/>`_, versions 5.5 and newer are supported.

.. _faq1_17a:

1.17a I cannot connect to the MySQL server. It always returns the error message, "Client does not support authentication protocol requested by server; consider upgrading MySQL client"
---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------

You tried to access MySQL with an old MySQL client library. The
version of your MySQL client library can be checked in your phpinfo()
output. In general, it should have at least the same minor version as
your server - as mentioned in :ref:`faq1_17`. This problem is
generally caused by using MySQL version 4.1 or newer. MySQL changed
the authentication hash and your PHP is trying to use the old method.
The proper solution is to use the `mysqli extension
<https://www.php.net/mysqli>`_ with the proper client library to match
your MySQL installation. More
information (and several workarounds) are located in the `MySQL
Documentation <https://dev.mysql.com/doc/refman/5.7/en/common-errors.html>`_.

.. _faq1_18:

1.18 (withdrawn).
-----------------

.. _faq1_19:

1.19 I can't run the "display relations" feature because the script seems not to know the font face I'm using!
--------------------------------------------------------------------------------------------------------------

The :term:`TCPDF` library we're using for this feature requires some special
files to use font faces. Please refers to the `TCPDF manual
<https://tcpdf.org/>`_ to build these files.

.. _faqmysql:

1.20 I receive an error about missing mysqli and mysql extensions.
------------------------------------------------------------------

To connect to a MySQL server, PHP needs a set of MySQL functions
called "MySQL extension". This extension may be part of the PHP
distribution (compiled-in), otherwise it needs to be loaded
dynamically. Its name is probably *mysqli.so* or *php\_mysqli.dll*.
phpMyAdmin tried to load the extension but failed. Usually, the
problem is solved by installing a software package called "PHP-MySQL"
or something similar.

There was two interfaces PHP provided as MySQL extensions - ``mysql``
and ``mysqli``. The ``mysql`` interface was removed in PHP 7.0.

This problem can be also caused by wrong paths in the :file:`php.ini` or using
wrong :file:`php.ini`.

Make sure that the extension files do exist in the folder which the
``extension_dir`` points to and that the corresponding lines in your
:file:`php.ini` are not commented out (you can use ``phpinfo()`` to check
current setup):

.. code-block:: ini

    [PHP]

    ; Directory in which the loadable extensions (modules) reside.
    extension_dir = "C:/Apache2/modules/php/ext"

The :file:`php.ini` can be loaded from several locations (especially on
Windows), so please check you're updating the correct one. If using Apache, you
can tell it to use specific path for this file using ``PHPIniDir`` directive:

.. code-block:: apache

    LoadModule php7_module "C:/php7/php7apache2_4.dll"
    <IfModule php7_module>
        PHPIniDir "C:/php7"
        <Location>
           AddType text/html .php
           AddHandler application/x-httpd-php .php
        </Location>
    </IfModule>

In some rare cases this problem can be also caused by other extensions loaded
in PHP which prevent MySQL extensions to be loaded. If anything else fails, you
can try commenting out extensions for other databases from :file:`php.ini`.

.. _faq1_21:

1.21 I am running the CGI version of PHP under Unix, and I cannot log in using cookie auth.
-------------------------------------------------------------------------------------------

In :file:`php.ini`, set ``mysql.max_links`` higher than 1.

.. _faq1_22:

1.22 I don't see the "Location of text file" field, so I cannot upload.
-----------------------------------------------------------------------

This is most likely because in :file:`php.ini`, your ``file_uploads``
parameter is not set to "on".

.. _faq1_23:

1.23 I'm running MySQL on a Win32 machine. Each time I create a new table the table and column names are changed to lowercase!
------------------------------------------------------------------------------------------------------------------------------

This happens because the MySQL directive ``lower_case_table_names``
defaults to 1 (``ON``) in the Win32 version of MySQL. You can change
this behavior by simply changing the directive to 0 (``OFF``): Just
edit your ``my.ini`` file that should be located in your Windows
directory and add the following line to the group [mysqld]:

.. code-block:: ini

    set-variable = lower_case_table_names=0

.. note::

    Forcing this variable to 0 with --lower-case-table-names=0 on a
    case-insensitive filesystem and access MyISAM tablenames using different
    lettercases, index corruption may result.

Next, save the file and restart the MySQL service. You can always
check the value of this directive using the query

.. code-block:: mysql

    SHOW VARIABLES LIKE 'lower_case_table_names';

.. seealso:: `Identifier Case Sensitivity in the MySQL Reference Manual <https://dev.mysql.com/doc/refman/5.7/en/identifier-case-sensitivity.html>`_

.. _faq1_24:

1.24 (withdrawn).
-----------------

.. _faq1_25:

1.25 I am running Apache with mod\_gzip-1.3.26.1a on Windows XP, and I get problems, such as undefined variables when I run a SQL query.
----------------------------------------------------------------------------------------------------------------------------------------

A tip from Jose Fandos: put a comment on the following two lines in
httpd.conf, like this:

.. code-block:: apache

    # mod_gzip_item_include file \.php$
    # mod_gzip_item_include mime "application/x-httpd-php.*"

as this version of mod\_gzip on Apache (Windows) has problems handling
PHP scripts. Of course you have to restart Apache.

.. _faq1_26:

1.26 I just installed phpMyAdmin in my document root of IIS but I get the error "No input file specified" when trying to run phpMyAdmin.
----------------------------------------------------------------------------------------------------------------------------------------

This is a permission problem. Right-click on the phpmyadmin folder and
choose properties. Under the tab Security, click on "Add" and select
the user "IUSR\_machine" from the list. Now set their permissions and it
should work.

.. _faq1_27:

1.27 I get empty page when I want to view huge page (eg. db\_structure.php with plenty of tables).
--------------------------------------------------------------------------------------------------

This was caused by a `PHP bug <https://bugs.php.net/bug.php?id=21079>`_ that occur when
GZIP output buffering is enabled. If you turn off it (by
:config:option:`$cfg['OBGzip']` in :file:`config.inc.php`), it should work.
This bug will has been fixed in PHP 5.0.0.

.. _faq1_28:

1.28 My MySQL server sometimes refuses queries and returns the message 'Errorcode: 13'. What does this mean?
------------------------------------------------------------------------------------------------------------

This can happen due to a MySQL bug when having database / table names
with upper case characters although ``lower_case_table_names`` is
set to 1. To fix this, turn off this directive, convert all database
and table names to lower case and turn it on again. Alternatively,
there's a bug-fix available starting with MySQL 3.23.56 /
4.0.11-gamma.

.. _faq1_29:

1.29 When I create a table or modify a column, I get an error and the columns are duplicated.
---------------------------------------------------------------------------------------------

It is possible to configure Apache in such a way that PHP has problems
interpreting .php files.

The problems occur when two different (and conflicting) set of
directives are used:

.. code-block:: apache

    SetOutputFilter PHP
    SetInputFilter PHP

and

.. code-block:: apache

    AddType application/x-httpd-php .php

In the case we saw, one set of directives was in
``/etc/httpd/conf/httpd.conf``, while the other set was in
``/etc/httpd/conf/addon-modules/php.conf``. The recommended way is
with ``AddType``, so just comment out the first set of lines and
restart Apache:

.. code-block:: apache

    #SetOutputFilter PHP
    #SetInputFilter PHP

.. _faq1_30:

1.30 I get the error "navigation.php: Missing hash".
----------------------------------------------------

This problem is known to happen when the server is running Turck
MMCache but upgrading MMCache to version 2.3.21 solves the problem.

.. _faq1_31:

1.31 Which PHP versions does phpMyAdmin support?
------------------------------------------------

Since release 4.5, phpMyAdmin supports only PHP 5.5 and newer. Since release
4.1 phpMyAdmin supports only PHP 5.3 and newer. For PHP 5.2 you can use 4.0.x
releases.

PHP 7 is supported since phpMyAdmin 4.6, PHP 7.1 is supported since 4.6.5,
PHP 7.2 is supported since 4.7.4.

HHVM is supported up to phpMyAdmin 4.8.

Since release 5.0, phpMyAdmin supports only PHP 7.1 and newer.
Since release 5.2, phpMyAdmin supports only PHP 7.2 and newer.

.. _faq1_32:

1.32 Can I use HTTP authentication with IIS?
--------------------------------------------

Yes. This procedure was tested with phpMyAdmin 2.6.1, PHP 4.3.9 in
:term:`ISAPI` mode under :term:`IIS` 5.1.

#. In your :file:`php.ini` file, set ``cgi.rfc2616_headers = 0``
#. In ``Web Site Properties -> File/Directory Security -> Anonymous
   Access`` dialog box, check the ``Anonymous access`` checkbox and
   uncheck any other checkboxes (i.e. uncheck ``Basic authentication``,
   ``Integrated Windows authentication``, and ``Digest`` if it's
   enabled.) Click ``OK``.
#. In ``Custom Errors``, select the range of ``401;1`` through ``401;5``
   and click the ``Set to Default`` button.

.. seealso:: :rfc:`2616`

.. _faq1_33:

1.33 (withdrawn).
-----------------

.. _faq1_34:

1.34 Can I directly access a database or table pages?
-----------------------------------------------------

Yes. Out of the box, you can use a :term:`URL` like
``http://server/phpMyAdmin/index.php?server=X&db=database&table=table&target=script``.
For ``server`` you can use the server number
which refers to the numeric host index (from ``$i``) in
:file:`config.inc.php`. The table and script parts are optional.

If you want a URL like
``http://server/phpMyAdmin/database[/table][/script]``, you need to do some additional configuration. The following
lines apply only for the `Apache <https://httpd.apache.org>`_ web server.
First, make sure that you have enabled some features within the Apache global
configuration. You need ``Options SymLinksIfOwnerMatch`` and ``AllowOverride
FileInfo`` enabled for directory where phpMyAdmin is installed and you
need mod\_rewrite to be enabled. Then you just need to create the
following :term:`.htaccess` file in root folder of phpMyAdmin installation (don't
forget to change directory name inside of it):

.. code-block:: apache

    RewriteEngine On
    RewriteBase /path_to_phpMyAdmin
    RewriteRule ^([a-zA-Z0-9_]+)/([a-zA-Z0-9_]+)/([a-z_]+\.php)$ index.php?db=$1&table=$2&target=$3 [R]
    RewriteRule ^([a-zA-Z0-9_]+)/([a-z_]+\.php)$ index.php?db=$1&target=$2 [R]
    RewriteRule ^([a-zA-Z0-9_]+)/([a-zA-Z0-9_]+)$ index.php?db=$1&table=$2 [R]
    RewriteRule ^([a-zA-Z0-9_]+)$ index.php?db=$1 [R]

.. seealso:: :ref:`faq4_8`

.. versionchanged:: 5.1.0

    Support for using the ``target`` parameter was removed in phpMyAdmin 5.1.0.
    Use the ``route`` parameter instead.

.. _faq1_35:

1.35 Can I use HTTP authentication with Apache CGI?
---------------------------------------------------

Yes. However you need to pass authentication variable to :term:`CGI` using
following rewrite rule:

.. code-block:: apache

    RewriteEngine On
    RewriteRule .* - [E=REMOTE_USER:%{HTTP:Authorization},L]

.. _faq1_36:

1.36 I get an error "500 Internal Server Error".
------------------------------------------------

There can be many explanations to this and a look at your server's
error log file might give a clue.

.. _faq1_37:

1.37 I run phpMyAdmin on cluster of different machines and password encryption in cookie auth doesn't work.
-----------------------------------------------------------------------------------------------------------

If your cluster consist of different architectures, PHP code used for
encryption/decryption won't work correctly. This is caused by use of
pack/unpack functions in code. Only solution is to use openssl
extension which works fine in this case.

.. _faq1_38:

1.38 Can I use phpMyAdmin on a server on which Suhosin is enabled?
------------------------------------------------------------------

Yes but the default configuration values of Suhosin are known to cause
problems with some operations, for example editing a table with many
columns and no :term:`primary key` or with textual :term:`primary key`.

Suhosin configuration might lead to malfunction in some cases and it
can not be fully avoided as phpMyAdmin is kind of application which
needs to transfer big amounts of columns in single HTTP request, what
is something what Suhosin tries to prevent. Generally all
``suhosin.request.*``, ``suhosin.post.*`` and ``suhosin.get.*``
directives can have negative effect on phpMyAdmin usability. You can
always find in your error logs which limit did cause dropping of
variable, so you can diagnose the problem and adjust matching
configuration variable.

The default values for most Suhosin configuration options will work in
most scenarios, however you might want to adjust at least following
parameters:

* `suhosin.request.max\_vars <https://suhosin5.suhosin.org/stories/configuration.html#suhosin-request-max-vars>`_ should
  be increased (eg. 2048)
* `suhosin.post.max\_vars <https://suhosin5.suhosin.org/stories/configuration.html#suhosin-post-max-vars>`_ should be
  increased (eg. 2048)
* `suhosin.request.max\_array\_index\_length <https://suhosin5.suhosin.org/stories/configuration.html#suhosin-request-max-array-index-length>`_
  should be increased (eg. 256)
* `suhosin.post.max\_array\_index\_length <https://suhosin5.suhosin.org/stories/configuration.html#suhosin-post-max-array-index-length>`_
  should be increased (eg. 256)
* `suhosin.request.max\_totalname\_length <https://suhosin5.suhosin.org/stories/configuration.html#suhosin-request-max-totalname-length>`_
  should be increased (eg. 8192)
* `suhosin.post.max\_totalname\_length <https://suhosin5.suhosin.org/stories/configuration.html#suhosin-post-max-totalname-length>`_ should be
  increased (eg. 8192)
* `suhosin.get.max\_value\_length <https://suhosin5.suhosin.org/stories/configuration.html#suhosin-get-max-value-length>`_
  should be increased (eg. 1024)
* `suhosin.sql.bailout\_on\_error <https://suhosin5.suhosin.org/stories/configuration.html#suhosin-sql-bailout-on-error>`_
  needs to be disabled (the default)
* `suhosin.log.\* <https://suhosin5.suhosin.org/stories/configuration.html#logging-configuration>`_ should not
  include :term:`SQL`, otherwise you get big
  slowdown
* `suhosin.sql.union <https://suhosin5.suhosin.org/stories/configuration.html#suhosin-
  sql-union>`_ must be disabled (which is the default).
* `suhosin.sql.multiselect <https://suhosin5.suhosin.org/stories/configuration.html#
  suhosin-sql-multiselect>`_ must be disabled (which is the default).
* `suhosin.sql.comment <https://suhosin5.suhosin.org/stories/configuration.html#suhosin-
  sql-comment>`_ must be disabled (which is the default).

To further improve security, we also recommend these modifications:

* `suhosin.executor.include.max\_traversal <https://suhosin5.suhosin.org/stories/
  configuration.html#suhosin-executor-include-max-traversal>`_ should be
  enabled as a mitigation against local file inclusion attacks. We suggest
  setting this to 2 as ``../`` is used with the ReCaptcha library.
* `suhosin.cookie.encrypt <https://suhosin5.suhosin.org/stories/configuration.html#
  suhosin-cookie-encrypt>`_ should be enabled.
* `suhosin.executor.disable_emodifier <https://suhosin5.suhosin.org/stories/config
  uration.html#suhosin-executor-disable-emodifier>`_ should be enabled.

You can also disable the warning using the :config:option:`$cfg['SuhosinDisableWarning']`.

.. _faq1_39:

1.39 When I try to connect via https, I can log in, but then my connection is redirected back to http. What can cause this behavior?
------------------------------------------------------------------------------------------------------------------------------------

This is caused by the fact that PHP scripts have no knowledge that the site is
using https. Depending on used webserver, you should configure it to let PHP
know about URL and scheme used to access it.

For example in Apache ensure that you have enabled ``SSLOptions`` and
``StdEnvVars`` in the configuration.

.. seealso:: <https://httpd.apache.org/docs/2.4/mod/mod_ssl.html>

.. _faq1_40:

1.40 When accessing phpMyAdmin via an Apache reverse proxy, cookie login does not work.
---------------------------------------------------------------------------------------

To be able to use cookie auth Apache must know that it has to rewrite
the set-cookie headers. Example from the Apache 2.2 documentation:

.. code-block:: apache

    ProxyPass /mirror/foo/ http://backend.example.com/
    ProxyPassReverse /mirror/foo/ http://backend.example.com/
    ProxyPassReverseCookieDomain backend.example.com public.example.com
    ProxyPassReverseCookiePath / /mirror/foo/

Note: if the backend url looks like ``http://server/~user/phpmyadmin``, the
tilde (~) must be url encoded as %7E in the ProxyPassReverse\* lines.
This is not specific to phpmyadmin, it's just the behavior of Apache.

.. code-block:: apache

    ProxyPass /mirror/foo/ http://backend.example.com/~user/phpmyadmin
    ProxyPassReverse /mirror/foo/ http://backend.example.com/%7Euser/phpmyadmin
    ProxyPassReverseCookiePath /%7Euser/phpmyadmin /mirror/foo

.. seealso:: <https://httpd.apache.org/docs/2.2/mod/mod_proxy.html>, :config:option:`$cfg['PmaAbsoluteUri']`

.. _faq1_41:

1.41 When I view a database and ask to see its privileges, I get an error about an unknown column.
--------------------------------------------------------------------------------------------------

The MySQL server's privilege tables are not up to date, you need to
run the :command:`mysql_upgrade` command on the server.

.. _faq1_42:

1.42 How can I prevent robots from accessing phpMyAdmin?
--------------------------------------------------------

You can add various rules to :term:`.htaccess` to filter access based on user agent
field. This is quite easy to circumvent, but could prevent at least
some robots accessing your installation.

.. code-block:: apache

    RewriteEngine on

    # Allow only GET and POST verbs
    RewriteCond %{REQUEST_METHOD} !^(GET|POST)$ [NC,OR]

    # Ban Typical Vulnerability Scanners and others
    # Kick out Script Kiddies
    RewriteCond %{HTTP_USER_AGENT} ^(java|curl|wget).* [NC,OR]
    RewriteCond %{HTTP_USER_AGENT} ^.*(libwww-perl|curl|wget|python|nikto|wkito|pikto|scan|acunetix).* [NC,OR]
    RewriteCond %{HTTP_USER_AGENT} ^.*(winhttp|HTTrack|clshttp|archiver|loader|email|harvest|extract|grab|miner).* [NC,OR]

    # Ban Search Engines, Crawlers to your administrative panel
    # No reasons to access from bots
    # Ultimately Better than the useless robots.txt
    # Did google respect robots.txt?
    # Try google: intitle:phpMyAdmin intext:"Welcome to phpMyAdmin *.*.*" intext:"Log in" -wiki -forum -forums -questions intext:"Cookies must be enabled"
    RewriteCond %{HTTP_USER_AGENT} ^.*(AdsBot-Google|ia_archiver|Scooter|Ask.Jeeves|Baiduspider|Exabot|FAST.Enterprise.Crawler|FAST-WebCrawler|www\.neomo\.de|Gigabot|Mediapartners-Google|Google.Desktop|Feedfetcher-Google|Googlebot|heise-IT-Markt-Crawler|heritrix|ibm.com\cs/crawler|ICCrawler|ichiro|MJ12bot|MetagerBot|msnbot-NewsBlogs|msnbot|msnbot-media|NG-Search|lucene.apache.org|NutchCVS|OmniExplorer_Bot|online.link.validator|psbot0|Seekbot|Sensis.Web.Crawler|SEO.search.Crawler|Seoma.\[SEO.Crawler\]|SEOsearch|Snappy|www.urltrends.com|www.tkl.iis.u-tokyo.ac.jp/~crawler|SynooBot|crawleradmin.t-info@telekom.de|TurnitinBot|voyager|W3.SiteSearch.Crawler|W3C-checklink|W3C_Validator|www.WISEnutbot.com|yacybot|Yahoo-MMCrawler|Yahoo\!.DE.Slurp|Yahoo\!.Slurp|YahooSeeker).* [NC]
    RewriteRule .* - [F]

.. _faq1_43:

1.43 Why can't I display the structure of my table containing hundreds of columns?
----------------------------------------------------------------------------------

Because your PHP's ``memory_limit`` is too low; adjust it in :file:`php.ini`.

.. _faq1:44:

1.44 How can I reduce the installed size of phpMyAdmin on disk?
---------------------------------------------------------------

Some users have requested to be able to reduce the size of the phpMyAdmin installation.
This is not recommended and could lead to confusion over missing features, but can be done.
A list of files and corresponding functionality which degrade gracefully when removed include:

* :file:`./locale/` folder, or unused subfolders (interface translations)
* Any unused themes in :file:`./themes/` except the default theme `pmahomme`.
* :file:`./libraries/language_stats.inc.php` (translation statistics)
* :file:`./doc/` (documentation)
* :file:`./setup/` (setup script)
* :file:`./examples/` (configuration examples)
* :file:`./sql/` (SQL scripts to configure advanced functionalities)
* :file:`./js/src/` (Source files to re-build `./js/dist/`)
* :file:`./js/config/` (Configuration files to re-build `./js/dist/`)
* Run `rm -rv vendor/tecnickcom/tcpdf && composer dump-autoload --no-interaction --optimize --dev` (exporting to PDF)
* Run `rm -rv vendor/williamdes/mariadb-mysql-kbs && composer dump-autoload --no-interaction --optimize --dev` (external links to MariaDB and MySQL documentations)
* Run `rm -rv vendor/code-lts/u2f-php-server && composer dump-autoload --no-interaction --optimize --dev` (U2F second factor authentication)
* Run `rm -rv vendor/pragmarx/* && composer dump-autoload --no-interaction --optimize --dev` (2FA second factor authentication)
* Run `rm -rv vendor/bacon/bacon-qr-code && composer dump-autoload --no-interaction --optimize --dev` (QRcode generation for 2FA second factor authentication)

.. _faq1_45:

1.45 I get an error message about unknown authentication method caching_sha2_password when trying to log in
-----------------------------------------------------------------------------------------------------------

When logging in using MySQL version 8 or newer, you may encounter an error message like this:

    mysqli_real_connect(): The server requested authentication method unknown to the client [caching_sha2_password]

    mysqli_real_connect(): (HY000/2054): The server requested authentication method unknown to the client

This error is because of a version compatibility problem between PHP and MySQL. The MySQL project introduced a new authentication
method (our tests show this began with version 8.0.11) however PHP did not include the ability to use that authentication method.
PHP reports that this was fixed in PHP version 7.4.

Users experiencing this are encouraged to upgrade their PHP installation, however a workaround exists. Your MySQL user account
can be set to use the older authentication with a command such as

.. code-block:: mysql

  ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY 'PASSWORD';

.. seealso:: <https://github.com/phpmyadmin/phpmyadmin/issues/14220>, <https://stackoverflow.com/questions/49948350/phpmyadmin-on-mysql-8-0>, <https://bugs.php.net/bug.php?id=76243>

.. _faq1_46:

1.46 Missing ``HTTP_HOST`` variable when using nginx with HTTP/3
-----------------------------------------------------------------------------------------------------------

When using phpMyAdmin with nginx and HTTP/3 enabled, the ``HTTP_HOST`` variable may be missing due to a known issue with nginx's HTTP/3 implementation, which can cause several issues:

- Two-factor authentication won't work
- Exported settings have no hostname in the filename (e.g., ``phpMyAdmin-config-.json``)
- The hostname is missing from the page title (empty ``@HTTP_HOST@``)

To fix this issue, add the following line to your nginx configuration:

.. code-block:: nginx

    fastcgi_param HTTP_HOST $host;

This line should be placed in the server block of your nginx configuration where the PHP-FPM settings are defined.

.. seealso:: <https://github.com/php/php-src/issues/13021>, <https://github.com/nginx/nginx/issues/455>, <https://trac.nginx.org/nginx/ticket/2281>, <https://mailman.nginx.org/pipermail/nginx-devel/2024-January/3CQEHII5QU2BTQ7L7DAVCBWK3OQS3GU6.html>

.. _faqconfig:

Configuration
+++++++++++++

.. _faq2_1:

2.1 The error message "Warning: Cannot add header information - headers already sent by ..." is displayed, what's the problem?
------------------------------------------------------------------------------------------------------------------------------

Edit your :file:`config.inc.php` file and ensure there is nothing (I.E. no
blank lines, no spaces, no characters...) neither before the ``<?php`` tag at
the beginning, neither after the ``?>`` tag at the end.

.. _faq2_2:

2.2 phpMyAdmin can't connect to MySQL. What's wrong?
----------------------------------------------------

Either there is an error with your PHP setup or your username/password
is wrong. Try to make a small script which uses mysql\_connect and see
if it works. If it doesn't, it may be you haven't even compiled MySQL
support into PHP.

.. _faq2_3:

2.3 The error message "Warning: MySQL Connection Failed: Can't connect to local MySQL server through socket '/tmp/mysql.sock' (111) ..." is displayed. What can I do?
---------------------------------------------------------------------------------------------------------------------------------------------------------------------

The error message can also be: :guilabel:`Error #2002 - The server is not
responding (or the local MySQL server's socket is not correctly configured)`.

First, you need to determine what socket is being used by MySQL. To do this,
connect to your server and go to the MySQL bin directory. In this directory
there should be a file named *mysqladmin*. Type ``./mysqladmin variables``, and
this should give you a bunch of info about your MySQL server, including the
socket (*/tmp/mysql.sock*, for example). You can also ask your ISP for the
connection info or, if you're hosting your own, connect from the 'mysql'
command-line client and type 'status' to get the connection type and socket or
port number.

Then, you need to tell PHP to use this socket. You can do this for all PHP in
the :file:`php.ini` or for phpMyAdmin only in the :file:`config.inc.php`. For
example: :config:option:`$cfg['Servers'][$i]['socket']`  Please also make sure
that the permissions of this file allow to be readable by your webserver.

On my RedHat-Box the socket of MySQL is */var/lib/mysql/mysql.sock*.
In your :file:`php.ini` you will find a line

.. code-block:: ini

    mysql.default_socket = /tmp/mysql.sock

change it to

.. code-block:: ini

    mysql.default_socket = /var/lib/mysql/mysql.sock

Then restart apache and it will work.

Have also a look at the `corresponding section of the MySQL
documentation <https://dev.mysql.com/doc/refman/5.7/en/can-not-connect-to-server.html>`_.

.. _faq2_4:

2.4 Nothing is displayed by my browser when I try to run phpMyAdmin, what can I do?
-----------------------------------------------------------------------------------

Try to set the :config:option:`$cfg['OBGzip']` directive to ``false`` in the phpMyAdmin configuration
file. It helps sometime. Also have a look at your PHP version number:
if it contains "b" or "alpha" it means you're running a testing
version of PHP. That's not a so good idea, please upgrade to a plain
revision.

.. _faq2_5:

2.5 Each time I want to insert or change a row or drop a database or a table, an error 404 (page not found) is displayed or, with HTTP or cookie authentication, I'm asked to log in again. What's wrong?
---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------

Check your webserver setup if it correctly fills in either PHP_SELF or REQUEST_URI variables.

If you are running phpMyAdmin behind reverse proxy, please set the
:config:option:`$cfg['PmaAbsoluteUri']` directive in the phpMyAdmin
configuration file to match your setup.

.. _faq2_6:

2.6 I get an "Access denied for user: 'root@localhost' (Using password: YES)"-error when trying to access a MySQL-Server on a host which is port-forwarded for my localhost.
----------------------------------------------------------------------------------------------------------------------------------------------------------------------------

When you are using a port on your localhost, which you redirect via
port-forwarding to another host, MySQL is not resolving the localhost
as expected. Erik Wasser explains: The solution is: if your host is
"localhost" MySQL (the command line tool :command:`mysql` as well) always
tries to use the socket connection for speeding up things. And that
doesn't work in this configuration with port forwarding. If you enter
"127.0.0.1" as hostname, everything is right and MySQL uses the
:term:`TCP` connection.

.. _faqthemes:

2.7 Using and creating themes
-----------------------------

See :ref:`themes`.

.. _faqmissingparameters:

2.8 I get "Missing parameters" errors, what can I do?
-----------------------------------------------------

Here are a few points to check:

* In :file:`config.inc.php`, try to leave the :config:option:`$cfg['PmaAbsoluteUri']` directive empty. See also
  :ref:`faq4_7`.
* Maybe you have a broken PHP installation or you need to upgrade your
  Zend Optimizer. See <https://bugs.php.net/bug.php?id=31134>.
* If you are using Hardened PHP with the ini directive
  ``varfilter.max_request_variables`` set to the default (200) or
  another low value, you could get this error if your table has a high
  number of columns. Adjust this setting accordingly. (Thanks to Klaus
  Dorninger for the hint).
* In the :file:`php.ini` directive ``arg_separator.input``, a value of ";"
  will cause this error. Replace it with "&;".
* If you are using `Suhosin <https://suhosin5.suhosin.org/stories/index.html>`_, you
  might want to increase `request limits <https://suhosin5.suhosin.org/stories/faq.html>`_.
* The directory specified in the :file:`php.ini` directive
  ``session.save_path`` does not exist or is read-only (this can be caused
  by `bug in the PHP installer <https://bugs.php.net/bug.php?id=39842>`_).

.. _faq2_9:

2.9 Seeing an upload progress bar
---------------------------------

To be able to see a progress bar during your uploads, your server must
have the `uploadprogress <https://pecl.php.net/package/uploadprogress>`_ extension, and
you must be running PHP 5.4.0 or higher. Moreover, the JSON extension
has to be enabled in your PHP.

If using PHP 5.4.0 or higher, you must set
``session.upload_progress.enabled`` to ``1`` in your :file:`php.ini`. However,
starting from phpMyAdmin version 4.0.4, session-based upload progress has
been temporarily deactivated due to its problematic behavior.

.. _faq2_10:

2.10 How to generate a string of random bytes
---------------------------------------------

One way to generate a string of random bytes suitable for cryptographic use is using the
`random_bytes <https://www.php.net/random_bytes>`_ :term:`PHP` function. Since this function returns a binary string,
the returned value should be converted to printable format before being able to copy it.

For example, the :config:option:`$cfg['blowfish_secret']` configuration directive requires a 32-bytes long string. The
following command can be used to generate a hexadecimal representation of this string.

.. code-block:: sh

    php -r 'echo bin2hex(random_bytes(32)) . PHP_EOL;'

The above example will output something similar to:

.. code-block:: sh

    f16ce59f45714194371b48fe362072dc3b019da7861558cd4ad29e4d6fb13851

And then this hexadecimal value can be used in the configuration file.

.. code-block:: php

    $cfg['blowfish_secret'] = sodium_hex2bin('f16ce59f45714194371b48fe362072dc3b019da7861558cd4ad29e4d6fb13851');

The `sodium_hex2bin <https://www.php.net/sodium_hex2bin>`_ is used here to convert the hexadecimal value back to the
binary format.

.. _faqlimitations:

Known limitations
+++++++++++++++++

.. _login_bug:

3.1 When using HTTP authentication, a user who logged out can not log in again in with the same nick.
-----------------------------------------------------------------------------------------------------

This is related to the authentication mechanism (protocol) used by
phpMyAdmin. To bypass this problem: just close all the opened browser
windows and then go back to phpMyAdmin. You should be able to log in
again.

.. _faq3_2:

3.2 When dumping a large table in compressed mode, I get a memory limit error or a time limit error.
----------------------------------------------------------------------------------------------------

Compressed dumps are built in memory and because of this are limited
to php's memory limit. For gzip/bzip2 exports this can be overcome
since 2.5.4 using :config:option:`$cfg['CompressOnFly']` (enabled by default).
zip exports can not be handled this way, so if you need zip files for larger
dump, you have to use another way.

.. _faq3_3:

3.3 With InnoDB tables, I lose foreign key relationships when I rename a table or a column.
-------------------------------------------------------------------------------------------

This is an InnoDB bug, see <https://bugs.mysql.com/bug.php?id=21704>.

.. _faq3_4:

3.4 I am unable to import dumps I created with the mysqldump tool bundled with the MySQL server distribution.
-------------------------------------------------------------------------------------------------------------

The problem is that older versions of ``mysqldump`` created invalid
comments like this:

.. code-block:: mysql

    -- MySQL dump 8.22
    --
    -- Host: localhost Database: database
    ---------------------------------------------------------
    -- Server version 3.23.54

The invalid part of the code is the horizontal line made of dashes
that appears once in every dump created with mysqldump. If you want to
run your dump you have to turn it into valid MySQL. This means, you
have to add a whitespace after the first two dashes of the line or add
a # before it:  ``-- -------------------------------------------------------`` or
``#---------------------------------------------------------``

.. _faq3_5:

3.5 When using nested folders, multiple hierarchies are displayed in a wrong manner.
------------------------------------------------------------------------------------

Please note that you should not use the separating string multiple
times without any characters between them, or at the beginning/end of
your table name. If you have to, think about using another
TableSeparator or disabling that feature.

.. seealso:: :config:option:`$cfg['NavigationTreeTableSeparator']`

.. _faq3_6:

3.6 (withdrawn).
-----------------

.. _faq3_7:

3.7 I have table with many (100+) columns and when I try to browse table I get series of errors like "Warning: unable to parse url". How can this be fixed?
-----------------------------------------------------------------------------------------------------------------------------------------------------------

Your table neither have a :term:`primary key` nor an :term:`unique key`, so we must
use a long expression to identify this row. This causes problems to
parse\_url function. The workaround is to create a :term:`primary key`
or :term:`unique key`.

.. _faq3_8:

3.8 I cannot use (clickable) HTML-forms in columns where I put a MIME-Transformation onto!
------------------------------------------------------------------------------------------

Due to a surrounding form-container (for multi-row delete checkboxes),
no nested forms can be put inside the table where phpMyAdmin displays
the results. You can, however, use any form inside of a table if keep
the parent form-container with the target to tbl\_row\_delete.php and
just put your own input-elements inside. If you use a custom submit
input field, the form will submit itself to the displaying page again,
where you can validate the $HTTP\_POST\_VARS in a transformation. For
a tutorial on how to effectively use transformations, see our `Link
section <https://www.phpmyadmin.net/docs/>`_ on the
official phpMyAdmin-homepage.

.. _faq3_9:

3.9 I get error messages when using "--sql\_mode=ANSI" for the MySQL server.
----------------------------------------------------------------------------

When MySQL is running in ANSI-compatibility mode, there are some major
differences in how :term:`SQL` is structured (see
<https://dev.mysql.com/doc/refman/5.7/en/sql-mode.html>). Most important of all, the
quote-character (") is interpreted as an identifier quote character and not as
a string quote character, which makes many internal phpMyAdmin operations into
invalid :term:`SQL` statements. There is no
workaround to this behaviour.  News to this item will be posted in `issue
#7383 <https://github.com/phpmyadmin/phpmyadmin/issues/7383>`_.

.. _faq3_10:

3.10 Homonyms and no primary key: When the results of a SELECT display more that one column with the same value (for example ``SELECT lastname from employees where firstname like 'A%'`` and two "Smith" values are displayed), if I click Edit I cannot be sure that I am editing the intended row.
-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------

Please make sure that your table has a :term:`primary key`, so that phpMyAdmin
can use it for the Edit and Delete links.

.. _faq3_11:

3.11 The number of rows for InnoDB tables is not correct.
---------------------------------------------------------

phpMyAdmin uses a quick method to get the row count, and this method only
returns an approximate count in the case of InnoDB tables. See
:config:option:`$cfg['MaxExactCount']` for a way to modify those results, but
this could have a serious impact on performance.
However, one can easily replace the approximate row count with exact count by
simply clicking on the approximate count. This can also be done for all tables
at once by clicking on the rows sum displayed at the bottom.

.. seealso:: :config:option:`$cfg['MaxExactCount']`

.. _faq3_12:

3.12 (withdrawn).
-----------------

.. _faq3_13:

3.13 I get an error when entering ``USE`` followed by a db name containing an hyphen.
-------------------------------------------------------------------------------------

The tests I have made with MySQL 5.1.49 shows that the API does not
accept this syntax for the USE command.

.. _faq3_14:

3.14 I am not able to browse a table when I don't have the right to SELECT one of the columns.
----------------------------------------------------------------------------------------------

This has been a known limitation of phpMyAdmin since the beginning and
it's not likely to be solved in the future.

.. _faq3_15:

3.15 (withdrawn).
-----------------

.. _faq3_16:

3.16 (withdrawn).
-----------------

.. _faq3_17:

3.17 (withdrawn).
-----------------

.. _faq3_18:

3.18 When I import a CSV file that contains multiple tables, they are lumped together into a single table.
----------------------------------------------------------------------------------------------------------

There is no reliable way to differentiate tables in :term:`CSV` format. For the
time being, you will have to break apart :term:`CSV` files containing multiple
tables.

.. _faq3_19:

3.19 When I import a file and have phpMyAdmin determine the appropriate data structure it only uses int, decimal, and varchar types.
------------------------------------------------------------------------------------------------------------------------------------

Currently, the import type-detection system can only assign these
MySQL types to columns. In future, more will likely be added but for
the time being you will have to edit the structure to your liking
post-import.  Also, you should note the fact that phpMyAdmin will use
the size of the largest item in any given column as the column size
for the appropriate type. If you know you will be adding larger items
to that column then you should manually adjust the column sizes
accordingly. This is done for the sake of efficiency.

.. _faq3_20:

3.20 After upgrading, some bookmarks are gone or their content cannot be shown.
-------------------------------------------------------------------------------

At some point, the character set used to store bookmark content has changed.
It's better to recreate your bookmark from the newer phpMyAdmin version.

.. _faq3_21:

3.21 I am unable to log in with a username containing unicode characters such as á.
-----------------------------------------------------------------------------------

This can happen if MySQL server is not configured to use utf-8 as default
charset. This is a limitation of how PHP and the MySQL server interact; there
is no way for PHP to set the charset before authenticating.

.. seealso::

    `phpMyAdmin issue 12232 <https://github.com/phpmyadmin/phpmyadmin/issues/12232>`_,
    `MySQL documentation note <https://www.php.net/manual/en/mysqli.real-connect.php#refsect1-mysqli.real-connect-notes>`_

.. _faqmultiuser:

ISPs, multi-user installations
++++++++++++++++++++++++++++++

.. _faq4_1:

4.1 I'm an ISP. Can I setup one central copy of phpMyAdmin or do I need to install it for each customer?
--------------------------------------------------------------------------------------------------------

Since version 2.0.3, you can setup a central copy of phpMyAdmin for all your
users. The development of this feature was kindly sponsored by NetCologne GmbH.
This requires a properly setup MySQL user management and phpMyAdmin
:term:`HTTP` or cookie authentication.

.. seealso:: :ref:`authentication_modes`

.. _faq4_2:

4.2 What's the preferred way of making phpMyAdmin secure against evil access?
-----------------------------------------------------------------------------

This depends on your system. If you're running a server which cannot be
accessed by other people, it's sufficient to use the directory protection
bundled with your webserver (with Apache you can use :term:`.htaccess` files,
for example). If other people have telnet access to your server, you should use
phpMyAdmin's :term:`HTTP` or cookie authentication features.

Suggestions:

* Your :file:`config.inc.php` file should be ``chmod 660``.
* All your phpMyAdmin files should be chown -R phpmy.apache, where phpmy
  is a user whose password is only known to you, and apache is the group
  under which Apache runs.
* Follow security recommendations for PHP and your webserver.

.. _faq4_3:

4.3 I get errors about not being able to include a file in */lang* or in */libraries*.
--------------------------------------------------------------------------------------

Check :file:`php.ini`, or ask your sysadmin to check it. The
``include_path`` must contain "." somewhere in it, and
``open_basedir``, if used, must contain "." and "./lang" to allow
normal operation of phpMyAdmin.

.. _faq4_4:

4.4 phpMyAdmin always gives "Access denied" when using HTTP authentication.
---------------------------------------------------------------------------

This could happen for several reasons:

* :config:option:`$cfg['Servers'][$i]['controluser']` and/or :config:option:`$cfg['Servers'][$i]['controlpass']`  are wrong.
* The username/password you specify in the login dialog are invalid.
* You have already setup a security mechanism for the phpMyAdmin-
  directory, eg. a :term:`.htaccess` file. This would interfere with phpMyAdmin's
  authentication, so remove it.

.. _faq4_5:

4.5 Is it possible to let users create their own databases?
-----------------------------------------------------------

Starting with 2.2.5, in the user management page, you can enter a
wildcard database name for a user (for example "joe%"), and put the
privileges you want. For example, adding ``SELECT, INSERT, UPDATE,
DELETE, CREATE, DROP, INDEX, ALTER`` would let a user create/manage
their database(s).

.. _faq4_6:

4.6 How can I use the Host-based authentication additions?
----------------------------------------------------------

If you have existing rules from an old :term:`.htaccess` file, you can take them and
add a username between the ``'deny'``/``'allow'`` and ``'from'``
strings. Using the username wildcard of ``'%'`` would be a major
benefit here if your installation is suited to using it. Then you can
just add those updated lines into the
:config:option:`$cfg['Servers'][$i]['AllowDeny']['rules']` array.

If you want a pre-made sample, you can try this fragment. It stops the
'root' user from logging in from any networks other than the private
network :term:`IP` blocks.

.. code-block:: php

    //block root from logging in except from the private networks
    $cfg['Servers'][$i]['AllowDeny']['order'] = 'deny,allow';
    $cfg['Servers'][$i]['AllowDeny']['rules'] = [
        'deny root from all',
        'allow root from localhost',
        'allow root from 10.0.0.0/8',
        'allow root from 192.168.0.0/16',
        'allow root from 172.16.0.0/12',
    ];

.. _faq4_7:

4.7 Authentication window is displayed more than once, why?
-----------------------------------------------------------

This happens if you are using a :term:`URL` to start phpMyAdmin which is
different than the one set in your :config:option:`$cfg['PmaAbsoluteUri']`. For
example, a missing "www", or entering with an :term:`IP` address while a domain
name is defined in the config file.

.. _faq4_8:

4.8 Which parameters can I use in the URL that starts phpMyAdmin?
-----------------------------------------------------------------

When starting phpMyAdmin, you can use the ``db``
and ``server`` parameters. This last one can contain
either the numeric host index (from ``$i`` of the configuration file)
or one of the host names present in the configuration file.

For example, to jump directly to a particular database, a URL can be constructed as
``https://example.com/phpmyadmin/?db=sakila``.

.. seealso:: :ref:`faq1_34`

.. versionchanged:: 4.9.0

    Support for using the ``pma_username`` and ``pma_password`` parameters was removed
    in phpMyAdmin 4.9.0 (see `PMASA-2019-4 <https://www.phpmyadmin.net/security/PMASA-2019-4/>`_).

.. _faqbrowsers:

Browsers or client OS
+++++++++++++++++++++

.. _faq5_1:

5.1 I get an out of memory error, and my controls are non-functional, when trying to create a table with more than 14 columns.
------------------------------------------------------------------------------------------------------------------------------

We could reproduce this problem only under Win98/98SE. Testing under
WinNT4 or Win2K, we could easily create more than 60 columns.  A
workaround is to create a smaller number of columns, then come back to
your table properties and add the other columns.

.. _faq5_2:

5.2 With Xitami 2.5b4, phpMyAdmin won't process form fields.
------------------------------------------------------------

This is not a phpMyAdmin problem but a Xitami known bug: you'll face
it with each script/website that use forms. Upgrade or downgrade your
Xitami server.

.. _faq5_3:

5.3 I have problems dumping tables with Konqueror (phpMyAdmin 2.2.2).
---------------------------------------------------------------------

With Konqueror 2.1.1: plain dumps, zip and gzip dumps work ok, except
that the proposed file name for the dump is always 'tbl\_dump.php'.
The bzip2 dumps don't seem to work. With Konqueror 2.2.1: plain dumps
work; zip dumps are placed into the user's temporary directory, so
they must be moved before closing Konqueror, or else they disappear.
gzip dumps give an error message. Testing needs to be done for
Konqueror 2.2.2.

.. _faq5_4:

5.4 I can't use the cookie authentication mode because Internet Explorer never stores the cookies.
--------------------------------------------------------------------------------------------------

MS Internet Explorer seems to be really buggy about cookies, at least
till version 6.

.. _faq5_5:

5.5 (withdrawn).
----------------------------------------------------------------------------

.. _faq5_6:

5.6 (withdrawn).
-----------------------------------------------------------------------------------------------------------------------------------------------------------------

.. _faq5_7:

5.7 I refresh (reload) my browser, and come back to the welcome page.
---------------------------------------------------------------------

Some browsers support right-clicking into the frame you want to
refresh, just do this in the right frame.

.. _faq5_8:

5.8 With Mozilla 0.9.7 I have problems sending a query modified in the query box.
---------------------------------------------------------------------------------

Looks like a Mozilla bug: 0.9.6 was OK. We will keep an eye on future
Mozilla versions.

.. _faq5_9:

5.9 With Mozilla 0.9.? to 1.0 and Netscape 7.0-PR1 I can't type a whitespace in the SQL-Query edit area: the page scrolls down.
-------------------------------------------------------------------------------------------------------------------------------

This is a Mozilla bug (see bug #26882 at `BugZilla
<https://bugzilla.mozilla.org/>`_).

.. _faq5_10:

5.10 (withdrawn).
-----------------------------------------------------------------------------------------

.. _faq5_11:

5.11 Extended-ASCII characters like German umlauts are displayed wrong.
-----------------------------------------------------------------------

Please ensure that you have set your browser's character set to the
one of the language file you have selected on phpMyAdmin's start page.
Alternatively, you can try the auto detection mode that is supported
by the recent versions of the most browsers.

.. _faq5_12:

5.12 Mac OS X Safari browser changes special characters to "?".
---------------------------------------------------------------

This issue has been reported by a :term:`macOS` user, who adds that Chimera,
Netscape and Mozilla do not have this problem.

.. _faq5_13:

5.13 (withdrawn)
----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------

.. _faq5_14:

5.14 (withdrawn)
------------------------------------------------------------------------------------------------------------------

.. _faq5_15:

5.15 (withdrawn)
-----------------------------------------

.. _faq5_16:

5.16 With Internet Explorer, I get "Access is denied" Javascript errors. Or I cannot make phpMyAdmin work under Windows.
------------------------------------------------------------------------------------------------------------------------

Please check the following points:

* Maybe you have defined your :config:option:`$cfg['PmaAbsoluteUri']` setting in
  :file:`config.inc.php` to an :term:`IP` address and you are starting phpMyAdmin
  with a :term:`URL` containing a domain name, or the reverse situation.
* Security settings in IE and/or Microsoft Security Center are too high,
  thus blocking scripts execution.
* The Windows Firewall is blocking Apache and MySQL. You must allow
  :term:`HTTP` ports (80 or 443) and MySQL
  port (usually 3306) in the "in" and "out" directions.

.. _faq5_17:

5.17 With Firefox, I cannot delete rows of data or drop a database.
-------------------------------------------------------------------

Many users have confirmed that the Tabbrowser Extensions plugin they
installed in their Firefox is causing the problem.

.. _faq5_18:

5.18 (withdrawn)
-----------------------------------------------------------------------------------------

.. _faq5_19:

5.19 I get JavaScript errors in my browser.
-------------------------------------------

Issues have been reported with some combinations of browser
extensions. To troubleshoot, disable all extensions then clear your
browser cache to see if the problem goes away.

.. _faq5_20:

5.20 I get errors about violating Content Security Policy.
----------------------------------------------------------

If you see errors like:

.. code-block:: text

    Refused to apply inline style because it violates the following Content Security Policy directive

This is usually caused by some software, which wrongly rewrites
:mailheader:`Content Security Policy` headers. Usually this is caused by
antivirus proxy or browser addons which are causing such errors.

If you see these errors, try disabling the HTTP proxy in antivirus or disable
the :mailheader:`Content Security Policy` rewriting in it. If that doesn't
help, try disabling browser extensions.

Alternatively it can be also server configuration issue (if the webserver is
configured to emit :mailheader:`Content Security Policy` headers, they can
override the ones from phpMyAdmin).

Programs known to cause these kind of errors:

* Kaspersky Internet Security

.. _faq5_21:

5.21 I get errors about potentially unsafe operation when browsing table or executing SQL query.
------------------------------------------------------------------------------------------------

If you see errors like:

.. code-block:: text

    A potentially unsafe operation has been detected in your request to this site.

This is usually caused by web application firewall doing requests filtering. It
tries to prevent SQL injection, however phpMyAdmin is tool designed to execute
SQL queries, thus it makes it unusable.

Please allow phpMyAdmin scripts from the web application firewall settings
or disable it completely for phpMyAdmin path.

Programs known to cause these kind of errors:

* Wordfence Web Application Firewall

.. _faqusing:

Using phpMyAdmin
++++++++++++++++

.. _faq6_1:

6.1 I can't insert new rows into a table / I can't create a table - MySQL brings up a SQL error.
------------------------------------------------------------------------------------------------

Examine the :term:`SQL` error with care.
Often the problem is caused by specifying a wrong column-type. Common
errors include:

* Using ``VARCHAR`` without a size argument
* Using ``TEXT`` or ``BLOB`` with a size argument

Also, look at the syntax chapter in the MySQL manual to confirm that
your syntax is correct.

.. _faq6_2:

6.2 When I create a table, I set an index for two columns and phpMyAdmin generates only one index with those two columns.
-------------------------------------------------------------------------------------------------------------------------

This is the way to create a multi-columns index. If you want two
indexes, create the first one when creating the table, save, then
display the table properties and click the Index link to create the
other index.

.. _faq6_3:

6.3 How can I insert a null value into my table?
------------------------------------------------

Since version 2.2.3, you have a checkbox for each column that can be
null. Before 2.2.3, you had to enter "null", without the quotes, as
the column's value. Since version 2.5.5, you have to use the checkbox
to get a real NULL value, so if you enter "NULL" this means you want a
literal NULL in the column, and not a NULL value (this works in PHP4).

.. _faq6_4:

6.4 How can I backup my database or table?
------------------------------------------

Click on a database or table name in the navigation panel, the properties will
be displayed. Then on the menu, click "Export", you can dump the structure, the
data, or both. This will generate standard :term:`SQL` statements that can be
used to recreate your database/table.  You will need to choose "Save as file",
so that phpMyAdmin can transmit the resulting dump to your station.  Depending
on your PHP configuration, you will see options to compress the dump. See also
the :config:option:`$cfg['ExecTimeLimit']` configuration variable. For
additional help on this subject, look for the word "dump" in this document.

.. _faq6_5:

6.5 How can I restore (upload) my database or table using a dump? How can I run a ".sql" file?
----------------------------------------------------------------------------------------------

Click on a database name in the navigation panel, the properties will
be displayed. Select "Import" from the list of tabs in the right–hand
frame (or ":term:`SQL`" if your phpMyAdmin
version is previous to 2.7.0). In the "Location of the text file"
section, type in the path to your dump filename, or use the Browse
button. Then click Go.  With version 2.7.0, the import engine has been
re–written, if possible it is suggested that you upgrade to take
advantage of the new features.  For additional help on this subject,
look for the word "upload" in this document.

Note: For errors while importing of dumps exported from older MySQL versions to newer MySQL versions,
please check :ref:`faq6_41`.

.. _faq6_6:

6.6 How can I use the relation table in Query-by-example?
---------------------------------------------------------

Here is an example with the tables persons, towns and countries, all
located in the database "mydb". If you don't have a ``pma__relation``
table, create it as explained in the configuration section. Then
create the example tables:

.. code-block:: mysql

    CREATE TABLE REL_countries (
    country_code char(1) NOT NULL default '',
    description varchar(10) NOT NULL default '',
    PRIMARY KEY (country_code)
    ) ENGINE=MyISAM;

    INSERT INTO REL_countries VALUES ('C', 'Canada');

    CREATE TABLE REL_persons (
    id tinyint(4) NOT NULL auto_increment,
    person_name varchar(32) NOT NULL default '',
    town_code varchar(5) default '0',
    country_code char(1) NOT NULL default '',
    PRIMARY KEY (id)
    ) ENGINE=MyISAM;

    INSERT INTO REL_persons VALUES (11, 'Marc', 'S', 'C');
    INSERT INTO REL_persons VALUES (15, 'Paul', 'S', 'C');

    CREATE TABLE REL_towns (
    town_code varchar(5) NOT NULL default '0',
    description varchar(30) NOT NULL default '',
    PRIMARY KEY (town_code)
    ) ENGINE=MyISAM;

    INSERT INTO REL_towns VALUES ('S', 'Sherbrooke');
    INSERT INTO REL_towns VALUES ('M', 'Montréal');

To setup appropriate links and display information:

* on table "REL\_persons" click Structure, then Relation view
* for "town\_code", choose from dropdowns, "mydb", "REL\_towns", "town\_code"
  for foreign database, table and column respectively
* for "country\_code", choose  from dropdowns, "mydb", "REL\_countries",
  "country\_code" for foreign database, table and column respectively
* on table "REL\_towns" click Structure, then Relation view
* in "Choose column to display", choose "description"
* repeat the two previous steps for table "REL\_countries"

Then test like this:

* Click on your db name in the navigation panel
* Choose "Query"
* Use tables: persons, towns, countries
* Click "Update query"
* In the columns row, choose persons.person\_name and click the "Show"
  tickbox
* Do the same for towns.description and countries.descriptions in the
  other 2 columns
* Click "Update query" and you will see in the query box that the
  correct joins have been generated
* Click "Submit query"

.. _faqdisplay:

6.7 How can I use the "display column" feature?
-----------------------------------------------

Starting from the previous example, create the ``pma__table_info`` as
explained in the configuration section, then browse your persons
table, and move the mouse over a town code or country code.  See also
:ref:`faq6_21` for an additional feature that "display column"
enables: drop-down list of possible values.

.. _faqpdf:

6.8 How can I produce a PDF schema of my database?
--------------------------------------------------

First the configuration variables "relation", "table\_coords" and
"pdf\_pages" have to be filled in.

* Select your database in the navigation panel.
* Choose ":guilabel:`Designer`" in the navigation bar at the top.
* Move the tables the way you want them.
* Choose ":guilabel:`Export schema`" in the left menu.
* The export modal will open.
* Select the type of export to :term:`PDF`, you may adjust the other settings.
* Submit the form and the file will start downloading.

.. seealso::

    :ref:`relations`

.. _faq6_9:

6.9 phpMyAdmin is changing the type of one of my columns!
---------------------------------------------------------

No, it's MySQL that is doing `silent column type changing
<https://dev.mysql.com/doc/refman/5.7/en/silent-column-changes.html>`_.

.. _underscore:

6.10 When creating a privilege, what happens with underscores in the database name?
-----------------------------------------------------------------------------------

If you do not put a backslash before the underscore, this is a
wildcard grant, and the underscore means "any character". So, if the
database name is "john\_db", the user would get rights to john1db,
john2db ... If you put a backslash before the underscore, it means
that the database name will have a real underscore.

.. _faq6_11:

6.11 What is the curious symbol ø in the statistics pages?
----------------------------------------------------------

It means "average".

.. _faqexport:

6.12 I want to understand some Export options.
----------------------------------------------

**Structure:**

* "Add DROP TABLE" will add a line telling MySQL to `drop the table
  <https://dev.mysql.com/doc/refman/5.7/en/drop-table.html>`_, if it already
  exists during the import. It does NOT drop the table after your
  export, it only affects the import file.
* "If Not Exists" will only create the table if it doesn't exist.
  Otherwise, you may get an error if the table name exists but has a
  different structure.
* "Add AUTO\_INCREMENT value" ensures that AUTO\_INCREMENT value (if
  any) will be included in backup.
* "Enclose table and column names with backquotes" ensures that column
  and table names formed with special characters are protected.
* "Add into comments" includes column comments, relations, and media
  types set in the pmadb in the dump as :term:`SQL` comments
  (*/\* xxx \*/*).

**Data:**

* "Complete inserts" adds the column names on every INSERT command, for
  better documentation (but resulting file is bigger).
* "Extended inserts" provides a shorter dump file by using only once the
  INSERT verb and the table name.
* "Delayed inserts" are best explained in the `MySQL manual - INSERT DELAYED Syntax
  <https://dev.mysql.com/doc/refman/5.7/en/insert-delayed.html>`_.
* "Ignore inserts" treats errors as a warning instead. Again, more info
  is provided in the `MySQL manual - INSERT Syntax
  <https://dev.mysql.com/doc/refman/5.7/en/insert.html>`_, but basically with
  this selected, invalid values are adjusted and inserted rather than
  causing the entire statement to fail.

.. _faq6_13:

6.13 I would like to create a database with a dot in its name.
--------------------------------------------------------------

This is a bad idea, because in MySQL the syntax "database.table" is
the normal way to reference a database and table name. Worse, MySQL
will usually let you create a database with a dot, but then you cannot
work with it, nor delete it.

.. _faqsqlvalidator:

6.14 (withdrawn).
-----------------

.. _faq6_15:

6.15 I want to add a BLOB column and put an index on it, but MySQL says "BLOB column '...' used in key specification without a key length".
-------------------------------------------------------------------------------------------------------------------------------------------

The right way to do this, is to create the column without any indexes,
then display the table structure and use the "Create an index" dialog.
On this page, you will be able to choose your BLOB column, and set a
size to the index, which is the condition to create an index on a BLOB
column.

.. _faq6_16:

6.16 How can I simply move in page with plenty editing fields?
--------------------------------------------------------------

You can use :kbd:`Ctrl+arrows` (:kbd:`Option+Arrows` in Safari) for moving on
most pages with many editing fields (table structure changes, row editing,
etc.).

.. _faq6_17:

6.17 Transformations: I can't enter my own mimetype! What is this feature then useful for?
------------------------------------------------------------------------------------------

Defining mimetypes is of no use if you can't put
transformations on them. Otherwise you could just put a comment on the
column. Because entering your own mimetype will cause serious syntax
checking issues and validation, this introduces a high-risk false-
user-input situation. Instead you have to initialize mimetypes using
functions or empty mimetype definitions.

Plus, you have a whole overview of available mimetypes. Who knows all those
mimetypes by heart so they can enter it at will?

.. _faqbookmark:

6.18 Bookmarks: Where can I store bookmarks? Why can't I see any bookmarks below the query box? What are these variables for?
-----------------------------------------------------------------------------------------------------------------------------

You need to have configured the :ref:`linked-tables` for using bookmarks
feature. Once you have done that, you can use bookmarks in the :guilabel:`SQL` tab.

.. seealso:: :ref:`bookmarks`

.. _faq6_19:

6.19 How can I create simple LATEX document to include exported table?
----------------------------------------------------------------------

You can simply include table in your LATEX documents,
minimal sample document should look like following one (assuming you
have table exported in file :file:`table.tex`):

.. code-block:: latex

    \documentclass{article} % or any class you want
    \usepackage{longtable}  % for displaying table
    \begin{document}        % start of document
    \include{table}         % including exported table
    \end{document}          % end of document

.. _faq6_20:

6.20 I see a lot of databases which are not mine, and cannot access them.
-------------------------------------------------------------------------

You have one of these global privileges: CREATE TEMPORARY TABLES, SHOW
DATABASES, LOCK TABLES. Those privileges also enable users to see all the
database names. So if your users do not need those privileges, you can remove
them and their databases list will shorten.

.. seealso:: <https://bugs.mysql.com/bug.php?id=179>

.. _faq6_21:

6.21 In edit/insert mode, how can I see a list of possible values for a column, based on some foreign table?
------------------------------------------------------------------------------------------------------------

You have to setup appropriate links between the tables, and also setup
the "display column" in the foreign table. See :ref:`faq6_6` for an
example. Then, if there are 100 values or less in the foreign table, a
drop-down list of values will be available. You will see two lists of
values, the first list containing the key and the display column, the
second list containing the display column and the key. The reason for
this is to be able to type the first letter of either the key or the
display column. For 100 values or more, a distinct window will appear,
to browse foreign key values and choose one. To change the default
limit of 100, see :config:option:`$cfg['ForeignKeyMaxLimit']`.

.. _faq6_22:

6.22 Bookmarks: Can I execute a default bookmark automatically when entering Browse mode for a table?
-----------------------------------------------------------------------------------------------------

Yes. If a bookmark has the same label as a table name and it's not a
public bookmark, it will be executed.

.. seealso:: :ref:`bookmarks`

.. _faq6_23:

6.23 Export: I heard phpMyAdmin can export Microsoft Excel files?
-----------------------------------------------------------------

You can use :term:`CSV` for Microsoft Excel,
which works out of the box.

.. versionchanged:: 3.4.5
    Since phpMyAdmin 3.4.5 support for direct export to Microsoft Excel version
    97 and newer was dropped.

.. _faq6_24:

6.24 Now that phpMyAdmin supports native MySQL 4.1.x column comments, what happens to my column comments stored in pmadb?
-------------------------------------------------------------------------------------------------------------------------

Automatic migration of a table's pmadb-style column comments to the
native ones is done whenever you enter Structure page for this table.

.. _faq6_25:

6.25 (withdrawn).
-----------------

.. _faq6_26:

6.26 How can I select a range of rows?
--------------------------------------

Click the first row of the range, hold the shift key and click the
last row of the range. This works everywhere you see rows, for example
in Browse mode or on the Structure page.

.. _faq6_27:

6.27 What format strings can I use?
-----------------------------------

In all places where phpMyAdmin accepts format strings, you can use
``@VARIABLE@`` expansion and `strftime <https://www.php.net/strftime>`_
format strings. The expanded variables depend on a context (for
example, if you haven't chosen a table, you can not get the table
name), but the following variables can be used:

``@HTTP_HOST@``
    HTTP host that runs phpMyAdmin
``@SERVER@``
    MySQL server name
``@VERBOSE@``
    Verbose MySQL server name as defined in :config:option:`$cfg['Servers'][$i]['verbose']`
``@VSERVER@``
    Verbose MySQL server name if set, otherwise normal
``@DATABASE@``
    Currently opened database
``@TABLE@``
    Currently opened table
``@COLUMNS@``
    Columns of the currently opened table
``@PHPMYADMIN@``
    phpMyAdmin with version

.. _faq6_28:

6.28 (withdrawn).
-----------------

.. _faq6_29:

6.29 Why can't I get a chart from my query result table?
--------------------------------------------------------

Not every table can be put to the chart. Only tables with one, two or
three columns can be visualised as a chart. Moreover the table must be
in a special format for chart script to understand it. Currently
supported formats can be found in :ref:`charts`.

.. _faq6_30:

6.30 Import: How can I import ESRI Shapefiles?
----------------------------------------------

An ESRI Shapefile is actually a set of several files, where .shp file
contains geometry data and .dbf file contains data related to those
geometry data. To read data from .dbf file you need to have PHP
compiled with the dBase extension (--enable-dbase). Otherwise only
geometry data will be imported.

To upload these set of files you can use either of the following
methods:

Configure upload directory with :config:option:`$cfg['UploadDir']`, upload both .shp and .dbf files with
the same filename and chose the .shp file from the import page.

Create a zip archive with .shp and .dbf files and import it. For this
to work, you need to set :config:option:`$cfg['TempDir']` to a place where the web server user can
write (for example ``'./tmp'``).

To create the temporary directory on a UNIX-based system, you can do:

.. code-block:: sh

    cd phpMyAdmin
    mkdir tmp
    chmod o+rwx tmp

.. _faq6_31:

6.31 How do I create a relation in designer?
--------------------------------------------

To select relation, click:  The display column is shown in pink. To
set/unset a column as the display column, click the "Choose column to
display" icon, then click on the appropriate column name.

.. _faq6_32:

6.32 How can I use the zoom search feature?
-------------------------------------------

The Zoom search feature is an alternative to table search feature. It allows
you to explore a table by representing its data in a scatter plot. You can
locate this feature by selecting a table and clicking the :guilabel:`Search`
tab. One of the sub-tabs in the :guilabel:`Table Search` page is
:guilabel:`Zoom Search`.

Consider the table REL\_persons in :ref:`faq6_6` for
an example. To use zoom search, two columns need to be selected, for
example, id and town\_code. The id values will be represented on one
axis and town\_code values on the other axis. Each row will be
represented as a point in a scatter plot based on its id and
town\_code. You can include two additional search criteria apart from
the two fields to display.

You can choose which field should be
displayed as label for each point. If a display column has been set
for the table (see :ref:`faqdisplay`), it is taken as the label unless
you specify otherwise. You can also select the maximum number of rows
you want to be displayed in the plot by specifing it in the 'Max rows
to plot' field. Once you have decided over your criteria, click 'Go'
to display the plot.

After the plot is generated, you can use the
mouse wheel to zoom in and out of the plot. In addition, panning
feature is enabled to navigate through the plot. You can zoom-in to a
certain level of detail and use panning to locate your area of
interest. Clicking on a point opens a dialogue box, displaying field
values of the data row represented by the point. You can edit the
values if required and click on submit to issue an update query. Basic
instructions on how to use can be viewed by clicking the 'How to use?'
link located just above the plot.

.. _faq6_33:

6.33 When browsing a table, how can I copy a column name?
---------------------------------------------------------

Selecting the name of the column within the browse table header cell
for copying is difficult, as the columns support reordering by
dragging the header cells as well as sorting by clicking on the linked
column name. To copy a column name, double-click on the empty area
next to the column name, when the tooltip tells you to do so. This
will show you an input box with the column name. You may right-click
the column name within this input box to copy it to your clipboard.

.. _faq6_34:

6.34 How can I use the Favorite Tables feature?
---------------------------------------------------------

Favorite Tables feature is very much similar to Recent Tables feature.
It allows you to add a shortcut for the frequently used tables of any
database in the navigation panel . You can easily navigate to any table
in the list by simply choosing it from the list. These tables are stored
in your browser's local storage if you have not configured your
`phpMyAdmin Configuration Storage`. Otherwise these entries are stored in
`phpMyAdmin Configuration Storage`.

IMPORTANT: In absence of `phpMyAdmin Configuration Storage`, your Favorite
tables may be different in different browsers based on your different
selections in them.

To add a table to Favorite list simply click on the `Gray` star in front
of a table name in the list of tables of a Database and wait until it
turns to `Yellow`.
To remove a table from list, simply click on the `Yellow` star and
wait until it turns `Gray` again.

Using :config:option:`$cfg['NumFavoriteTables']` in your :file:`config.inc.php`
file, you can define the  maximum number of favorite tables shown in the
navigation panel. Its default value is `10`.

.. _faq6_35:

6.35 How can I use the Range search feature?
---------------------------------------------------------

With the help of range search feature, one can specify a range of values for
particular column(s) while performing search operation on a table from the `Search`
tab.

To use this feature simply click on the `BETWEEN` or `NOT BETWEEN` operators
from the operator select list in front of the column name. On choosing one of the
above options, a dialog box will show up asking for the `Minimum` and `Maximum`
value for that column. Only the specified range of values will be included
in case of `BETWEEN` and excluded in case of `NOT BETWEEN` from the final results.

Note: The Range search feature will work only `Numeric` and `Date` data type columns.

.. _faq6_36:

6.36 What is Central columns and how can I use this feature?
------------------------------------------------------------

As the name suggests, the Central columns feature enables to maintain a central list of
columns per database to avoid similar name for the same data element and bring consistency
of data type for the same data element. You can use the central list of columns to
add an element to any table structure in that database which will save from writing
similar column name and column definition.

To add a column to central list, go to table structure page, check the columns you want
to include and then simply click on "Add to central columns". If you want to add all
unique columns from more than one table from a database then go to database structure page,
check the tables you want to include and then select "Add columns to central list".

To remove a column from central list, go to Table structure page, check the columns you want
to remove and then simply click on "Remove from central columns". If you want to remove all
columns from more than one tables from a database then go to database structure page,
check the tables you want to include and then select "Remove columns from central list".

To view and manage the central list, select the database you want to manage central columns
for then from the top menu click on "Central columns". You will be taken to a page where
you will have options to edit, delete or add new columns to central list.

.. _faq6_37:

6.37 How can I use Improve Table structure feature?
---------------------------------------------------------

Improve table structure feature helps to bring the table structure upto
Third Normal Form. A wizard is presented to user which asks questions about the
elements during the various steps for normalization and a new structure is proposed
accordingly to bring the table into the First/Second/Third Normal form.
On startup of the wizard, user gets to select upto what normal form they want to
normalize the table structure.

Here is an example table which you can use to test all of the three First, Second and
Third Normal Form.

.. code-block:: mysql

    CREATE TABLE `VetOffice` (
     `petName` varchar(64) NOT NULL,
     `petBreed` varchar(64) NOT NULL,
     `petType` varchar(64) NOT NULL,
     `petDOB` date NOT NULL,
     `ownerLastName` varchar(64) NOT NULL,
     `ownerFirstName` varchar(64) NOT NULL,
     `ownerPhone1` int(12) NOT NULL,
     `ownerPhone2` int(12) NOT NULL,
     `ownerEmail` varchar(64) NOT NULL,
    );

The above table is not in First normal Form as no :term:`primary key` exists. Primary key
is supposed to be (`petName`,`ownerLastName`,`ownerFirstName`) . If the :term:`primary key`
is chosen as suggested the resultant table won't be in Second as well as Third Normal
form as the following dependencies exists.

.. code-block:: mysql

    (OwnerLastName, OwnerFirstName) -> OwnerEmail
    (OwnerLastName, OwnerFirstName) -> OwnerPhone
    PetBreed -> PetType

Which says, OwnerEmail depends on OwnerLastName and OwnerFirstName.
OwnerPhone depends on OwnerLastName and OwnerFirstName.
PetType depends on PetBreed.

.. _faq6_38:

6.38 How can I reassign auto-incremented values?
------------------------------------------------

Some users prefer their AUTO_INCREMENT values to be consecutive; this is not
always the case after row deletion.

Here are the steps to accomplish this. These are manual steps because they
involve a manual verification at one point.

* Ensure that you have exclusive access to the table to rearrange

* On your :term:`primary key` column (i.e. id), remove the AUTO_INCREMENT setting

* Delete your primary key in Structure > indexes

* Create a new column future_id as primary key, AUTO_INCREMENT

* Browse your table and verify that the new increments correspond to what
  you're expecting

* Drop your old id column

* Rename the future_id column to id

* Move the new id column via Structure > Move columns

.. _faq6_39:

6.39 What is the "Adjust privileges" option when renaming, copying, or moving a database, table, column, or procedure?
----------------------------------------------------------------------------------------------------------------------

When renaming/copying/moving a database/table/column/procedure,
MySQL does not adjust the original privileges relating to these objects
on its own. By selecting this option, phpMyAdmin will adjust the privilege
table so that users have the same privileges on the new items.

For example: A user 'bob'@'localhost' has a 'SELECT' privilege on a
column named 'id'. Now, if this column is renamed to 'id_new', MySQL,
on its own, would **not** adjust the column privileges to the new column name.
phpMyAdmin can make this adjustment for you automatically.

Notes:

* While adjusting privileges for a database, the privileges of all
  database-related elements (tables, columns and procedures) are also adjusted
  to the database's new name.

* Similarly, while adjusting privileges for a table, the privileges of all
  the columns inside the new table are also adjusted.

* While adjusting privileges, the user performing the operation **must** have the following
  privileges:

  * SELECT, INSERT, UPDATE, DELETE privileges on following tables:
    `mysql`.`db`, `mysql`.`columns_priv`, `mysql`.`tables_priv`, `mysql`.`procs_priv`
  * FLUSH privilege (GLOBAL)

Thus, if you want to replicate the database/table/column/procedure as it is
while renaming/copying/moving these objects, make sure you have checked this option.

.. _faq6_40:

6.40 I see "Bind parameters" checkbox in the "SQL" page. How do I write parameterized SQL queries?
--------------------------------------------------------------------------------------------------

From version 4.5, phpMyAdmin allows users to execute parameterized queries in the "SQL" page.
Parameters should be prefixed with a colon(:) and when the "Bind parameters" checkbox is checked
these parameters will be identified and input fields for these parameters will be presented.
Values entered in these field will be substituted in the query before being executed.

.. _faq6_41:

6.41 I get import errors while importing the dumps exported from older MySQL versions (pre-5.7.6) into newer MySQL versions (5.7.7+), but they work fine when imported back on same older versions ?
--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------

If you get errors like *#1031 - Table storage engine for 'table_name' doesn't have this option*
while importing the dumps exported from pre-5.7.7 MySQL servers into new MySQL server versions 5.7.7+,
it might be because ROW_FORMAT=FIXED is not supported with InnoDB tables. Moreover, the value of
`innodb_strict_mode <https://dev.mysql.com/doc/refman/5.7/en/innodb-parameters.html#sysvar_innodb_strict_mode>`_ would define if this would be reported as a warning or as an error.

Since MySQL version 5.7.9, the default value for `innodb_strict_mode` is `ON` and thus would generate
an error when such a CREATE TABLE or ALTER TABLE statement is encountered.

There are two ways of preventing such errors while importing:

* Change the value of `innodb_strict_mode` to `OFF` before starting the import and turn it `ON` after
  the import is successfully completed.
* This can be achieved in two ways:

  * Go to 'Variables' page and edit the value of `innodb_strict_mode`
  * Run the query : `SET GLOBAL `innodb_strict_mode` = '[value]'`

After the import is done, it is suggested that the value of `innodb_strict_mode` should be reset to the
original value.

.. _faqproject:

phpMyAdmin project
++++++++++++++++++

.. _faq7_1:

7.1 I have found a bug. How do I inform developers?
---------------------------------------------------

Our issues tracker is located at <https://github.com/phpmyadmin/phpmyadmin/issues>.
For security issues, please refer to the instructions at <https://www.phpmyadmin.net/security> to email
the developers directly.

.. _faq7_2:

7.2 I want to translate the messages to a new language or upgrade an existing language, where do I start?
---------------------------------------------------------------------------------------------------------

Translations are very welcome and all you need to have are the
language skills. The easiest way is to use our `online translation
service <https://hosted.weblate.org/projects/phpmyadmin/>`_. You can check
out all the possibilities to translate in the `translate section on
our website <https://www.phpmyadmin.net/translate/>`_.

.. _faq7_3:

7.3 I would like to help out with the development of phpMyAdmin. How should I proceed?
--------------------------------------------------------------------------------------

We welcome every contribution to the development of phpMyAdmin. You
can check out all the possibilities to contribute in the `contribute
section on our website
<https://www.phpmyadmin.net/contribute/>`_.

.. seealso:: :ref:`developers`

.. _faqsecurity:

Security
++++++++

.. _faq8_1:

8.1 Where can I get information about the security alerts issued for phpMyAdmin?
--------------------------------------------------------------------------------

Please refer to <https://www.phpmyadmin.net/security/>.

.. _faq8_2:

8.2 How can I protect phpMyAdmin against brute force attacks?
-------------------------------------------------------------

If you use Apache web server, phpMyAdmin exports information about
authentication to the Apache environment and it can be used in Apache
logs. Currently there are two variables available:

``userID``
    User name of currently active user (they do not have to be logged in).
``userStatus``
    Status of currently active user, one of ``ok`` (user is logged in),
    ``mysql-denied`` (MySQL denied user login), ``allow-denied`` (user denied
    by allow/deny rules), ``root-denied`` (root is denied in configuration),
    ``empty-denied`` (empty password is denied).

``LogFormat`` directive for Apache can look like following:

.. code-block:: apache

    LogFormat "%h %l %u %t \"%r\" %>s %b \"%{Referer}i\" \"%{User-Agent}i\" %{userID}n %{userStatus}n"   pma_combined

You can then use any log analyzing tools to detect possible break-in
attempts.

.. _faq8_3:

8.3 Why are there path disclosures when directly loading certain files?
-----------------------------------------------------------------------

This is a server configuration problem. Never enable ``display_errors`` on a production site.

.. _faq8_4:

8.4 CSV files exported from phpMyAdmin could allow a formula injection attack.
------------------------------------------------------------------------------

It is possible to generate a :term:`CSV` file that, when imported to a spreadsheet program such as Microsoft Excel,
could potentially allow the execution of arbitrary commands.

The CSV files generated by phpMyAdmin could potentially contain text that would be interpreted by a spreadsheet program as
a formula, but we do not believe escaping those fields is the proper behavior. There is no means to properly escape and
differentiate between a desired text output and a formula that should be escaped, and CSV is a text format where function
definitions should not be interpreted anyway. We have discussed this at length and feel it is the responsibility of the
spreadsheet program to properly parse and sanitize such data on input instead.

Google also has a `similar view <https://sites.google.com/site/bughunteruniversity/nonvuln/csv-excel-formula-injection>`_.

.. _faqsynchronization:

Synchronization
+++++++++++++++

.. _faq9_1:

9.1 (withdrawn).
----------------

.. _faq9_2:

9.2 (withdrawn).
----------------

.. _require:

Requirements
============

Web server
----------

Since phpMyAdmin's interface is based entirely in your browser, you'll need a
web server (such as Apache, nginx, :term:`IIS`) to install phpMyAdmin's files into.

PHP
---

* You need PHP 7.2.5 or newer, with ``session`` support, the Standard PHP Library
  (SPL) extension, hash, ctype, and JSON support.

* The ``mbstring`` extension (see :term:`mbstring`) is strongly recommended
  for performance reasons.

* To support uploading of ZIP files, you need the PHP ``zip`` extension.

* You need GD2 support in PHP to display inline thumbnails of JPEGs
  ("image/jpeg: inline") with their original aspect ratio.

* When using the cookie authentication (the default), the `openssl
  <https://www.php.net/openssl>`_ extension is strongly suggested.

* To support upload progress bars, see :ref:`faq2_9`.

* To support XML and Open Document Spreadsheet importing, you need the
  `libxml <https://www.php.net/libxml>`_ extension.

* To support reCAPTCHA on the login page, you need the
  `openssl <https://www.php.net/openssl>`_ extension.

* To support displaying phpMyAdmin's latest version, you need to enable
  ``allow_url_open`` in your :file:`php.ini` or to have the
  `curl <https://www.php.net/curl>`_ extension.

.. seealso:: :ref:`faq1_31`, :ref:`authentication_modes`

Database
--------

phpMyAdmin supports MySQL-compatible databases.

* MySQL 5.5 or newer
* MariaDB 5.5 or newer

.. seealso:: :ref:`faq1_17`

Web browser
-----------

To access phpMyAdmin you need a web browser with cookies and JavaScript
enabled.

You need a browser which is supported by Bootstrap 4.5, see
<https://getbootstrap.com/docs/4.5/getting-started/browsers-devices/>.

.. versionchanged:: 5.2.0

    You need a browser which is supported by Bootstrap 5.0, see
    <https://getbootstrap.com/docs/5.0/getting-started/browsers-devices/>.

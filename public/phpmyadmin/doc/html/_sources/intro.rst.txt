.. _intro:

Introduction
============

phpMyAdmin is a free software tool written in PHP that is intended to handle the
administration of a MySQL or MariaDB database server. You can use phpMyAdmin to
perform most administration tasks, including creating a database, running queries,
and adding user accounts.

Supported features
------------------

Currently phpMyAdmin can:

* create, browse, edit, and drop databases, tables, views, columns, and indexes
* display multiple results sets through stored procedures or queries
* create, copy, drop, rename and alter databases, tables, columns and
  indexes
* maintenance server, databases and tables, with proposals on server
  configuration
* execute, edit and bookmark any :term:`SQL`-statement, even batch-queries
* load text files into tables
* create [#f1]_ and read dumps of tables
* export [#f1]_ data to various formats: :term:`CSV`, :term:`XML`, :term:`PDF`,
  :term:`ISO`/:term:`IEC` 26300 - :term:`OpenDocument` Text and Spreadsheet, Microsoft
  Word 2000, and LATEX formats
* import data and :term:`MySQL` structures from :term:`OpenDocument` spreadsheets, as
  well as :term:`XML`, :term:`CSV`, and :term:`SQL` files
* administer multiple servers
* add, edit, and remove MySQL user accounts and privileges
* check referential integrity in MyISAM tables
* using Query-by-example (QBE), create complex queries automatically
  connecting required tables
* create :term:`PDF` graphics of your
  database layout
* search globally in a database or a subset of it
* transform stored data into any format using a set of predefined
  functions, like displaying BLOB-data as image or download-link
* track changes on databases, tables and views
* support InnoDB tables and foreign keys
* support mysqli, the improved MySQL extension see :ref:`faq1_17`
* create, edit, call, export and drop stored procedures and functions
* create, edit, export and drop events and triggers
* communicate in `80 different languages
  <https://www.phpmyadmin.net/translations/>`_

Shortcut keys
-------------

Currently phpMyAdmin supports following shortcuts:

* k - Toggle console
* h - Go to home page
* s - Open settings
* d + s - Go to database structure (Provided you are in database related page)
* d + f - Search database (Provided you are in database related page)
* t + s - Go to table structure (Provided you are in table related page)
* t + f - Search table (Provided you are in table related page)
* backspace - Takes you to older page.

A word about users
------------------

Many people have difficulty understanding the concept of user
management with regards to phpMyAdmin. When a user logs in to
phpMyAdmin, that username and password are passed directly to MySQL.
phpMyAdmin does no account management on its own (other than allowing
one to manipulate the MySQL user account information); all users must
be valid MySQL users.

.. rubric:: Footnotes

.. [#f1]

    phpMyAdmin can compress (:term:`ZIP`, :term:`GZip` or :term:`RFC 1952`
    formats) dumps and :term:`CSV` exports if you use PHP with
    :term:`Zlib` support (``--with-zlib``).
    Proper support may also need changes in :file:`php.ini`.

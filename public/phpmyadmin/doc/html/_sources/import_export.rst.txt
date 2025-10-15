Import and export
=================

Import
++++++

To import data, go to the "Import" tab in phpMyAdmin. To import data into a
specific database or table, open the database or table before going to the
"Import" tab.

In addition to the standard Import and Export tab, you can also import an SQL
file directly by dragging and dropping it from your local file manager to the
phpMyAdmin interface in your web browser.

If you are having troubles importing big files, please consult :ref:`faq1_16`.

You can import using following methods:

Form based upload

    Can be used with any supported format, also (b|g)zipped files, e.g., mydump.sql.gz .

Form based SQL Query

    Can be used with valid SQL dumps.

Using upload directory

    You can specify an upload directory on your web server where phpMyAdmin is installed, after uploading your file into this directory you can select this file in the import dialog of phpMyAdmin, see :config:option:`$cfg['UploadDir']`.

phpMyAdmin can import from several various commonly used formats.

CSV
---

Comma separated values format which is often used by spreadsheets or various other programs for export/import.

.. note::

    When importing data into a table from a CSV file where the table has an
    'auto_increment' field, make the 'auto_increment' value for each record in
    the CSV field to be '0' (zero). This allows the 'auto_increment' field to
    populate correctly.

It is now possible to import a CSV file at the server or database level.
Instead of having to create a table to import the CSV file into, a best-fit
structure will be determined for you and the data imported into it, instead.
All other features, requirements, and limitations are as before.

CSV using LOAD DATA
-------------------

Similar to CSV, only using the internal MySQL parser and not the phpMyAdmin one.

ESRI Shape File
---------------

The ESRI shapefile or simply a shapefile is a popular geospatial vector data
format for geographic information systems software. It is developed and
regulated by Esri as a (mostly) open specification for data interoperability
among Esri and other software products.

MediaWiki
---------

MediaWiki files, which can be exported by phpMyAdmin (version 4.0 or later),
can now also be imported. This is the format used by Wikipedia to display
tables.

Open Document Spreadsheet (ODS)
-------------------------------

OpenDocument workbooks containing one or more spreadsheets can now be directly imported.

When importing an ODS spreadsheet, the spreadsheet must be named in a specific way in order to make the
import as simple as possible.

Table name
~~~~~~~~~~

During import, phpMyAdmin uses the sheet name as the table name; you should rename the
sheet in your spreadsheet program in order to match your existing table name (or the table you wish to create,
though this is less of a concern since you could quickly rename the new table from the Operations tab).

Column names
~~~~~~~~~~~~

You should also make the first row of your spreadsheet a header with the names of the columns (this can be
accomplished by inserting a new row at the top of your spreadsheet). When on the Import screen, select the
checkbox for "The first line of the file contains the table column names;" this way your newly imported
data will go to the proper columns.

.. note::

    Formulas and calculations will NOT be evaluated, rather, their value from
    the most recent save will be loaded. Please ensure that all values in the
    spreadsheet are as needed before importing it.

SQL
---

SQL can be used to make any manipulation on data, it is also useful for restoring backed up data.

XML
---

XML files exported by phpMyAdmin (version 3.3.0 or later) can now be imported.
Structures (databases, tables, views, triggers, etc.) and/or data will be
created depending on the contents of the file.

The supported xml schemas are not yet documented in this wiki.

Export
++++++

phpMyAdmin can export into text files (even compressed) on your local disk (or
a special the webserver :config:option:`$cfg['SaveDir']` folder) in various
commonly used formats:

CodeGen
-------

`NHibernate <https://en.wikipedia.org/wiki/NHibernate>`_ file format. Planned
versions: Java, Hibernate, PHP PDO, JSON, etc. So the preliminary name is
codegen.

CSV
---

Comma separated values format which is often used by spreadsheets or various
other programs for export/import.

CSV for Microsoft Excel
-----------------------

This is just preconfigured version of CSV export which can be imported into
most English versions of Microsoft Excel. Some localised versions (like
"Danish") are expecting ";" instead of "," as field separator.

Microsoft Word 2000
-------------------

If you're using Microsoft Word 2000 or newer (or compatible such as
OpenOffice.org), you can use this export.

JSON
----

JSON (JavaScript Object Notation) is a lightweight data-interchange format. It
is easy for humans to read and write and it is easy for machines to parse and
generate.

.. versionchanged:: 4.7.0

    The generated JSON structure has been changed in phpMyAdmin 4.7.0 to
    produce valid JSON data.

The generated JSON is list of objects with following attributes:

.. js:data:: type

    Type of given object, can be one of:

    ``header``
        Export header containing comment and phpMyAdmin version.
    ``database``
        Start of a database marker, containing name of database.
    ``table``
        Table data export.

.. js:data:: version

    Used in ``header`` :js:data:`type` and indicates phpMyAdmin version.

.. js:data:: comment

    Optional textual comment.

.. js:data:: name

    Object name - either table or database based on :js:data:`type`.

.. js:data:: database

    Database name for ``table`` :js:data:`type`.

.. js:data:: data

    Table content for ``table`` :js:data:`type`.

Sample output:

.. code-block:: json

    [
        {
            "comment": "Export to JSON plugin for phpMyAdmin",
            "type": "header",
            "version": "4.7.0-dev"
        },
        {
            "name": "cars",
            "type": "database"
        },
        {
            "data": [
                {
                    "car_id": "1",
                    "description": "Green Chrysler 300",
                    "make_id": "5",
                    "mileage": "113688",
                    "price": "13545.00",
                    "transmission": "automatic",
                    "yearmade": "2007"
                }
            ],
            "database": "cars",
            "name": "cars",
            "type": "table"
        },
        {
            "data": [
                {
                    "make": "Chrysler",
                    "make_id": "5"
                }
            ],
            "database": "cars",
            "name": "makes",
            "type": "table"
        }
    ]

LaTeX
-----

If you want to embed table data or structure in LaTeX, this is right choice for you.

LaTeX is a typesetting system that is very suitable for producing scientific
and mathematical documents of high typographical quality. It is also suitable
for producing all sorts of other documents, from simple letters to complete
books. LaTeX uses TeX as its formatting engine. Learn more about TeX and
LaTeX on `the Comprehensive TeX Archive Network <https://www.ctan.org/>`_
also see the `short description od TeX <https://www.ctan.org/tex/>`_.

The output needs to be embedded into a LaTeX document before it can be
rendered, for example in following document:

.. code-block:: latex

    \documentclass{article}
    \title{phpMyAdmin SQL output}
    \author{}
    \usepackage{longtable,lscape}
    \date{}
    \setlength{\parindent}{0pt}
    \usepackage[left=2cm,top=2cm,right=2cm,nohead,nofoot]{geometry}
    \pdfpagewidth 210mm
    \pdfpageheight 297mm
    \begin{document}
    \maketitle

    % insert phpMyAdmin LaTeX Dump here

    \end{document}

MediaWiki
---------

Both tables and databases can be exported in the MediaWiki format, which is
used by Wikipedia to display tables. It can export structure, data or both,
including table names or headers.

OpenDocument Spreadsheet
------------------------

Open standard for spreadsheet data, which is being widely adopted. Many recent
spreadsheet programs, such as LibreOffice, OpenOffice, Microsoft Office or
Google Docs can handle this format.

OpenDocument Text
-----------------

New standard for text data which is being widely adopted. Most recent word
processors (such as LibreOffice, OpenOffice, Microsoft Word, AbiWord or KWord)
can handle this.

PDF
---

For presentation purposes, non editable PDF might be best choice for you.

PHP Array
---------

You can generate a php file which will declare a multidimensional array with
the contents of the selected table or database.

SQL
---

Export in SQL can be used to restore your database, thus it is useful for
backing up.

The option 'Maximal length of created query' seems to be undocumented. But
experiments has shown that it splits large extended INSERTS so each one is no
bigger than the given number of bytes (or characters?). Thus when importing the
file, for large tables you avoid the error "Got a packet bigger than
'max_allowed_packet' bytes".

.. seealso::

    https://dev.mysql.com/doc/refman/5.7/en/packet-too-large.html

Data Options
~~~~~~~~~~~~

**Complete inserts** adds the column names to the SQL dump. This parameter
improves the readability and reliability of the dump. Adding the column names
increases the size of the dump, but when combined with Extended inserts it's
negligible.

**Extended inserts** combines multiple rows of data into a single INSERT query.
This will significantly decrease filesize for large SQL dumps, increases the
INSERT speed when imported, and is generally recommended.

Texy!
-----

`Texy! <https://texy.info/>`_ markup format. You can see example on `Texy! demo
<https://texy.info/en/try/4q5we>`_.

XML
---

Easily parsable export for use with custom scripts.

.. versionchanged:: 3.3.0

    The XML schema used has changed as of version 3.3.0

YAML
----

YAML is a data serialization format which is both human readable and
computationally powerful ( <https://yaml.org> ).

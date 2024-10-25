.. _transformations:

Transformations
===============

.. note::

    You need to have configured the :ref:`linked-tables` to use the transformations
    feature.

.. _transformationsintro:

Introduction
++++++++++++

To enable transformations, you have to set up the ``column_info``
table and the proper directives. Please see the :ref:`config` on how to do so.

phpMyAdmin has two different types of transformations: browser display
transformations, which affect only how the data is shown when browsing
through phpMyAdmin; and input transformations, which affect a value
prior to being inserted through phpMyAdmin.
You can apply different transformations to the contents of each
column. Each transformation has options to define how it will affect the
stored data.

Say you have a column ``filename`` which contains a filename. Normally
you would see in phpMyAdmin only this filename. Using display transformations
you can transform that filename into a HTML link, so you can click
inside of the phpMyAdmin structure on the column's link and will see
the file displayed in a new browser window. Using transformation
options you can also specify strings to append/prepend to a string or
the format you want the output stored in.

For a general overview of all available transformations and their
options, you can either go to the ``Change`` link for an existing column
or from the dialog to create a new column, in either case there is a link
on that column structure page for "Browser display transformation" and
"Input transformation" which will show more information about each
transformation that is available on your system.

For a tutorial on how to effectively use transformations, see our
`Link section <https://www.phpmyadmin.net/docs/>`_ on the
official phpMyAdmin homepage.

.. _transformationshowto:

Usage
+++++

Go to the table structure page (reached by clicking on
the 'Structure' link for a table). There click on "Change" (or the change
icon) and there you will see the five transformation--related fields at the end of the line.
They are called ':term:`Media type`', 'Browser transformation' and
'Transformation options'.

* The field ':term:`Media type`' is a drop-down field. Select the :term:`Media type` that
  corresponds to the column's contents. Please note that many transformations
  are inactive until a :term:`Media type` is selected.
* The field 'Browser display transformation' is a drop-down field. You can
  choose from a hopefully growing amount of pre-defined transformations.
  See below for information on how to build your own transformation.
  There are global transformations and mimetype-bound transformations.
  Global transformations can be used for any mimetype. They will take
  the mimetype, if necessary, into regard. Mimetype-bound
  transformations usually only operate on a certain mimetype. There are
  transformations which operate on the main mimetype (like 'image'),
  which will most likely take the subtype into regard, and those who
  only operate on a specific subtype (like 'image/jpeg'). You can use
  transformations on mimetypes for which the function was not defined
  for. There is no security check for you selected the right
  transformation, so take care of what the output will be like.
* The field 'Browser display transformation options' is a free-type textfield. You have
  to enter transform-function specific options here. Usually the
  transforms can operate with default options, but it is generally a
  good idea to look up the overview to see which options are necessary.
  Much like the ENUM/SET-Fields, you have to split up several options
  using the format 'a','b','c',...(NOTE THE MISSING BLANKS). This is
  because internally the options will be parsed as an array, leaving the
  first value the first element in the array, and so forth. If you want
  to specify a MIME character set you can define it in the
  transformation\_options. You have to put that outside of the pre-
  defined options of the specific mime-transform, as the last value of
  the set. Use the format "'; charset=XXX'". If you use a transform, for
  which you can specify 2 options and you want to append a character
  set, enter "'first parameter','second parameter','charset=us-ascii'".
  You can, however use the defaults for the parameters: "'','','charset
  =us-ascii'". The default options can be configured using
  :config:option:`$cfg['DefaultTransformations']`.
* 'Input transformation' is another drop-down menu that corresponds exactly
  with the instructions above for "Browser display transformation" except
  these these affect the data before insertion in to the database. These are
  most commonly used to either provide a specialized editor (for example, using
  the phpMyAdmin SQL editor interface) or selector (such as for uploading an image).
  It's also possible to manipulate the data such as converting an IPv4 address to binary
  or parsing it through a regular expression.
* Finally, 'Input transformation options' is the equivalent of the "Browser display
  transformation options" section above and is where optional and required parameters are entered.

.. _transformationsfiles:

File structure
++++++++++++++

All specific transformations for mimetypes are defined through class
files in the directory :file:`libraries/classes/Plugins/Transformations/`. Each of
them extends a certain transformation abstract class declared in
:file:`libraries/classes/Plugins/Transformations/Abs`.

They are stored in files to ease customization and to allow easy adding of
new or custom transformations.

Because the user cannot enter their own mimetypes, it is kept certain that
the transformations will always work. It makes no sense to apply a
transformation to a mimetype the transform-function doesn't know to
handle.

There is a file called :file:`libraries/classes/Plugins/Transformations.php` that provides some
basic functions which can be included by any other transform function.

The file name convention is ``[Mimetype]_[Subtype]_[Transformation
Name].php``, while the abstract class that it extends has the
name ``[Transformation Name]TransformationsPlugin``. All of the
methods that have to be implemented by a transformations plug-in are:

#. getMIMEType() and getMIMESubtype() in the main class;
#. getName(), getInfo() and applyTransformation() in the abstract class
   it extends.

The getMIMEType(), getMIMESubtype() and getName() methods return the
name of the MIME type, MIME Subtype and transformation accordingly.
getInfo() returns the transformation's description and possible
options it may receive and applyTransformation() is the method that
does the actual work of the transformation plug-in.

Please see the :file:`libraries/classes/Plugins/Transformations/TEMPLATE` and
:file:`libraries/classes/Plugins/Transformations/TEMPLATE\_ABSTRACT` files for adding
your own transformation plug-in. You can also generate a new
transformation plug-in (with or without the abstract transformation
class), by using
:file:`scripts/transformations_generator_plugin.sh` or
:file:`scripts/transformations_generator_main_class.sh`.

The applyTransformation() method always gets passed three variables:

#. **$buffer** - Contains the text inside of the column. This is the
   text, you want to transform.
#. **$options** - Contains any user-passed options to a transform
   function as an array.
#. **$meta** - Contains an object with information about your column. The
   data is drawn from the output of the `mysql\_fetch\_field()
   <https://www.php.net/mysql_fetch_field>`_ function. This means, all
   object properties described on the `manual page
   <https://www.php.net/mysql_fetch_field>`_ are available in this
   variable and can be used to transform a column accordingly to
   unsigned/zerofill/not\_null/... properties. The $meta->mimetype
   variable contains the original :term:`Media type` of the column (i.e.
   'text/plain', 'image/jpeg' etc.)

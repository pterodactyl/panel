.. _bookmarks:

Bookmarks
=========

.. note::

    You need to have configured the :ref:`linked-tables` for using bookmarks
    feature.

Storing bookmarks
-----------------

Any query that is executed can be marked as a bookmark on the page
where the results are displayed. You will find a button labeled
:guilabel:`Bookmark this query` just at the end of the page. As soon as you have
stored a bookmark, that query is linked to the database.
You can now access a bookmark dropdown on each page where the query box appears on for that database.

Variables inside bookmarks
--------------------------

Inside a query, you can also add placeholders for variables.
This is done by inserting into the query SQL comments between ``/*`` and
``*/``. The special string ``[VARIABLE{variable-number}]`` is used inside the comments.
Be aware that the whole query minus the SQL comments must be
valid by itself, otherwise you won't be able to store it as a bookmark.
Also, note that the text 'VARIABLE' is case-sensitive.

When you execute the bookmark, everything typed into the *Variables*
input boxes on the query box page will replace the strings ``/*[VARIABLE{variable-number}]*/`` in
your stored query.

Also remember, that everything else inside the ``/*[VARIABLE{variable-number}]*/`` string for
your query will remain the way it is, but will be stripped of the ``/**/``
chars. So you can use:

.. code-block:: mysql

    /*, [VARIABLE1] AS myname */

which will be expanded to

.. code-block:: mysql

    , VARIABLE1 as myname

in your query, where VARIABLE1 is the string you entered in the Variable 1 input box.

A more complex example, say you have stored this query:

.. code-block:: mysql

    SELECT Name, Address FROM addresses WHERE 1 /* AND Name LIKE '%[VARIABLE1]%' */

If you wish to enter "phpMyAdmin" as the variable for the stored query, the full
query will be:

.. code-block:: mysql

    SELECT Name, Address FROM addresses WHERE 1 AND Name LIKE '%phpMyAdmin%'

**NOTE THE ABSENCE OF SPACES** inside the ``/**/`` construct. Any spaces
inserted there will be later also inserted as spaces in your query and may lead
to unexpected results especially when using the variable expansion inside of a
"LIKE ''" expression.

Browsing a table using a bookmark
---------------------------------

When a bookmark has the same name as the table, it will be used as the query when browsing
this table.

.. seealso::

    :ref:`faqbookmark`,
    :ref:`faq6_22`

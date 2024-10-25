User management
===============

User management is the process of controlling which users are allowed to
connect to the MySQL server and what permissions they have on each database.
phpMyAdmin does not handle user management, rather it passes the username and
password on to MySQL, which then determines whether a user is permitted to
perform a particular action. Within phpMyAdmin, administrators have full
control over creating users, viewing and editing privileges for existing users,
and removing users.

Within phpMyAdmin, user management is controlled via the :guilabel:`User accounts` tab
from the main page. Users can be created, edited, and removed.

Creating a new user
-------------------

To create a new user, click the :guilabel:`Add user account` link near the bottom
of the :guilabel:`User accounts` page (you must be a "superuser", e.g., user "root").
Use the textboxes and drop-downs to configure the user to your particular
needs. You can then select whether to create a database for that user and grant
specific global privileges. Once you've created the user (by clicking Go), you
can define that user's permissions on a specific database (don't grant global
privileges in that case). In general, users do not need any global privileges
(other than USAGE), only permissions for their specific database.

Editing an existing user
------------------------

To edit an existing user, simply click the pencil icon to the right of that
user in the :guilabel:`User accounts` page. You can then edit their global- and
database-specific privileges, change their password, or even copy those
privileges to a new user.

Deleting a user
---------------

From the :guilabel:`User accounts` page, check the checkbox for the user you wish to
remove, select whether or not to also remove any databases of the same name (if
they exist), and click Go.

Assigning privileges to user for a specific database
----------------------------------------------------

Users are assigned to databases by editing the user record (from the
:guilabel:`User accounts` link on the home page).
If you are creating a user specifically for a given table
you will have to create the user first (with no global privileges) and then go
back and edit that user to add the table and privileges for the individual
table.

.. _configurablemenus:

Configurable menus and user groups
----------------------------------

By enabling :config:option:`$cfg['Servers'][$i]['users']` and
:config:option:`$cfg['Servers'][$i]['usergroups']` you can customize what users
will see in the phpMyAdmin navigation.

.. warning::

    This feature only limits what a user sees, they are still able to use all the
    functions. So this can not be considered as a security limitation. Should
    you want to limit what users can do, use MySQL privileges to achieve that.

With this feature enabled, the :guilabel:`User accounts` management interface gains
a second tab for managing :guilabel:`User groups`, where you can define what each
group will view (see image below) and you can then assign each user to one of
these groups. Users will be presented with a simplified user interface, which might be
useful for inexperienced users who could be overwhelmed by all the features
phpMyAdmin provides.

.. image:: images/usergroups.png

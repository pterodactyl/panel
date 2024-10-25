Security policy
===============

The phpMyAdmin developer team is putting lot of effort to make phpMyAdmin as
secure as possible. But still web application like phpMyAdmin can be vulnerable
to a number of attacks and new ways to exploit are still being explored.

For every reported vulnerability we issue a phpMyAdmin Security Announcement
(PMASA) and it get's assigned a CVE ID as well. We might group similar
vulnerabilities to one PMASA (eg. multiple XSS vulnerabilities can be announced
under one PMASA).

If you think you've found a vulnerability, please see :ref:`reporting-security`.

Typical vulnerabilities
-----------------------

In this section, we will describe typical vulnerabilities, which can appear in
our code base. This list is by no means complete, it is intended to show
typical attack surface.

Cross-site scripting (XSS)
++++++++++++++++++++++++++

When phpMyAdmin shows a piece of user data, e.g. something inside a user's
database, all html special chars have to be escaped. When this escaping is
missing somewhere a malicious user might fill a database with specially crafted
content to trick an other user of that database into executing something. This
could for example be a piece of JavaScript code that would do any number of
nasty things.

phpMyAdmin tries to escape all userdata before it is rendered into html for the
browser.

.. seealso::

    `Cross-site scripting on Wikipedia <https://en.wikipedia.org/wiki/Cross-site_scripting>`_

Cross-site request forgery (CSRF)
+++++++++++++++++++++++++++++++++

An attacker would trick a phpMyAdmin user into clicking on a link to provoke
some action in phpMyAdmin. This link could either be sent via email or some
random website. If successful this the attacker would be able to perform some
action with the users privileges.

To mitigate this phpMyAdmin requires a token to be sent on sensitive requests.
The idea is that an attacker does not poses the currently valid token to
include in the presented link.

The token is regenerated for every login, so it's generally valid only for
limited time, what makes it harder for attacker to obtain valid one.

.. seealso::

    `Cross-site request forgery on Wikipedia <https://en.wikipedia.org/wiki/Cross-site_request_forgery>`_

SQL injection
+++++++++++++

As the whole purpose of phpMyAdmin is to preform sql queries, this is not our
first concern. SQL injection is sensitive to us though when it concerns the
mysql control connection. This controlconnection can have additional privileges
which the logged in user does not poses. E.g. access the :ref:`linked-tables`.

User data that is included in (administrative) queries should always be run
through DatabaseInterface::escapeString().

.. seealso::

    `SQL injection on Wikipedia <https://en.wikipedia.org/wiki/SQL_injection>`_

Brute force attack
++++++++++++++++++

phpMyAdmin on its own does not rate limit authentication attempts in any way.
This is caused by need to work in stateless environment, where there is no way
to protect against such kind of things.

To mitigate this, you can use Captcha or utilize external tools such as
fail2ban, this is more details described in :ref:`securing`.

.. seealso::

    `Brute force attack on Wikipedia <https://en.wikipedia.org/wiki/Brute-force_attack>`_

.. _reporting-security:

Reporting security issues
-------------------------

Should you find a security issue in the phpMyAdmin programming code, please
contact the `phpMyAdmin security team <mailto:security@phpmyadmin.net>`_ in
advance before publishing it. This way we can prepare a fix and release the fix together with your
announcement. You will be also given credit in our security announcement.
You can optionally encrypt your report with PGP key ID
``DA68AB39218AB947`` with following fingerprint:

.. code-block:: console

    pub   4096R/DA68AB39218AB947 2016-08-02
          Key fingerprint = 5BAD 38CF B980 50B9 4BD7  FB5B DA68 AB39 218A B947
    uid                          phpMyAdmin Security Team <security@phpmyadmin.net>
    sub   4096R/5E4176FB497A31F7 2016-08-02

The key can be either obtained from the keyserver or is available in
`phpMyAdmin keyring <https://files.phpmyadmin.net/phpmyadmin.keyring>`_
available on our download server or using `Keybase <https://keybase.io/phpmyadmin_sec>`_.

Should you have suggestion on improving phpMyAdmin to make it more secure, please
report that to our `issue tracker <https://github.com/phpmyadmin/phpmyadmin/issues>`_.
Existing improvement suggestions can be found by
`hardening label <https://github.com/phpmyadmin/phpmyadmin/labels/hardening>`_.

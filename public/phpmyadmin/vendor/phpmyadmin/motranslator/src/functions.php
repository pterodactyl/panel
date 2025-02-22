<?php

declare(strict_types=1);

/*
    Copyright (c) 2005 Steven Armstrong <sa at c-area dot ch>
    Copyright (c) 2009 Danilo Segan <danilo@kvota.net>
    Copyright (c) 2016 Michal Čihař <michal@cihar.com>

    This file is part of MoTranslator.

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License along
    with this program; if not, write to the Free Software Foundation, Inc.,
    51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
*/

use PhpMyAdmin\MoTranslator\Loader;

if (! function_exists('_setlocale')) {
    /**
     * Sets a requested locale.
     *
     * @param int    $category Locale category, ignored
     * @param string $locale   Locale name
     *
     * @return string Set or current locale
     */
    function _setlocale(int $category, string $locale): string
    {
        return Loader::getInstance()->setlocale($locale);
    }
}

if (! function_exists('_bindtextdomain')) {
    /**
     * Sets the path for a domain.
     *
     * @param string $domain Domain name
     * @param string $path   Path where to find locales
     */
    function _bindtextdomain(string $domain, string $path): void
    {
        Loader::getInstance()->bindtextdomain($domain, $path);
    }
}

if (! function_exists('_bind_textdomain_codeset')) {
    /**
     * Dummy compatibility function, MoTranslator assumes
     * everything is using same character set on input and
     * output.
     *
     * Generally it is wise to output in UTF-8 and have
     * mo files in UTF-8.
     *
     * @param string $domain  Domain where to set character set
     * @param string $codeset Character set to set
     */
    function _bind_textdomain_codeset($domain, $codeset): void
    {
    }
}

if (! function_exists('_textdomain')) {
    /**
     * Sets the default domain.
     *
     * @param string $domain Domain name
     */
    function _textdomain(string $domain): void
    {
        Loader::getInstance()->textdomain($domain);
    }
}

if (! function_exists('_gettext')) {
    /**
     * Translates a string.
     *
     * @param string $msgid String to be translated
     *
     * @return string translated string (or original, if not found)
     */
    function _gettext(string $msgid): string
    {
        return Loader::getInstance()->getTranslator()->gettext($msgid);
    }
}

if (! function_exists('__')) {
    /**
     * Translates a string, alias for _gettext.
     *
     * @param string $msgid String to be translated
     *
     * @return string translated string (or original, if not found)
     */
    function __(string $msgid): string
    {
        return Loader::getInstance()->getTranslator()->gettext($msgid);
    }
}

if (! function_exists('_ngettext')) {
    /**
     * Plural version of gettext.
     *
     * @param string $msgid       Single form
     * @param string $msgidPlural Plural form
     * @param int    $number      Number of objects
     *
     * @return string translated plural form
     */
    function _ngettext(string $msgid, string $msgidPlural, int $number): string
    {
        return Loader::getInstance()->getTranslator()->ngettext($msgid, $msgidPlural, $number);
    }
}

if (! function_exists('_pgettext')) {
    /**
     * Translate with context.
     *
     * @param string $msgctxt Context
     * @param string $msgid   String to be translated
     *
     * @return string translated plural form
     */
    function _pgettext(string $msgctxt, string $msgid): string
    {
        return Loader::getInstance()->getTranslator()->pgettext($msgctxt, $msgid);
    }
}

if (! function_exists('_npgettext')) {
    /**
     * Plural version of pgettext.
     *
     * @param string $msgctxt     Context
     * @param string $msgid       Single form
     * @param string $msgidPlural Plural form
     * @param int    $number      Number of objects
     *
     * @return string translated plural form
     */
    function _npgettext(string $msgctxt, string $msgid, string $msgidPlural, int $number): string
    {
        return Loader::getInstance()->getTranslator()->npgettext($msgctxt, $msgid, $msgidPlural, $number);
    }
}

if (! function_exists('_dgettext')) {
    /**
     * Translates a string.
     *
     * @param string $domain Domain to use
     * @param string $msgid  String to be translated
     *
     * @return string translated string (or original, if not found)
     */
    function _dgettext(string $domain, string $msgid): string
    {
        return Loader::getInstance()->getTranslator($domain)->gettext($msgid);
    }
}

if (! function_exists('_dngettext')) {
    /**
     * Plural version of gettext.
     *
     * @param string $domain      Domain to use
     * @param string $msgid       Single form
     * @param string $msgidPlural Plural form
     * @param int    $number      Number of objects
     *
     * @return string translated plural form
     */
    function _dngettext(string $domain, string $msgid, string $msgidPlural, int $number): string
    {
        return Loader::getInstance()->getTranslator($domain)->ngettext($msgid, $msgidPlural, $number);
    }
}

if (! function_exists('_dpgettext')) {
    /**
     * Translate with context.
     *
     * @param string $domain  Domain to use
     * @param string $msgctxt Context
     * @param string $msgid   String to be translated
     *
     * @return string translated plural form
     */
    function _dpgettext(string $domain, string $msgctxt, string $msgid): string
    {
        return Loader::getInstance()->getTranslator($domain)->pgettext($msgctxt, $msgid);
    }
}

if (! function_exists('_dnpgettext')) {
    /**
     * Plural version of pgettext.
     *
     * @param string $domain      Domain to use
     * @param string $msgctxt     Context
     * @param string $msgid       Single form
     * @param string $msgidPlural Plural form
     * @param int    $number      Number of objects
     *
     * @return string translated plural form
     */
    function _dnpgettext(string $domain, string $msgctxt, string $msgid, string $msgidPlural, int $number): string
    {
        return Loader::getInstance()->getTranslator($domain)->npgettext($msgctxt, $msgid, $msgidPlural, $number);
    }
}

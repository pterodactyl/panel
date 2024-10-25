<?php

declare(strict_types=1);

/*
    Copyright (c) 2003, 2005, 2006, 2009 Danilo Segan <danilo@kvota.net>.
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

namespace PhpMyAdmin\MoTranslator;

use function file_get_contents;
use function strlen;
use function substr;
use function unpack;

use const PHP_INT_MAX;

/**
 * Simple wrapper around string buffer for
 * random access and values parsing.
 */
class StringReader
{
    /** @var string */
    private $string;
    /** @var int */
    private $length;

    /**
     * @param string $filename Name of file to load
     */
    public function __construct(string $filename)
    {
        $this->string = (string) file_get_contents($filename);
        $this->length = strlen($this->string);
    }

    /**
     * Read number of bytes from given offset.
     *
     * @param int $pos   Offset
     * @param int $bytes Number of bytes to read
     */
    public function read(int $pos, int $bytes): string
    {
        if ($pos + $bytes > $this->length) {
            throw new ReaderException('Not enough bytes!');
        }

        $data = substr($this->string, $pos, $bytes);

        return $data === false ? '' : $data;
    }

    /**
     * Reads a 32bit integer from the stream.
     *
     * @param string $unpack Unpack string
     * @param int    $pos    Position
     *
     * @return int Integer from the stream
     */
    public function readint(string $unpack, int $pos): int
    {
        $data = unpack($unpack, $this->read($pos, 4));
        if ($data === false) {
            return PHP_INT_MAX;
        }

        $result = $data[1];

        /* We're reading unsigned int, but PHP will happily
         * give us negative number on 32-bit platforms.
         *
         * See also documentation:
         * https://secure.php.net/manual/en/function.unpack.php#refsect1-function.unpack-notes
         */
        return $result < 0 ? PHP_INT_MAX : $result;
    }

    /**
     * Reads an array of integers from the stream.
     *
     * @param string $unpack Unpack string
     * @param int    $pos    Position
     * @param int    $count  How many elements should be read
     *
     * @return int[] Array of Integers
     */
    public function readintarray(string $unpack, int $pos, int $count): array
    {
        $data = unpack($unpack . $count, $this->read($pos, 4 * $count));
        if ($data === false) {
            return [];
        }

        return $data;
    }
}

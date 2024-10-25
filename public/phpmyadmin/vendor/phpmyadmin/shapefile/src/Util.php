<?php

declare(strict_types=1);

/**
 * phpMyAdmin ShapeFile library
 * <https://github.com/phpmyadmin/shapefile/>.
 *
 * Copyright 2006-2007 Ovidio <ovidio AT users.sourceforge.net>
 * Copyright 2016 - 2017 Michal Čihař <michal@cihar.com>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, you can download one from
 * https://www.gnu.org/copyleft/gpl.html.
 */

namespace PhpMyAdmin\ShapeFile;

use function current;
use function pack;
use function sprintf;
use function strlen;
use function unpack;

class Util
{
    /** @var bool|null */
    private static $littleEndian = null;

    /** @var array */
    private static $shapeNames = [
        0 => 'Null Shape',
        1 => 'Point',
        3 => 'PolyLine',
        5 => 'Polygon',
        8 => 'MultiPoint',
        11 => 'PointZ',
        13 => 'PolyLineZ',
        15 => 'PolygonZ',
        18 => 'MultiPointZ',
        21 => 'PointM',
        23 => 'PolyLineM',
        25 => 'PolygonM',
        28 => 'MultiPointM',
        31 => 'MultiPatch',
    ];

    /**
     * Reads data.
     *
     * @param string       $type type for unpack()
     * @param string|false $data Data to process
     *
     * @return mixed|false
     */
    public static function loadData(string $type, $data)
    {
        if ($data === false) {
            return false;
        }

        if (strlen($data) === 0) {
            return false;
        }

        $tmp = unpack($type, $data);

        return $tmp === false ? $tmp : current($tmp);
    }

    /**
     * Changes endianity.
     *
     * @param string $binValue Binary value
     */
    public static function swap(string $binValue): string
    {
        $result = $binValue[strlen($binValue) - 1];
        for ($i = strlen($binValue) - 2; $i >= 0; --$i) {
            $result .= $binValue[$i];
        }

        return $result;
    }

    /**
     * Encodes double value to correct endianity.
     *
     * @param float $value Value to pack
     */
    public static function packDouble(float $value): string
    {
        $bin = pack('d', (float) $value);

        if (self::$littleEndian === null) {
            self::$littleEndian = (pack('L', 1) === pack('V', 1));
        }

        if (self::$littleEndian) {
            return $bin;
        }

        return self::swap($bin);
    }

    /**
     * Returns shape name.
     */
    public static function nameShape(int $type): string
    {
        if (isset(self::$shapeNames[$type])) {
            return self::$shapeNames[$type];
        }

        return sprintf('Shape %d', $type);
    }
}

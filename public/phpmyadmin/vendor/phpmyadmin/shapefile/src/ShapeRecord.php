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

use function array_values;
use function count;
use function fwrite;
use function in_array;
use function is_array;
use function pack;
use function sprintf;
use function strlen;

/**
 * ShapeFile record class.
 */
class ShapeRecord
{
    /** @var resource */
    private $shpFile = null;
    /** @var resource */
    private $dbfFile = null;
    /** @var ShapeFile */
    private $shapeFile = null;

    /** @var int */
    private $size = 0;
    /** @var int */
    private $read = 0;

    /** @var int|null */
    public $recordNumber = null;

    /** @var int */
    public $shapeType = null;

     /** @var string */
    public $lastError = '';

    /** @var array */
    public $shpData = [];
    /** @var array */
    public $dbfData = [];

    public function __construct(int $shapeType)
    {
        $this->shapeType = $shapeType;
    }

    /**
     * Loads record from files.
     *
     * @param ShapeFile $shapeFile The ShapeFile object
     * @param resource  $shpFile   Opened SHP file (by reference)
     * @param resource  $dbfFile   Opened DBF file (by reference)
     */
    public function loadFromFile(ShapeFile &$shapeFile, &$shpFile, &$dbfFile): void
    {
        $this->shapeFile = $shapeFile;
        $this->shpFile = $shpFile;
        $this->dbfFile = $dbfFile;
        $this->loadHeaders();

        /* No header read */
        if ($this->read === 0) {
            return;
        }

        switch ($this->shapeType) {
            case 0:
                $this->loadNullRecord();
                break;
            case 1:
                $this->loadPointRecord();
                break;
            case 21:
                $this->loadPointMRecord();
                break;
            case 11:
                $this->loadPointZRecord();
                break;
            case 3:
                $this->loadPolyLineRecord();
                break;
            case 23:
                $this->loadPolyLineMRecord();
                break;
            case 13:
                $this->loadPolyLineZRecord();
                break;
            case 5:
                $this->loadPolygonRecord();
                break;
            case 25:
                $this->loadPolygonMRecord();
                break;
            case 15:
                $this->loadPolygonZRecord();
                break;
            case 8:
                $this->loadMultiPointRecord();
                break;
            case 28:
                $this->loadMultiPointMRecord();
                break;
            case 18:
                $this->loadMultiPointZRecord();
                break;
            default:
                $this->setError(sprintf('The Shape Type "%s" is not supported.', $this->shapeType));
                break;
        }

        /* We need to skip rest of the record */
        while ($this->read < $this->size) {
            $this->loadData('V', 4);
        }

        /* Check if we didn't read too much */
        if ($this->read !== $this->size) {
            $this->setError(sprintf('Failed to parse record, read=%d, size=%d', $this->read, $this->size));
        }

        if (! ShapeFile::supportsDbase() || ! isset($this->dbfFile)) {
            return;
        }

        $this->loadDBFData();
    }

    /**
     * Saves record to files.
     *
     * @param resource $shpFile      Opened SHP file
     * @param resource $dbfFile      Opened DBF file
     * @param int      $recordNumber Record number
     */
    public function saveToFile(&$shpFile, &$dbfFile, int $recordNumber): void
    {
        $this->shpFile = $shpFile;
        $this->dbfFile = $dbfFile;
        $this->recordNumber = $recordNumber;
        $this->saveHeaders();

        switch ($this->shapeType) {
            case 0:
                // Nothing to save
                break;
            case 1:
                $this->savePointRecord();
                break;
            case 21:
                $this->savePointMRecord();
                break;
            case 11:
                $this->savePointZRecord();
                break;
            case 3:
                $this->savePolyLineRecord();
                break;
            case 23:
                $this->savePolyLineMRecord();
                break;
            case 13:
                $this->savePolyLineZRecord();
                break;
            case 5:
                $this->savePolygonRecord();
                break;
            case 25:
                $this->savePolygonMRecord();
                break;
            case 15:
                $this->savePolygonZRecord();
                break;
            case 8:
                $this->saveMultiPointRecord();
                break;
            case 28:
                $this->saveMultiPointMRecord();
                break;
            case 18:
                $this->saveMultiPointZRecord();
                break;
            default:
                $this->setError(sprintf('The Shape Type "%s" is not supported.', $this->shapeType));
                break;
        }

        if (! ShapeFile::supportsDbase() || $this->dbfFile === null) {
            return;
        }

        $this->saveDBFData();
    }

    /**
     * Updates DBF data to match header.
     *
     * @param array $header DBF structure header
     */
    public function updateDBFInfo(array $header): void
    {
        $tmp = $this->dbfData;
        unset($this->dbfData);
        $this->dbfData = [];
        foreach ($header as $value) {
            $this->dbfData[$value[0]] = $tmp[$value[0]] ?? '';
        }
    }

    /**
     * Reads data.
     *
     * @param string $type  type for unpack()
     * @param int    $count number of bytes
     *
     * @return mixed
     */
    private function loadData(string $type, int $count)
    {
        $data = $this->shapeFile->readSHP($count);
        if ($data === false) {
            return false;
        }

        $this->read += strlen($data);

        return Util::loadData($type, $data);
    }

    /**
     * Loads metadata header from a file.
     */
    private function loadHeaders(): void
    {
        $this->shapeType = false;
        $this->recordNumber = $this->loadData('N', 4);
        if ($this->recordNumber === false) {
            return;
        }

        // We read the length of the record
        $this->size = $this->loadData('N', 4);
        if ($this->size === false) {
            return;
        }

        $this->size = ($this->size * 2) + 8;
        $this->shapeType = $this->loadData('V', 4);
    }

    /**
     * Saves metadata header to a file.
     */
    private function saveHeaders(): void
    {
        fwrite($this->shpFile, pack('N', $this->recordNumber));
        fwrite($this->shpFile, pack('N', $this->getContentLength()));
        fwrite($this->shpFile, pack('V', $this->shapeType));
    }

    private function loadPoint(): array
    {
        $data = [];

        $data['x'] = $this->loadData('d', 8);
        $data['y'] = $this->loadData('d', 8);

        return $data;
    }

    private function loadPointM(): array
    {
        $data = $this->loadPoint();

        $data['m'] = $this->loadData('d', 8);

        return $data;
    }

    private function loadPointZ(): array
    {
        $data = $this->loadPoint();

        $data['z'] = $this->loadData('d', 8);
        $data['m'] = $this->loadData('d', 8);

        return $data;
    }

    private function savePoint(array $data): void
    {
        fwrite($this->shpFile, Util::packDouble($data['x']));
        fwrite($this->shpFile, Util::packDouble($data['y']));
    }

    private function savePointM(array $data): void
    {
        fwrite($this->shpFile, Util::packDouble($data['x']));
        fwrite($this->shpFile, Util::packDouble($data['y']));
        fwrite($this->shpFile, Util::packDouble($data['m']));
    }

    private function savePointZ(array $data): void
    {
        fwrite($this->shpFile, Util::packDouble($data['x']));
        fwrite($this->shpFile, Util::packDouble($data['y']));
        fwrite($this->shpFile, Util::packDouble($data['z']));
        fwrite($this->shpFile, Util::packDouble($data['m']));
    }

    private function loadNullRecord(): void
    {
        $this->shpData = [];
    }

    private function loadPointRecord(): void
    {
        $this->shpData = $this->loadPoint();
    }

    private function loadPointMRecord(): void
    {
        $this->shpData = $this->loadPointM();
    }

    private function loadPointZRecord(): void
    {
        $this->shpData = $this->loadPointZ();
    }

    private function savePointRecord(): void
    {
        $this->savePoint($this->shpData);
    }

    private function savePointMRecord(): void
    {
        $this->savePointM($this->shpData);
    }

    private function savePointZRecord(): void
    {
        $this->savePointZ($this->shpData);
    }

    private function loadBBox(): void
    {
        $this->shpData['xmin'] = $this->loadData('d', 8);
        $this->shpData['ymin'] = $this->loadData('d', 8);
        $this->shpData['xmax'] = $this->loadData('d', 8);
        $this->shpData['ymax'] = $this->loadData('d', 8);
    }

    private function loadMultiPointRecord(): void
    {
        $this->shpData = [];
        $this->loadBBox();

        $this->shpData['numpoints'] = $this->loadData('V', 4);

        for ($i = 0; $i < $this->shpData['numpoints']; ++$i) {
            $this->shpData['points'][] = $this->loadPoint();
        }
    }

    private function loadMultiPointMZRecord(string $type): void
    {
        /* The m dimension is optional, depends on bounding box data */
        if ($type === 'm' && ! $this->shapeFile->hasMeasure()) {
            return;
        }

        $this->shpData[$type . 'min'] = $this->loadData('d', 8);
        $this->shpData[$type . 'max'] = $this->loadData('d', 8);

        for ($i = 0; $i < $this->shpData['numpoints']; ++$i) {
            $this->shpData['points'][$i][$type] = $this->loadData('d', 8);
        }
    }

    private function loadMultiPointMRecord(): void
    {
        $this->loadMultiPointRecord();

        $this->loadMultiPointMZRecord('m');
    }

    private function loadMultiPointZRecord(): void
    {
        $this->loadMultiPointRecord();

        $this->loadMultiPointMZRecord('z');
        $this->loadMultiPointMZRecord('m');
    }

    private function saveMultiPointRecord(): void
    {
        fwrite($this->shpFile, pack(
            'dddd',
            $this->shpData['xmin'],
            $this->shpData['ymin'],
            $this->shpData['xmax'],
            $this->shpData['ymax']
        ));

        fwrite($this->shpFile, pack('V', $this->shpData['numpoints']));

        for ($i = 0; $i < $this->shpData['numpoints']; ++$i) {
            $this->savePoint($this->shpData['points'][$i]);
        }
    }

    private function saveMultiPointMZRecord(string $type): void
    {
        fwrite($this->shpFile, pack('dd', $this->shpData[$type . 'min'], $this->shpData[$type . 'max']));

        for ($i = 0; $i < $this->shpData['numpoints']; ++$i) {
            fwrite($this->shpFile, Util::packDouble($this->shpData['points'][$i][$type]));
        }
    }

    private function saveMultiPointMRecord(): void
    {
        $this->saveMultiPointRecord();

        $this->saveMultiPointMZRecord('m');
    }

    private function saveMultiPointZRecord(): void
    {
        $this->saveMultiPointRecord();

        $this->saveMultiPointMZRecord('z');
        $this->saveMultiPointMZRecord('m');
    }

    private function loadPolyLineRecord(): void
    {
        $this->shpData = [];
        $this->loadBBox();

        $this->shpData['numparts'] = $this->loadData('V', 4);
        $this->shpData['numpoints'] = $this->loadData('V', 4);

        $numparts = $this->shpData['numparts'];
        $numpoints = $this->shpData['numpoints'];

        for ($i = 0; $i < $numparts; ++$i) {
            $this->shpData['parts'][$i] = $this->loadData('V', 4);
        }

        $part = 0;
        for ($i = 0; $i < $numpoints; ++$i) {
            if ($part + 1 < $numparts && $i === $this->shpData['parts'][$part + 1]) {
                ++$part;
            }

            if (
                ! isset($this->shpData['parts'][$part]['points'])
                || ! is_array($this->shpData['parts'][$part]['points'])
            ) {
                $this->shpData['parts'][$part] = ['points' => []];
            }

            $this->shpData['parts'][$part]['points'][] = $this->loadPoint();
        }
    }

    private function loadPolyLineMZRecord(string $type): void
    {
        /* The m dimension is optional, depends on bounding box data */
        if ($type === 'm' && ! $this->shapeFile->hasMeasure()) {
            return;
        }

        $this->shpData[$type . 'min'] = $this->loadData('d', 8);
        $this->shpData[$type . 'max'] = $this->loadData('d', 8);

        $numparts = $this->shpData['numparts'];
        $numpoints = $this->shpData['numpoints'];

        $part = 0;
        for ($i = 0; $i < $numpoints; ++$i) {
            if ($part + 1 < $numparts && $i === $this->shpData['parts'][$part + 1]) {
                ++$part;
            }

            $this->shpData['parts'][$part]['points'][$i][$type] = $this->loadData('d', 8);
        }
    }

    private function loadPolyLineMRecord(): void
    {
        $this->loadPolyLineRecord();

        $this->loadPolyLineMZRecord('m');
    }

    private function loadPolyLineZRecord(): void
    {
        $this->loadPolyLineRecord();

        $this->loadPolyLineMZRecord('z');
        $this->loadPolyLineMZRecord('m');
    }

    private function savePolyLineRecord(): void
    {
        fwrite($this->shpFile, pack(
            'dddd',
            $this->shpData['xmin'],
            $this->shpData['ymin'],
            $this->shpData['xmax'],
            $this->shpData['ymax']
        ));

        fwrite($this->shpFile, pack('VV', $this->shpData['numparts'], $this->shpData['numpoints']));

        $partIndex = 0;
        for ($i = 0; $i < $this->shpData['numparts']; ++$i) {
            fwrite($this->shpFile, pack('V', $partIndex));
            $partIndex += count($this->shpData['parts'][$i]['points']);
        }

        foreach ($this->shpData['parts'] as $partData) {
            foreach ($partData['points'] as $pointData) {
                $this->savePoint($pointData);
            }
        }
    }

    private function savePolyLineMZRecord(string $type): void
    {
        fwrite($this->shpFile, pack('dd', $this->shpData[$type . 'min'], $this->shpData[$type . 'max']));

        foreach ($this->shpData['parts'] as $partData) {
            foreach ($partData['points'] as $pointData) {
                fwrite($this->shpFile, Util::packDouble($pointData[$type]));
            }
        }
    }

    private function savePolyLineMRecord(): void
    {
        $this->savePolyLineRecord();

        $this->savePolyLineMZRecord('m');
    }

    private function savePolyLineZRecord(): void
    {
        $this->savePolyLineRecord();

        $this->savePolyLineMZRecord('z');
        $this->savePolyLineMZRecord('m');
    }

    private function loadPolygonRecord(): void
    {
        $this->loadPolyLineRecord();
    }

    private function loadPolygonMRecord(): void
    {
        $this->loadPolyLineMRecord();
    }

    private function loadPolygonZRecord(): void
    {
        $this->loadPolyLineZRecord();
    }

    private function savePolygonRecord(): void
    {
        $this->savePolyLineRecord();
    }

    private function savePolygonMRecord(): void
    {
        $this->savePolyLineMRecord();
    }

    private function savePolygonZRecord(): void
    {
        $this->savePolyLineZRecord();
    }

    private function adjustBBox(array $point): void
    {
        // Adjusts bounding box based on point
        $directions = [
            'x',
            'y',
            'z',
            'm',
        ];
        foreach ($directions as $direction) {
            if (! isset($point[$direction])) {
                continue;
            }

            $min = $direction . 'min';
            $max = $direction . 'max';
            if (! isset($this->shpData[$min]) || ($this->shpData[$min] > $point[$direction])) {
                $this->shpData[$min] = $point[$direction];
            }

            if (isset($this->shpData[$max]) && ($this->shpData[$max] >= $point[$direction])) {
                continue;
            }

            $this->shpData[$max] = $point[$direction];
        }
    }

    /**
     * Sets dimension to 0 if not set.
     *
     * @param array  $point     Point to check
     * @param string $dimension Dimension to check
     *
     * @return array
     */
    private function fixPoint(array $point, string $dimension): array
    {
        if (! isset($point[$dimension])) {
            $point[$dimension] = 0.0; // no_value
        }

        return $point;
    }

    /**
     * Adjust point and bounding box when adding point.
     *
     * @param array $point Point data
     *
     * @return array Fixed point data
     */
    private function adjustPoint(array $point): array
    {
        $type = $this->shapeType / 10;
        if ($type >= 2) {
            $point = $this->fixPoint($point, 'm');
        } elseif ($type >= 1) {
            $point = $this->fixPoint($point, 'z');
            $point = $this->fixPoint($point, 'm');
        }

        return $point;
    }

    /**
     * Adds point to a record.
     *
     * @param array $point     Point data
     * @param int   $partIndex Part index
     */
    public function addPoint(array $point, int $partIndex = 0): void
    {
        $point = $this->adjustPoint($point);
        switch ($this->shapeType) {
            case 0:
                //Don't add anything
                return;

            case 1:
            case 11:
            case 21:
                //Substitutes the value of the current point
                $this->shpData = $point;
                break;
            case 3:
            case 5:
            case 13:
            case 15:
            case 23:
            case 25:
                //Adds a new point to the selected part
                $this->shpData['parts'][$partIndex]['points'][] = $point;
                $this->shpData['numparts'] = count($this->shpData['parts']);
                $this->shpData['numpoints'] = 1 + ($this->shpData['numpoints'] ?? 0);
                break;
            case 8:
            case 18:
            case 28:
                //Adds a new point
                $this->shpData['points'][] = $point;
                $this->shpData['numpoints'] = 1 + ($this->shpData['numpoints'] ?? 0);
                break;
            default:
                $this->setError(sprintf('The Shape Type "%s" is not supported.', $this->shapeType));

                return;
        }

        $this->adjustBBox($point);
    }

    /**
     * Deletes point from a record.
     *
     * @param int $pointIndex Point index
     * @param int $partIndex  Part index
     */
    public function deletePoint(int $pointIndex = 0, int $partIndex = 0): void
    {
        switch ($this->shapeType) {
            case 0:
                //Don't delete anything
                break;
            case 1:
            case 11:
            case 21:
                //Sets the value of the point to zero
                $this->shpData['x'] = 0.0;
                $this->shpData['y'] = 0.0;
                if (in_array($this->shapeType, [11, 21])) {
                    $this->shpData['m'] = 0.0;
                }

                if (in_array($this->shapeType, [11])) {
                    $this->shpData['z'] = 0.0;
                }

                break;
            case 3:
            case 5:
            case 13:
            case 15:
            case 23:
            case 25:
                //Deletes the point from the selected part, if exists
                if (
                    isset($this->shpData['parts'][$partIndex])
                    && isset($this->shpData['parts'][$partIndex]['points'][$pointIndex])
                ) {
                    $count = count($this->shpData['parts'][$partIndex]['points']) - 1;
                    for ($i = $pointIndex; $i < $count; ++$i) {
                        $point = $this->shpData['parts'][$partIndex]['points'][$i + 1];
                        $this->shpData['parts'][$partIndex]['points'][$i] = $point;
                    }

                    $count = count($this->shpData['parts'][$partIndex]['points']) - 1;
                    unset($this->shpData['parts'][$partIndex]['points'][$count]);

                    $this->shpData['numparts'] = count($this->shpData['parts']);
                    --$this->shpData['numpoints'];
                }

                break;
            case 8:
            case 18:
            case 28:
                //Deletes the point, if exists
                if (isset($this->shpData['points'][$pointIndex])) {
                    $count = count($this->shpData['points']) - 1;
                    for ($i = $pointIndex; $i < $count; ++$i) {
                        $this->shpData['points'][$i] = $this->shpData['points'][$i + 1];
                    }

                    unset($this->shpData['points'][count($this->shpData['points']) - 1]);

                    --$this->shpData['numpoints'];
                }

                break;
            default:
                $this->setError(sprintf('The Shape Type "%s" is not supported.', $this->shapeType));
                break;
        }
    }

    /**
     * Returns length of content.
     */
    public function getContentLength(): ?int
    {
        // The content length for a record is the length of the record contents section measured in 16-bit words.
        // one coordinate makes 4 16-bit words (64 bit double)
        switch ($this->shapeType) {
            case 0:
                $result = 0;
                break;
            case 1:
                $result = 10;
                break;
            case 21:
                $result = 10 + 4;
                break;
            case 11:
                $result = 10 + 8;
                break;
            case 3:
            case 5:
                $count = count($this->shpData['parts']);
                $result = 22 + 2 * $count;
                for ($i = 0; $i < $count; ++$i) {
                    $result += 8 * count($this->shpData['parts'][$i]['points']);
                }

                break;
            case 23:
            case 25:
                $count = count($this->shpData['parts']);
                $result = 22 + (2 * 4) + 2 * $count;
                for ($i = 0; $i < $count; ++$i) {
                    $result += (8 + 4) * count($this->shpData['parts'][$i]['points']);
                }

                break;
            case 13:
            case 15:
                $count = count($this->shpData['parts']);
                $result = 22 + (4 * 4) + 2 * $count;
                for ($i = 0; $i < $count; ++$i) {
                    $result += (8 + 8) * count($this->shpData['parts'][$i]['points']);
                }

                break;
            case 8:
                $result = 20 + 8 * count($this->shpData['points']);
                break;
            case 28:
                $result = 20 + (2 * 4) + (8 + 4) * count($this->shpData['points']);
                break;
            case 18:
                $result = 20 + (4 * 4) + (8 + 8) * count($this->shpData['points']);
                break;
            default:
                $result = null;
                $this->setError(sprintf('The Shape Type "%s" is not supported.', $this->shapeType));
                break;
        }

        return $result;
    }

    private function loadDBFData(): void
    {
        $this->dbfData = @dbase_get_record_with_names($this->dbfFile, $this->recordNumber);
        unset($this->dbfData['deleted']);
    }

    private function saveDBFData(): void
    {
        if (count($this->dbfData) === 0) {
            return;
        }

        unset($this->dbfData['deleted']);
        if ($this->recordNumber <= dbase_numrecords($this->dbfFile)) {
            if (! dbase_replace_record($this->dbfFile, array_values($this->dbfData), $this->recordNumber)) {
                $this->setError('I wasn\'t possible to update the information in the DBF file.');
            }
        } else {
            if (! dbase_add_record($this->dbfFile, array_values($this->dbfData))) {
                $this->setError('I wasn\'t possible to add the information to the DBF file.');
            }
        }
    }

    /**
     * Sets error message.
     */
    public function setError(string $error): void
    {
        $this->lastError = $error;
    }

    /**
     * Returns shape name.
     */
    public function getShapeName(): string
    {
        return Util::nameShape($this->shapeType);
    }
}

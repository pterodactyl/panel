<?php
/**
 * Interface for the zip extension
 */

declare(strict_types=1);

namespace PhpMyAdmin;

use ZipArchive;

use function __;
use function array_combine;
use function count;
use function crc32;
use function getdate;
use function gzcompress;
use function implode;
use function is_array;
use function is_string;
use function pack;
use function preg_match;
use function sprintf;
use function str_replace;
use function strcmp;
use function strlen;
use function strpos;
use function substr;

/**
 * Transformations class
 */
class ZipExtension
{
    /** @var ZipArchive|null */
    private $zip;

    public function __construct(?ZipArchive $zip = null)
    {
        $this->zip = $zip;
    }

    /**
     * Gets zip file contents
     *
     * @param string $file          path to zip file
     * @param string $specificEntry regular expression to match a file
     *
     * @return array ($error_message, $file_data); $error_message
     *                  is empty if no error
     */
    public function getContents($file, $specificEntry = null)
    {
        /**
        * This function is used to "import" a SQL file which has been exported earlier
        * That means that this function works on the assumption that the zip file contains only a single SQL file
        * It might also be an ODS file, look below
        */

        if ($this->zip === null) {
            return [
                'error' => sprintf(__('The %s extension is missing. Please check your PHP configuration.'), 'zip'),
                'data' => '',
            ];
        }

        $errorMessage = '';
        $fileData = '';

        $res = $this->zip->open($file);

        if ($res !== true) {
            $errorMessage = __('Error in ZIP archive:') . ' ' . $this->zip->getStatusString();
            $this->zip->close();

            return [
                'error' => $errorMessage,
                'data' => $fileData,
            ];
        }

        if ($this->zip->numFiles === 0) {
            $errorMessage = __('No files found inside ZIP archive!');
            $this->zip->close();

            return [
                'error' => $errorMessage,
                'data' => $fileData,
            ];
        }

        /* Is the the zip really an ODS file? */
        $odsMediaType = 'application/vnd.oasis.opendocument.spreadsheet';
        $firstZipEntry = $this->zip->getFromIndex(0);
        if (! strcmp($odsMediaType, (string) $firstZipEntry)) {
            $specificEntry = '/^content\.xml$/';
        }

        if (! isset($specificEntry)) {
            $fileData = $firstZipEntry;
            $this->zip->close();

            return [
                'error' => $errorMessage,
                'data' => $fileData,
            ];
        }

        /* Return the correct contents, not just the first entry */
        for ($i = 0; $i < $this->zip->numFiles; $i++) {
            if (preg_match($specificEntry, (string) $this->zip->getNameIndex($i))) {
                $fileData = $this->zip->getFromIndex($i);
                break;
            }
        }

        /* Couldn't find any files that matched $specific_entry */
        if (empty($fileData)) {
            $errorMessage = __('Error in ZIP archive:')
                . ' Could not find "' . $specificEntry . '"';
        }

        $this->zip->close();

        return [
            'error' => $errorMessage,
            'data' => $fileData,
        ];
    }

    /**
     * Returns the filename of the first file that matches the given $file_regexp.
     *
     * @param string $file  path to zip file
     * @param string $regex regular expression for the file name to match
     *
     * @return string|false the file name of the first file that matches the given regular expression
     */
    public function findFile($file, $regex)
    {
        if ($this->zip === null) {
            return false;
        }

        $res = $this->zip->open($file);

        if ($res === true) {
            for ($i = 0; $i < $this->zip->numFiles; $i++) {
                if (preg_match($regex, (string) $this->zip->getNameIndex($i))) {
                    $filename = $this->zip->getNameIndex($i);
                    $this->zip->close();

                    return $filename;
                }
            }
        }

        return false;
    }

    /**
     * Returns the number of files in the zip archive.
     *
     * @param string $file path to zip file
     *
     * @return int the number of files in the zip archive or 0, either if there weren't any files or an error occurred.
     */
    public function getNumberOfFiles($file)
    {
        if ($this->zip === null) {
            return 0;
        }

        $num = 0;
        $res = $this->zip->open($file);

        if ($res === true) {
            $num = $this->zip->numFiles;
        }

        return $num;
    }

    /**
     * Extracts the content of $entry.
     *
     * @param string $file  path to zip file
     * @param string $entry file in the archive that should be extracted
     *
     * @return string|false data on success, false otherwise
     */
    public function extract($file, $entry)
    {
        if ($this->zip === null) {
            return false;
        }

        if ($this->zip->open($file) === true) {
            $result = $this->zip->getFromName($entry);
            $this->zip->close();

            return $result;
        }

        return false;
    }

    /**
     * Creates a zip file.
     * If $data is an array and $name is a string, the filenames will be indexed.
     * The function will return false if $data is a string but $name is an array
     * or if $data is an array and $name is an array, but they don't have the
     * same amount of elements.
     *
     * @param array|string $data contents of the file/files
     * @param array|string $name name of the file/files in the archive
     * @param int          $time the current timestamp
     *
     * @return string|bool the ZIP file contents, or false if there was an error.
     */
    public function createFile($data, $name, $time = 0)
    {
        $datasec = []; // Array to store compressed data
        $ctrlDir = []; // Central directory
        $oldOffset = 0; // Last offset position
        $eofCtrlDir = "\x50\x4b\x05\x06\x00\x00\x00\x00"; // End of central directory record

        if (is_string($data) && is_string($name)) {
            $data = [$name => $data];
        } elseif (is_array($data) && is_string($name)) {
            $extPos = (int) strpos($name, '.');
            $extension = substr($name, $extPos);
            $newData = [];
            foreach ($data as $key => $value) {
                $newName = str_replace($extension, '_' . $key . $extension, $name);
                $newData[$newName] = $value;
            }

            $data = $newData;
        } elseif (is_array($data) && is_array($name) && count($data) === count($name)) {
            /** @var array $data */
            $data = array_combine($name, $data);
        } else {
            return false;
        }

        foreach ($data as $table => $dump) {
            $tempName = str_replace('\\', '/', $table);

            /* Get Local Time */
            $timearray = getdate();

            if ($timearray['year'] < 1980) {
                $timearray['year'] = 1980;
                $timearray['mon'] = 1;
                $timearray['mday'] = 1;
                $timearray['hours'] = 0;
                $timearray['minutes'] = 0;
                $timearray['seconds'] = 0;
            }

            $time = $timearray['year'] - 1980 << 25
            | ($timearray['mon'] << 21)
            | ($timearray['mday'] << 16)
            | ($timearray['hours'] << 11)
            | ($timearray['minutes'] << 5)
            | ($timearray['seconds'] >> 1);

            $hexdtime = pack('V', $time);

            $uncLen = strlen($dump);
            $crc = crc32($dump);
            $zdata = (string) gzcompress($dump);
            $zdata = substr((string) substr($zdata, 0, strlen($zdata) - 4), 2); // fix crc bug
            $cLen = strlen($zdata);
            $fr = "\x50\x4b\x03\x04"
                . "\x14\x00" // ver needed to extract
                . "\x00\x00" // gen purpose bit flag
                . "\x08\x00" // compression method
                . $hexdtime // last mod time and date

                // "local file header" segment
                . pack('V', $crc) // crc32
                . pack('V', $cLen) // compressed filesize
                . pack('V', $uncLen) // uncompressed filesize
                . pack('v', strlen($tempName)) // length of filename
                . pack('v', 0) // extra field length
                . $tempName

                // "file data" segment
                . $zdata;

            $datasec[] = $fr;

            // now add to central directory record
            $cdrec = "\x50\x4b\x01\x02"
                . "\x00\x00" // version made by
                . "\x14\x00" // version needed to extract
                . "\x00\x00" // gen purpose bit flag
                . "\x08\x00" // compression method
                . $hexdtime // last mod time & date
                . pack('V', $crc) // crc32
                . pack('V', $cLen) // compressed filesize
                . pack('V', $uncLen) // uncompressed filesize
                . pack('v', strlen($tempName)) // length of filename
                . pack('v', 0) // extra field length
                . pack('v', 0) // file comment length
                . pack('v', 0) // disk number start
                . pack('v', 0) // internal file attributes
                . pack('V', 32) // external file attributes
                                                 // - 'archive' bit set
                . pack('V', $oldOffset) // relative offset of local header
                . $tempName; // filename
            $oldOffset += strlen($fr);
            // optional extra field, file comment goes here
            // save to central directory
            $ctrlDir[] = $cdrec;
        }

        /* Build string to return */
        $tempCtrlDir = implode('', $ctrlDir);
        $header = $tempCtrlDir .
            $eofCtrlDir .
            pack('v', count($ctrlDir)) . //total #of entries "on this disk"
            pack('v', count($ctrlDir)) . //total #of entries overall
            pack('V', strlen($tempCtrlDir)) . //size of central dir
            pack('V', $oldOffset) . //offset to start of central dir
            "\x00\x00"; //.zip file comment length

        $data = implode('', $datasec);

        return $data . $header;
    }
}

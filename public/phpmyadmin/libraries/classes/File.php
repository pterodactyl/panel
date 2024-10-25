<?php

declare(strict_types=1);

namespace PhpMyAdmin;

use ZipArchive;

use function __;
use function basename;
use function bin2hex;
use function bzopen;
use function bzread;
use function extension_loaded;
use function fclose;
use function feof;
use function file_get_contents;
use function fopen;
use function fread;
use function function_exists;
use function gzopen;
use function gzread;
use function is_link;
use function is_readable;
use function is_string;
use function is_uploaded_file;
use function mb_strcut;
use function move_uploaded_file;
use function ob_end_clean;
use function ob_start;
use function sprintf;
use function strlen;
use function tempnam;
use function trim;
use function unlink;

use const UPLOAD_ERR_CANT_WRITE;
use const UPLOAD_ERR_EXTENSION;
use const UPLOAD_ERR_FORM_SIZE;
use const UPLOAD_ERR_INI_SIZE;
use const UPLOAD_ERR_NO_FILE;
use const UPLOAD_ERR_NO_TMP_DIR;
use const UPLOAD_ERR_OK;
use const UPLOAD_ERR_PARTIAL;

/**
 * File wrapper class
 *
 * @todo when uploading a file into a blob field, should we also consider using
 *       chunks like in import? UPDATE `table` SET `field` = `field` + [chunk]
 */
class File
{
    /** @var string the temporary file name */
    protected $name = null;

    /** @var string the content */
    protected $content = null;

    /** @var Message|null the error message */
    protected $errorMessage = null;

    /** @var bool whether the file is temporary or not */
    protected $isTemp = false;

    /** @var string type of compression */
    protected $compression = null;

    /** @var int */
    protected $offset = 0;

    /** @var int size of chunk to read with every step */
    protected $chunkSize = 32768;

    /** @var resource|null file handle */
    protected $handle = null;

    /** @var bool whether to decompress content before returning */
    protected $decompress = false;

    /** @var string charset of file */
    protected $charset = null;

    /** @var ZipExtension */
    private $zipExtension;

    /**
     * @param bool|string $name file name or false
     */
    public function __construct($name = false)
    {
        if ($name && is_string($name)) {
            $this->setName($name);
        }

        if (! extension_loaded('zip')) {
            return;
        }

        $this->zipExtension = new ZipExtension(new ZipArchive());
    }

    /**
     * destructor
     *
     * @see     File::cleanUp()
     */
    public function __destruct()
    {
        $this->cleanUp();
    }

    /**
     * deletes file if it is temporary, usually from a moved upload file
     */
    public function cleanUp(): bool
    {
        if ($this->isTemp()) {
            return $this->delete();
        }

        return true;
    }

    /**
     * deletes the file
     */
    public function delete(): bool
    {
        return unlink((string) $this->getName());
    }

    /**
     * checks or sets the temp flag for this file
     * file objects with temp flags are deleted with object destruction
     *
     * @param bool $is_temp sets the temp flag
     */
    public function isTemp(?bool $is_temp = null): bool
    {
        if ($is_temp !== null) {
            $this->isTemp = $is_temp;
        }

        return $this->isTemp;
    }

    /**
     * accessor
     *
     * @param string|null $name file name
     */
    public function setName(?string $name): void
    {
        $this->name = trim((string) $name);
    }

    /**
     * Gets file content
     *
     * @return string|false the binary file content, or false if no content
     */
    public function getRawContent()
    {
        if ($this->content !== null) {
            return $this->content;
        }

        if ($this->isUploaded() && ! $this->checkUploadedFile()) {
            return false;
        }

        if (! $this->isReadable()) {
            return false;
        }

        $this->content = file_get_contents((string) $this->getName());

        return $this->content;
    }

    /**
     * Gets file content
     *
     * @return string|false the binary file content as a string,
     *                      or false if no content
     */
    public function getContent()
    {
        $result = $this->getRawContent();
        if ($result === false) {
            return false;
        }

        return '0x' . bin2hex($result);
    }

    /**
     * Whether file is uploaded.
     */
    public function isUploaded(): bool
    {
        if ($this->getName() === null) {
            return false;
        }

        return is_uploaded_file($this->getName());
    }

    /**
     * accessor
     *
     * @return string|null File::$_name
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Initializes object from uploaded file.
     *
     * @param string $name name of file uploaded
     */
    public function setUploadedFile(string $name): bool
    {
        $this->setName($name);

        if (! $this->isUploaded()) {
            $this->setName(null);
            $this->errorMessage = Message::error(__('File was not an uploaded file.'));

            return false;
        }

        return true;
    }

    /**
     * Loads uploaded file from table change request.
     *
     * @param string $key       the md5 hash of the column name
     * @param string $rownumber number of row to process
     */
    public function setUploadedFromTblChangeRequest(
        string $key,
        string $rownumber
    ): bool {
        if (
            ! isset($_FILES['fields_upload'])
            || empty($_FILES['fields_upload']['name']['multi_edit'][$rownumber][$key])
        ) {
            return false;
        }

        $file = $this->fetchUploadedFromTblChangeRequestMultiple($_FILES['fields_upload'], $rownumber, $key);

        switch ($file['error']) {
            case UPLOAD_ERR_OK:
                return $this->setUploadedFile($file['tmp_name']);

            case UPLOAD_ERR_NO_FILE:
                break;
            case UPLOAD_ERR_INI_SIZE:
                $this->errorMessage = Message::error(__(
                    'The uploaded file exceeds the upload_max_filesize directive in php.ini.'
                ));
                break;
            case UPLOAD_ERR_FORM_SIZE:
                $this->errorMessage = Message::error(__(
                    'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.'
                ));
                break;
            case UPLOAD_ERR_PARTIAL:
                $this->errorMessage = Message::error(__(
                    'The uploaded file was only partially uploaded.'
                ));
                break;
            case UPLOAD_ERR_NO_TMP_DIR:
                $this->errorMessage = Message::error(__('Missing a temporary folder.'));
                break;
            case UPLOAD_ERR_CANT_WRITE:
                $this->errorMessage = Message::error(__('Failed to write file to disk.'));
                break;
            case UPLOAD_ERR_EXTENSION:
                $this->errorMessage = Message::error(__('File upload stopped by extension.'));
                break;
            default:
                $this->errorMessage = Message::error(__('Unknown error in file upload.'));
        }

        return false;
    }

    /**
     * strips some dimension from the multi-dimensional array from $_FILES
     *
     * <code>
     * $file['name']['multi_edit'][$rownumber][$key] = [value]
     * $file['type']['multi_edit'][$rownumber][$key] = [value]
     * $file['size']['multi_edit'][$rownumber][$key] = [value]
     * $file['tmp_name']['multi_edit'][$rownumber][$key] = [value]
     * $file['error']['multi_edit'][$rownumber][$key] = [value]
     *
     * // becomes:
     *
     * $file['name'] = [value]
     * $file['type'] = [value]
     * $file['size'] = [value]
     * $file['tmp_name'] = [value]
     * $file['error'] = [value]
     * </code>
     *
     * @param array  $file      the array
     * @param string $rownumber number of row to process
     * @param string $key       key to process
     *
     * @return array
     *
     * @static
     */
    public function fetchUploadedFromTblChangeRequestMultiple(
        array $file,
        string $rownumber,
        string $key
    ): array {
        return [
            'name' => $file['name']['multi_edit'][$rownumber][$key],
            'type' => $file['type']['multi_edit'][$rownumber][$key],
            'size' => $file['size']['multi_edit'][$rownumber][$key],
            'tmp_name' => $file['tmp_name']['multi_edit'][$rownumber][$key],
            'error' => $file['error']['multi_edit'][$rownumber][$key],
        ];
    }

    /**
     * sets the name if the file to the one selected in the tbl_change form
     *
     * @param string $key       the md5 hash of the column name
     * @param string $rownumber number of row to process
     */
    public function setSelectedFromTblChangeRequest(
        string $key,
        ?string $rownumber = null
    ): bool {
        if (
            ! empty($_REQUEST['fields_uploadlocal']['multi_edit'][$rownumber][$key])
            && is_string($_REQUEST['fields_uploadlocal']['multi_edit'][$rownumber][$key])
        ) {
            // ... whether with multiple rows ...
            return $this->setLocalSelectedFile($_REQUEST['fields_uploadlocal']['multi_edit'][$rownumber][$key]);
        }

        return false;
    }

    /**
     * Returns possible error message.
     *
     * @return Message|null error message
     */
    public function getError(): ?Message
    {
        return $this->errorMessage;
    }

    /**
     * Checks whether there was any error.
     */
    public function isError(): bool
    {
        return $this->errorMessage !== null;
    }

    /**
     * checks the superglobals provided if the tbl_change form is submitted
     * and uses the submitted/selected file
     *
     * @param string $key       the md5 hash of the column name
     * @param string $rownumber number of row to process
     */
    public function checkTblChangeForm(string $key, string $rownumber): bool
    {
        if ($this->setUploadedFromTblChangeRequest($key, $rownumber)) {
            // well done ...
            $this->errorMessage = null;

            return true;
        }

        if ($this->setSelectedFromTblChangeRequest($key, $rownumber)) {
            // well done ...
            $this->errorMessage = null;

            return true;
        }

        // all failed, whether just no file uploaded/selected or an error

        return false;
    }

    /**
     * Sets named file to be read from UploadDir.
     *
     * @param string $name file name
     */
    public function setLocalSelectedFile(string $name): bool
    {
        if (empty($GLOBALS['cfg']['UploadDir'])) {
            return false;
        }

        if (! is_string($GLOBALS['cfg']['UploadDir'])) {
            return false;
        }

        $this->setName(
            Util::userDir($GLOBALS['cfg']['UploadDir']) . Core::securePath($name)
        );
        if (@is_link((string) $this->getName())) {
            $this->errorMessage = Message::error(__('File is a symbolic link'));
            $this->setName(null);

            return false;
        }

        if (! $this->isReadable()) {
            $this->errorMessage = Message::error(__('File could not be read!'));
            $this->setName(null);

            return false;
        }

        return true;
    }

    /**
     * Checks whether file can be read.
     */
    public function isReadable(): bool
    {
        // suppress warnings from being displayed, but not from being logged
        // any file access outside of open_basedir will issue a warning
        return @is_readable((string) $this->getName());
    }

    /**
     * If we are on a server with open_basedir, we must move the file
     * before opening it. The FAQ 1.11 explains how to create the "./tmp"
     * directory - if needed
     *
     * @todo move check of $cfg['TempDir'] into Config?
     */
    public function checkUploadedFile(): bool
    {
        if ($this->isReadable()) {
            return true;
        }

        $tmp_subdir = $GLOBALS['config']->getUploadTempDir();
        if ($tmp_subdir === null) {
            // cannot create directory or access, point user to FAQ 1.11
            $this->errorMessage = Message::error(__(
                'Error moving the uploaded file, see [doc@faq1-11]FAQ 1.11[/doc].'
            ));

            return false;
        }

        $new_file_to_upload = (string) tempnam(
            $tmp_subdir,
            basename((string) $this->getName())
        );

        // suppress warnings from being displayed, but not from being logged
        // any file access outside of open_basedir will issue a warning
        ob_start();
        $move_uploaded_file_result = move_uploaded_file(
            (string) $this->getName(),
            $new_file_to_upload
        );
        ob_end_clean();
        if (! $move_uploaded_file_result) {
            $this->errorMessage = Message::error(__('Error while moving uploaded file.'));

            return false;
        }

        $this->setName($new_file_to_upload);
        $this->isTemp(true);

        if (! $this->isReadable()) {
            $this->errorMessage = Message::error(__('Cannot read uploaded file.'));

            return false;
        }

        return true;
    }

    /**
     * Detects what compression the file uses
     *
     * @return string|false false on error, otherwise string MIME type of
     *                      compression, none for none
     *
     * @todo   move file read part into readChunk() or getChunk()
     * @todo   add support for compression plugins
     */
    protected function detectCompression()
    {
        // suppress warnings from being displayed, but not from being logged
        // f.e. any file access outside of open_basedir will issue a warning
        ob_start();
        $file = fopen((string) $this->getName(), 'rb');
        ob_end_clean();

        if (! $file) {
            $this->errorMessage = Message::error(__('File could not be read!'));

            return false;
        }

        $this->compression = Util::getCompressionMimeType($file);

        return $this->compression;
    }

    /**
     * Sets whether the content should be decompressed before returned
     *
     * @param bool $decompress whether to decompress
     */
    public function setDecompressContent(bool $decompress): void
    {
        $this->decompress = $decompress;
    }

    /**
     * Returns the file handle
     *
     * @return resource|null file handle
     */
    public function getHandle()
    {
        if ($this->handle === null) {
            $this->open();
        }

        return $this->handle;
    }

    /**
     * Sets the file handle
     *
     * @param resource $handle file handle
     */
    public function setHandle($handle): void
    {
        $this->handle = $handle;
    }

    /**
     * Sets error message for unsupported compression.
     */
    public function errorUnsupported(): void
    {
        $this->errorMessage = Message::error(sprintf(
            __(
                'You attempted to load file with unsupported compression (%s). '
                . 'Either support for it is not implemented or disabled by your '
                . 'configuration.'
            ),
            $this->getCompression()
        ));
    }

    /**
     * Attempts to open the file.
     */
    public function open(): bool
    {
        if (! $this->decompress) {
            $this->handle = @fopen((string) $this->getName(), 'r');
        }

        switch ($this->getCompression()) {
            case false:
                return false;

            case 'application/bzip2':
                if (! $GLOBALS['cfg']['BZipDump'] || ! function_exists('bzopen')) {
                    $this->errorUnsupported();

                    return false;
                }

                $this->handle = @bzopen($this->getName(), 'r');
                break;
            case 'application/gzip':
                if (! $GLOBALS['cfg']['GZipDump'] || ! function_exists('gzopen')) {
                    $this->errorUnsupported();

                    return false;
                }

                $this->handle = @gzopen((string) $this->getName(), 'r');
                break;
            case 'application/zip':
                if ($GLOBALS['cfg']['ZipDump'] && function_exists('zip_open')) {
                    return $this->openZip();
                }

                $this->errorUnsupported();

                return false;

            case 'none':
                $this->handle = @fopen((string) $this->getName(), 'r');
                break;
            default:
                $this->errorUnsupported();

                return false;
        }

        return $this->handle !== false;
    }

    /**
     * Opens file from zip
     *
     * @param string|null $specific_entry Entry to open
     */
    public function openZip(?string $specific_entry = null): bool
    {
        $result = $this->zipExtension->getContents($this->getName(), $specific_entry);
        if (! empty($result['error'])) {
            $this->errorMessage = Message::rawError($result['error']);

            return false;
        }

        $this->content = $result['data'];
        $this->offset = 0;

        return true;
    }

    /**
     * Checks whether we've reached end of file
     */
    public function eof(): bool
    {
        if ($this->handle !== null) {
            return feof($this->handle);
        }

        return $this->offset == strlen($this->content);
    }

    /**
     * Closes the file
     */
    public function close(): void
    {
        if ($this->handle !== null) {
            fclose($this->handle);
            $this->handle = null;
        } else {
            $this->content = '';
            $this->offset = 0;
        }

        $this->cleanUp();
    }

    /**
     * Reads data from file
     *
     * @param int $size Number of bytes to read
     */
    public function read(int $size): string
    {
        if ($this->compression === 'application/zip') {
            $result = mb_strcut($this->content, $this->offset, $size);
            $this->offset += strlen($result);

            return $result;
        }

        if ($this->handle === null) {
            return '';
        }

        if ($this->compression === 'application/bzip2') {
            return (string) bzread($this->handle, $size);
        }

        if ($this->compression === 'application/gzip') {
            return (string) gzread($this->handle, $size);
        }

        return (string) fread($this->handle, $size);
    }

    /**
     * Returns the character set of the file
     *
     * @return string character set of the file
     */
    public function getCharset(): string
    {
        return $this->charset;
    }

    /**
     * Sets the character set of the file
     *
     * @param string $charset character set of the file
     */
    public function setCharset(string $charset): void
    {
        $this->charset = $charset;
    }

    /**
     * Returns compression used by file.
     *
     * @return string MIME type of compression, none for none
     */
    public function getCompression(): string
    {
        if ($this->compression === null) {
            return $this->detectCompression();
        }

        return $this->compression;
    }

    /**
     * Returns the offset
     *
     * @return int the offset
     */
    public function getOffset(): int
    {
        return $this->offset;
    }

    /**
     * Returns the chunk size
     *
     * @return int the chunk size
     */
    public function getChunkSize(): int
    {
        return $this->chunkSize;
    }

    /**
     * Sets the chunk size
     *
     * @param int $chunkSize the chunk size
     */
    public function setChunkSize(int $chunkSize): void
    {
        $this->chunkSize = $chunkSize;
    }

    /**
     * Returns the length of the content in the file
     *
     * @return int the length of the file content
     */
    public function getContentLength(): int
    {
        return strlen($this->content);
    }
}

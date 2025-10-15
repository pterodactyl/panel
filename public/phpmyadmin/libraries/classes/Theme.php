<?php

declare(strict_types=1);

namespace PhpMyAdmin;

use function __;
use function file_exists;
use function file_get_contents;
use function filemtime;
use function filesize;
use function in_array;
use function is_array;
use function is_dir;
use function is_readable;
use function json_decode;
use function sprintf;
use function trigger_error;
use function trim;
use function version_compare;

use const DIRECTORY_SEPARATOR;
use const E_USER_ERROR;
use const E_USER_WARNING;
use const PHP_VERSION_ID;

/**
 * handles theme
 *
 * @todo add the possibility to make a theme depend on another theme
 * and by default on original
 * @todo make all components optional - get missing components from 'parent' theme
 */
class Theme
{
    /** @var string theme version */
    public $version = '0.0.0.0';

    /** @var string theme name */
    public $name = '';

    /** @var string theme id */
    public $id = '';

    /** @var string theme path */
    public $path = '';

    /** @var string file system theme path */
    private $fsPath = '';

    /** @var string image path as an URL */
    public $imgPath = '';

    /** @var string image path on the file-system */
    public $imgPathFs = '';

    /** @var int last modification time for info file */
    public $mtimeInfo = 0;

    /**
     * needed because sometimes, the mtime for different themes
     * is identical
     *
     * @var int filesize for info file
     */
    public $filesizeInfo = 0;

    /**
     * Loads theme information
     */
    public function loadInfo(): bool
    {
        $infofile = $this->getFsPath() . 'theme.json';
        if (! @file_exists($infofile)) {
            return false;
        }

        if ($this->mtimeInfo === filemtime($infofile)) {
            return true;
        }

        $content = @file_get_contents($infofile);
        if ($content === false) {
            return false;
        }

        $data = json_decode($content, true);

        // Did we get expected data?
        if (! is_array($data)) {
            return false;
        }

        // Check that all required data are there
        $members = [
            'name',
            'version',
            'supports',
        ];
        foreach ($members as $member) {
            if (! isset($data[$member])) {
                return false;
            }
        }

        // Version check
        if (! is_array($data['supports'])) {
            return false;
        }

        if (! in_array(Version::SERIES, $data['supports'])) {
            return false;
        }

        $this->mtimeInfo = filemtime($infofile);
        $this->filesizeInfo = filesize($infofile);

        $this->setVersion($data['version']);
        $this->setName($data['name']);

        return true;
    }

    public static function load(string $themeUrl, string $themeFsPath, string $themeName): ?self
    {
        $theme = new self();

        $theme->setPath($themeUrl);
        $theme->setFsPath($themeFsPath);

        if (! $theme->loadInfo()) {
            return null;
        }

        $theme->checkImgPath();
        $theme->setId($themeName);

        return $theme;
    }

    /**
     * checks image path for existence - if not found use img from fallback theme
     */
    public function checkImgPath(): bool
    {
        // try current theme first
        if (is_dir($this->getFsPath() . 'img' . DIRECTORY_SEPARATOR)) {
            $this->setImgPath($this->getPath() . '/img/');
            $this->setImgPathFs($this->getFsPath() . 'img' . DIRECTORY_SEPARATOR);

            return true;
        }

        // try fallback theme
        $fallbackFsPathThemeDir = ThemeManager::getThemesFsDir() . ThemeManager::FALLBACK_THEME
                                  . DIRECTORY_SEPARATOR . 'img' . DIRECTORY_SEPARATOR;
        if (is_dir($fallbackFsPathThemeDir)) {
            $fallbackUrl = ThemeManager::getThemesDir() . ThemeManager::FALLBACK_THEME
                        . '/img/';
            $this->setImgPath($fallbackUrl);
            $this->setImgPathFs($fallbackFsPathThemeDir);

            return true;
        }

        // we failed
        trigger_error(
            sprintf(
                __('No valid image path for theme %s found!'),
                $this->getName()
            ),
            PHP_VERSION_ID < 80400 ? E_USER_ERROR : E_USER_WARNING
        );

        return false;
    }

    /**
     * returns path to theme
     *
     * @return string path to theme
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * returns file system path to the theme
     *
     * @return string file system path to theme
     */
    public function getFsPath(): string
    {
        return $this->fsPath;
    }

    /**
     * set path to theme
     *
     * @param string $path path to theme
     */
    public function setPath($path): void
    {
        $this->path = trim($path);
    }

    /**
     * set file system path to the theme
     *
     * @param string $path path to theme
     */
    public function setFsPath(string $path): void
    {
        $this->fsPath = trim($path);
    }

    /**
     * sets version
     *
     * @param string $version version to set
     */
    public function setVersion($version): void
    {
        $this->version = trim($version);
    }

    /**
     * returns version
     *
     * @return string version
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * checks theme version against $version
     * returns true if theme version is equal or higher to $version
     *
     * @param string $version version to compare to
     */
    public function checkVersion($version): bool
    {
        return version_compare($this->getVersion(), $version, 'lt');
    }

    /**
     * sets name
     *
     * @param string $name name to set
     */
    public function setName($name): void
    {
        $this->name = trim($name);
    }

    /**
     * returns name
     *
     * @return string name
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * sets id
     *
     * @param string $id new id
     */
    public function setId($id): void
    {
        $this->id = trim($id);
    }

    /**
     * returns id
     *
     * @return string id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Sets path to images for the theme
     *
     * @param string $path path to images for this theme as an URL path
     */
    public function setImgPath($path): void
    {
        $this->imgPath = $path;
    }

    /**
     * Sets path to images for the theme
     *
     * @param string $path file-system path to images for this theme
     */
    public function setImgPathFs(string $path): void
    {
        $this->imgPathFs = $path;
    }

    /**
     * Returns the path to image for the theme.
     * If filename is given, it possibly fallbacks to fallback
     * theme for it if image does not exist.
     *
     * @param string $file     file name for image
     * @param string $fallback fallback image
     *
     * @return string image path for this theme
     */
    public function getImgPath($file = null, $fallback = null)
    {
        if ($file === null) {
            return $this->imgPath;
        }

        if (is_readable($this->imgPathFs . $file)) {
            return $this->imgPath . $file;
        }

        if ($fallback !== null) {
            return $this->getImgPath($fallback);
        }

        return './themes/' . ThemeManager::FALLBACK_THEME . '/img/' . $file;
    }
}

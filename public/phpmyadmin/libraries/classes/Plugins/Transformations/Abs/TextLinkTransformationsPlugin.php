<?php
/**
 * Abstract class for the link transformations plugins
 */

declare(strict_types=1);

namespace PhpMyAdmin\Plugins\Transformations\Abs;

use PhpMyAdmin\FieldMetadata;
use PhpMyAdmin\Plugins\TransformationsPlugin;
use PhpMyAdmin\Sanitize;

use function __;
use function htmlspecialchars;

/**
 * Provides common methods for all of the link transformations plugins.
 */
abstract class TextLinkTransformationsPlugin extends TransformationsPlugin
{
    /**
     * Gets the transformation description of the specific plugin
     *
     * @return string
     */
    public static function getInfo()
    {
        return __(
            'Displays a link; the column contains the filename. The first option'
            . ' is a URL prefix like "https://www.example.com/". The second option'
            . ' is a title for the link.'
        );
    }

    /**
     * Does the actual work of each specific transformations plugin.
     *
     * @param string             $buffer  text to be transformed
     * @param array              $options transformation options
     * @param FieldMetadata|null $meta    meta information
     *
     * @return string
     */
    public function applyTransformation($buffer, array $options = [], ?FieldMetadata $meta = null)
    {
        $cfg = $GLOBALS['cfg'];
        $options = $this->getOptions($options, $cfg['DefaultTransformations']['TextLink']);
        $url = ($options[0] ?? '') . (isset($options[2]) && $options[2] ? '' : $buffer);
        /* Do not allow javascript links */
        if (! Sanitize::checkLink($url, true, true)) {
            return htmlspecialchars($url);
        }

        return '<a href="'
            . htmlspecialchars($url)
            . '" title="'
            . htmlspecialchars($options[1] ?? '')
            . '" target="_blank" rel="noopener noreferrer">'
            . htmlspecialchars($options[1] ?? $buffer)
            . '</a>';
    }

    /* ~~~~~~~~~~~~~~~~~~~~ Getters and Setters ~~~~~~~~~~~~~~~~~~~~ */

    /**
     * Gets the transformation name of the specific plugin
     *
     * @return string
     */
    public static function getName()
    {
        return 'TextLink';
    }
}

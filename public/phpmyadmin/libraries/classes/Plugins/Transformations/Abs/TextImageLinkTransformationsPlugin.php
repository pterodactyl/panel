<?php
/**
 * Abstract class for the image link transformations plugins
 */

declare(strict_types=1);

namespace PhpMyAdmin\Plugins\Transformations\Abs;

use PhpMyAdmin\FieldMetadata;
use PhpMyAdmin\Plugins\TransformationsPlugin;
use PhpMyAdmin\Sanitize;
use PhpMyAdmin\Template;

use function __;
use function htmlspecialchars;

/**
 * Provides common methods for all of the image link transformations plugins.
 */
abstract class TextImageLinkTransformationsPlugin extends TransformationsPlugin
{
    /**
     * Gets the transformation description of the specific plugin
     *
     * @return string
     */
    public static function getInfo()
    {
        return __(
            'Displays an image and a link; the column contains the filename. The'
            . ' first option is a URL prefix like "https://www.example.com/". The'
            . ' second and third options are the width and the height in pixels.'
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
        $options = $this->getOptions($options, $cfg['DefaultTransformations']['TextImageLink']);
        $url = $options[0] . $buffer;
        /* Do not allow javascript links */
        if (! Sanitize::checkLink($url, true, true)) {
            return htmlspecialchars($url);
        }

        $template = new Template();

        return $template->render('plugins/text_image_link_transformations', [
            'url' => $url,
            'width' => (int) $options[1],
            'height' => (int) $options[2],
            'buffer' => $buffer,
        ]);
    }

    /* ~~~~~~~~~~~~~~~~~~~~ Getters and Setters ~~~~~~~~~~~~~~~~~~~~ */

    /**
     * Gets the transformation name of the specific plugin
     *
     * @return string
     */
    public static function getName()
    {
        return 'Image Link';
    }
}

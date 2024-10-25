<?php
/**
 * Abstract class for the link transformations plugins
 */

declare(strict_types=1);

namespace PhpMyAdmin\Plugins\Transformations\Abs;

use PhpMyAdmin\FieldMetadata;
use PhpMyAdmin\Plugins\TransformationsPlugin;
use PhpMyAdmin\Url;

use function __;
use function htmlspecialchars;

/**
 * Provides common methods for all of the link transformations plugins.
 */
abstract class ImageLinkTransformationsPlugin extends TransformationsPlugin
{
    /**
     * Gets the transformation description of the specific plugin
     *
     * @return string
     */
    public static function getInfo()
    {
        return __('Displays a link to download this image.');
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
        // must disable the page loader, see
        // https://wiki.phpmyadmin.net/pma/Page_loader#Bypassing_the_page_loader
        $link = '<a class="disableAjax" target="_blank" rel="noopener noreferrer" href="';
        $link .= Url::getFromRoute('/transformation/wrapper', $options['wrapper_params']);
        $link .= '" alt="[' . htmlspecialchars($buffer);
        $link .= ']">[BLOB]</a>';

        return $link;
    }

    /* ~~~~~~~~~~~~~~~~~~~~ Getters and Setters ~~~~~~~~~~~~~~~~~~~~ */

    /**
     * Gets the transformation name of the specific plugin
     *
     * @return string
     */
    public static function getName()
    {
        return 'ImageLink';
    }
}

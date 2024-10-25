<?php
/**
 * Abstract class for the inline transformations plugins
 */

declare(strict_types=1);

namespace PhpMyAdmin\Plugins\Transformations\Abs;

use PhpMyAdmin\FieldMetadata;
use PhpMyAdmin\Plugins\TransformationsPlugin;
use PhpMyAdmin\Url;

use function __;
use function array_merge;
use function htmlspecialchars;

/**
 * Provides common methods for all of the inline transformations plugins.
 */
abstract class InlineTransformationsPlugin extends TransformationsPlugin
{
    /**
     * Gets the transformation description of the specific plugin
     *
     * @return string
     */
    public static function getInfo()
    {
        return __(
            'Displays a clickable thumbnail. The options are the maximum width'
            . ' and height in pixels. The original aspect ratio is preserved.'
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
        $options = $this->getOptions($options, $cfg['DefaultTransformations']['Inline']);

        if ($GLOBALS['config']->get('PMA_IS_GD2') === 1) {
            return '<a href="' . Url::getFromRoute('/transformation/wrapper', $options['wrapper_params'])
                . '" rel="noopener noreferrer" target="_blank"><img src="'
                . Url::getFromRoute('/transformation/wrapper', array_merge($options['wrapper_params'], [
                    'resize' => 'jpeg',
                    'newWidth' => (int) $options[0],
                    'newHeight' => (int) $options[1],
                ]))
                . '" alt="[' . htmlspecialchars($buffer) . ']" border="0"></a>';
        }

        return '<img src="' . Url::getFromRoute('/transformation/wrapper', $options['wrapper_params'])
            . '" alt="[' . htmlspecialchars($buffer) . ']" width="320" height="240">';
    }

    /* ~~~~~~~~~~~~~~~~~~~~ Getters and Setters ~~~~~~~~~~~~~~~~~~~~ */

    /**
     * Gets the transformation name of the specific plugin
     *
     * @return string
     */
    public static function getName()
    {
        return 'Inline';
    }
}

<?php
/**
 * Abstract class for the prepend/append transformations plugins
 */

declare(strict_types=1);

namespace PhpMyAdmin\Plugins\Transformations\Abs;

use PhpMyAdmin\FieldMetadata;
use PhpMyAdmin\Plugins\TransformationsPlugin;

use function __;
use function htmlspecialchars;

/**
 * Provides common methods for all of the prepend/append transformations plugins.
 */
abstract class PreApPendTransformationsPlugin extends TransformationsPlugin
{
    /**
     * Gets the transformation description of the specific plugin
     *
     * @return string
     */
    public static function getInfo()
    {
        return __(
            'Prepends and/or Appends text to a string. First option is text'
            . ' to be prepended, second is appended (enclosed in single'
            . ' quotes, default empty string).'
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
        $options = $this->getOptions($options, $cfg['DefaultTransformations']['PreApPend']);

        //just prepend and/or append the options to the original text
        return htmlspecialchars($options[0]) . htmlspecialchars($buffer)
            . htmlspecialchars($options[1]);
    }

    /* ~~~~~~~~~~~~~~~~~~~~ Getters and Setters ~~~~~~~~~~~~~~~~~~~~ */

    /**
     * Gets the transformation name of the specific plugin
     *
     * @return string
     */
    public static function getName()
    {
        return 'PreApPend';
    }
}

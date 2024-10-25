<?php
/**
 * Interface for the transformations plugins
 */

declare(strict_types=1);

namespace PhpMyAdmin\Plugins;

/**
 * Provides a common interface that will have to be implemented by all of the
 * transformations plugins.
 */
interface TransformationsInterface
{
    /**
     * Gets the transformation description
     *
     * @return string
     */
    public static function getInfo();

    /**
     * Gets the specific MIME type
     *
     * @return string
     */
    public static function getMIMEType();

    /**
     * Gets the specific MIME subtype
     *
     * @return string
     */
    public static function getMIMESubtype();

    /**
     * Gets the transformation name of the specific plugin
     *
     * @return string
     */
    public static function getName();
}

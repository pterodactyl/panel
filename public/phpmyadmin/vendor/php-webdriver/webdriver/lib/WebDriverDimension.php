<?php

namespace Facebook\WebDriver;

/**
 * Represent a dimension.
 */
class WebDriverDimension
{
    /**
     * @var int|float
     */
    private $height;
    /**
     * @var int|float
     */
    private $width;

    /**
     * @param int|float $width
     * @param int|float $height
     */
    public function __construct($width, $height)
    {
        $this->width = $width;
        $this->height = $height;
    }

    /**
     * Get the height.
     *
     * @return int The height.
     */
    public function getHeight()
    {
        return (int) $this->height;
    }

    /**
     * Get the width.
     *
     * @return int The width.
     */
    public function getWidth()
    {
        return (int) $this->width;
    }

    /**
     * Check whether the given dimension is the same as the instance.
     *
     * @param WebDriverDimension $dimension The dimension to be compared with.
     * @return bool Whether the height and the width are the same as the instance.
     */
    public function equals(self $dimension)
    {
        return $this->height === $dimension->getHeight() && $this->width === $dimension->getWidth();
    }
}

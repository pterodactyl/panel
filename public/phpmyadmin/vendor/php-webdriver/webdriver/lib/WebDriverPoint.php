<?php

namespace Facebook\WebDriver;

/**
 * Represent a point.
 */
class WebDriverPoint
{
    private $x;
    private $y;

    public function __construct($x, $y)
    {
        $this->x = $x;
        $this->y = $y;
    }

    /**
     * Get the x-coordinate.
     *
     * @return int The x-coordinate of the point.
     */
    public function getX()
    {
        return (int) $this->x;
    }

    /**
     * Get the y-coordinate.
     *
     * @return int The y-coordinate of the point.
     */
    public function getY()
    {
        return (int) $this->y;
    }

    /**
     * Set the point to a new position.
     *
     * @param int $new_x
     * @param int $new_y
     * @return WebDriverPoint The same instance with updated coordinates.
     */
    public function move($new_x, $new_y)
    {
        $this->x = $new_x;
        $this->y = $new_y;

        return $this;
    }

    /**
     * Move the current by offsets.
     *
     * @param int $x_offset
     * @param int $y_offset
     * @return WebDriverPoint The same instance with updated coordinates.
     */
    public function moveBy($x_offset, $y_offset)
    {
        $this->x += $x_offset;
        $this->y += $y_offset;

        return $this;
    }

    /**
     * Check whether the given point is the same as the instance.
     *
     * @param WebDriverPoint $point The point to be compared with.
     * @return bool Whether the x and y coordinates are the same as the instance.
     */
    public function equals(self $point)
    {
        return $this->x === $point->getX() &&
        $this->y === $point->getY();
    }
}

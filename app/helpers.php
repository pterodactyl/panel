<?php

if (!function_exists('is_digit')) {
    /**
     * Deal with normal (and irritating) PHP behavior to determine if
     * a value is a non-float positive integer.
     *
     * @param mixed $value
     *
     * @return bool
     */
    function is_digit($value)
    {
        return is_bool($value) ? false : ctype_digit(strval($value));
    }
}

if (!function_exists('object_get_strict')) {
    /**
     * Get an object using dot notation. An object key with a value of null is still considered valid
     * and will not trigger the response of a default value (unlike object_get).
     *
     * @param object $object
     * @param string $key
     * @param null $default
     *
     * @return mixed
     */
    function object_get_strict($object, $key, $default = null)
    {
        if (is_null($key) || trim($key) == '') {
            return $object;
        }

        foreach (explode('.', $key) as $segment) {
            if (!is_object($object) || !property_exists($object, $segment)) {
                return value($default);
            }

            $object = $object->{$segment};
        }

        return $object;
    }
}

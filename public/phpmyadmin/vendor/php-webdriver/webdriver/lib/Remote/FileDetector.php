<?php

namespace Facebook\WebDriver\Remote;

interface FileDetector
{
    /**
     * Try to detect whether the given $file is a file or not. Return the path
     * of the file. Otherwise, return null.
     *
     * @param string $file
     *
     * @return null|string The path of the file.
     */
    public function getLocalFile($file);
}

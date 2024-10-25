<?php

namespace Facebook\WebDriver\Remote;

class LocalFileDetector implements FileDetector
{
    /**
     * @param string $file
     *
     * @return null|string
     */
    public function getLocalFile($file)
    {
        if (is_file($file)) {
            return realpath($file);
        }

        return null;
    }
}

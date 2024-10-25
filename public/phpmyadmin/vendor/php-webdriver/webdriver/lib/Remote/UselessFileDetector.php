<?php

namespace Facebook\WebDriver\Remote;

class UselessFileDetector implements FileDetector
{
    public function getLocalFile($file)
    {
        return null;
    }
}

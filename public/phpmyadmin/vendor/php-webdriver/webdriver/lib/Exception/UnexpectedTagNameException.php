<?php

namespace Facebook\WebDriver\Exception;

class UnexpectedTagNameException extends WebDriverException
{
    /**
     * @param string $expected_tag_name
     * @param string $actual_tag_name
     */
    public function __construct(
        $expected_tag_name,
        $actual_tag_name
    ) {
        parent::__construct(
            sprintf(
                'Element should have been "%s" but was "%s"',
                $expected_tag_name,
                $actual_tag_name
            )
        );
    }
}

<?php

namespace Facebook\WebDriver\Support;

class XPathEscaper
{
    /**
     * Converts xpath strings with both quotes and ticks into:
     *   `foo'"bar` -> `concat('foo', "'" ,'"bar')`
     *
     * @param string $xpathToEscape The xpath to be converted.
     * @return string The escaped string.
     */
    public static function escapeQuotes($xpathToEscape)
    {
        // Single quotes not present => we can quote in them
        if (mb_strpos($xpathToEscape, "'") === false) {
            return sprintf("'%s'", $xpathToEscape);
        }

        // Double quotes not present => we can quote in them
        if (mb_strpos($xpathToEscape, '"') === false) {
            return sprintf('"%s"', $xpathToEscape);
        }

        // Both single and double quotes are present
        return sprintf(
            "concat('%s')",
            str_replace("'", "', \"'\" ,'", $xpathToEscape)
        );
    }
}

<?php

declare(strict_types=1);

namespace PhpMyAdmin\Config\Settings;

use function is_array;

// phpcs:disable Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps

/**
 * @psalm-immutable
 */
final class Transformations
{
    /**
     * Displays a part of a string.
     * - The first option is the number of characters to skip from the beginning of the string (Default 0).
     * - The second option is the number of characters to return (Default: until end of string).
     * - The third option is the string to append and/or prepend when truncation occurs (Default: "…").
     *
     * @var array<int, int|string>
     * @psalm-var array{0: int, 1: 'all'|int, 2: string}
     */
    public $Substring;

    /**
     * Converts Boolean values to text (default 'T' and 'F').
     * - First option is for TRUE, second for FALSE. Nonzero=true.
     *
     * @var string[]
     * @psalm-var array{0: string, 1: string}
     */
    public $Bool2Text;

    /**
     * LINUX ONLY: Launches an external application and feeds it the column data via standard input.
     * Returns the standard output of the application. The default is Tidy, to pretty-print HTML code.
     * For security reasons, you have to manually edit the file
     * libraries/classes/Plugins/Transformations/Abs/ExternalTransformationsPlugin.php and list the tools
     * you want to make available.
     * - The first option is then the number of the program you want to use.
     * - The second option should be blank for historical reasons.
     * - The third option, if set to 1, will convert the output using htmlspecialchars() (Default 1).
     * - The fourth option, if set to 1, will prevent wrapping and ensure that the output appears
     *   all on one line (Default 1).
     *
     * @var array<int, int|string>
     * @psalm-var array{0: int, 1: string, 2: int, 3: int}
     */
    public $External;

    /**
     * Prepends and/or Appends text to a string.
     * - First option is text to be prepended. second is appended (enclosed in single quotes, default empty string).
     *
     * @var string[]
     * @psalm-var array{0: string, 1: string}
     */
    public $PreApPend;

    /**
     * Displays hexadecimal representation of data.
     * Optional first parameter specifies how often space will be added (defaults to 2 nibbles).
     *
     * @var string[]
     * @psalm-var array{0: 0|positive-int}
     */
    public $Hex;

    /**
     * Displays a TIME, TIMESTAMP, DATETIME or numeric unix timestamp column as formatted date.
     * - The first option is the offset (in hours) which will be added to the timestamp (Default: 0).
     * - Use second option to specify a different date/time format string.
     * - Third option determines whether you want to see local date or UTC one (use "local" or "utc" strings) for that.
     *   According to that, date format has different value - for "local" see the documentation
     *   for PHP's strftime() function and for "utc" it is done using gmdate() function.
     *
     * @var array<int, int|string>
     * @psalm-var array{0: 0|positive-int, 1: string, 2: 'local'|'utc'}
     */
    public $DateFormat;

    /**
     * Displays a clickable thumbnail.
     * The options are the maximum width and height in pixels.
     * The original aspect ratio is preserved.
     *
     * @var array<(int|string), (int|string|array<string, string>|null)>
     * @psalm-var array{
     *   0: 0|positive-int,
     *   1: 0|positive-int,
     *   wrapper_link: string|null,
     *   wrapper_params: array<array-key, string>
     * }
     */
    public $Inline;

    /**
     * Displays an image and a link; the column contains the filename.
     * - The first option is a URL prefix like "https://www.example.com/".
     * - The second and third options are the width and the height in pixels.
     *
     * @var array<int, int|string|null>
     * @psalm-var array{0: string|null, 1: 0|positive-int, 2: 0|positive-int}
     */
    public $TextImageLink;

    /**
     * Displays a link; the column contains the filename.
     * - The first option is a URL prefix like "https://www.example.com/".
     * - The second option is a title for the link.
     *
     * @var array<int, string|null>
     * @psalm-var array{0: string|null, 1: string|null, 2: bool|null}
     */
    public $TextLink;

    /**
     * @param array<int|string, mixed> $transformations
     */
    public function __construct(array $transformations = [])
    {
        $this->Substring = $this->setSubstring($transformations);
        $this->Bool2Text = $this->setBool2Text($transformations);
        $this->External = $this->setExternal($transformations);
        $this->PreApPend = $this->setPreApPend($transformations);
        $this->Hex = $this->setHex($transformations);
        $this->DateFormat = $this->setDateFormat($transformations);
        $this->Inline = $this->setInline($transformations);
        $this->TextImageLink = $this->setTextImageLink($transformations);
        $this->TextLink = $this->setTextLink($transformations);
    }

    /**
     * @param array<int|string, mixed> $transformations
     *
     * @return array<int, int|string>
     * @psalm-return array{0: int, 1: 'all'|int, 2: string}
     */
    private function setSubstring(array $transformations): array
    {
        $substring = [0, 'all', '…'];
        if (isset($transformations['Substring']) && is_array($transformations['Substring'])) {
            if (isset($transformations['Substring'][0])) {
                $substring[0] = (int) $transformations['Substring'][0];
            }

            if (isset($transformations['Substring'][1]) && $transformations['Substring'][1] !== 'all') {
                $substring[1] = (int) $transformations['Substring'][1];
            }

            if (isset($transformations['Substring'][2])) {
                $substring[2] = (string) $transformations['Substring'][2];
            }
        }

        return $substring;
    }

    /**
     * @param array<int|string, mixed> $transformations
     *
     * @return string[]
     * @psalm-return array{0: string, 1: string}
     */
    private function setBool2Text(array $transformations): array
    {
        $bool2Text = ['T', 'F'];
        if (isset($transformations['Bool2Text']) && is_array($transformations['Bool2Text'])) {
            if (isset($transformations['Bool2Text'][0])) {
                $bool2Text[0] = (string) $transformations['Bool2Text'][0];
            }

            if (isset($transformations['Bool2Text'][1])) {
                $bool2Text[1] = (string) $transformations['Bool2Text'][1];
            }
        }

        return $bool2Text;
    }

    /**
     * @param array<int|string, mixed> $transformations
     *
     * @return array<int, int|string>
     * @psalm-return array{0: int, 1: string, 2: int, 3: int}
     */
    private function setExternal(array $transformations): array
    {
        $external = [0, '-f /dev/null -i -wrap -q', 1, 1];
        if (isset($transformations['External']) && is_array($transformations['External'])) {
            if (isset($transformations['External'][0])) {
                $external[0] = (int) $transformations['External'][0];
            }

            if (isset($transformations['External'][1])) {
                $external[1] = (string) $transformations['External'][1];
            }

            if (isset($transformations['External'][2])) {
                $external[2] = (int) $transformations['External'][2];
            }

            if (isset($transformations['External'][3])) {
                $external[3] = (int) $transformations['External'][3];
            }
        }

        return $external;
    }

    /**
     * @param array<int|string, mixed> $transformations
     *
     * @return string[]
     * @psalm-return array{0: string, 1: string}
     */
    private function setPreApPend(array $transformations): array
    {
        $preApPend = ['', ''];
        if (isset($transformations['PreApPend']) && is_array($transformations['PreApPend'])) {
            if (isset($transformations['PreApPend'][0])) {
                $preApPend[0] = (string) $transformations['PreApPend'][0];
            }

            if (isset($transformations['PreApPend'][1])) {
                $preApPend[1] = (string) $transformations['PreApPend'][1];
            }
        }

        return $preApPend;
    }

    /**
     * @param array<int|string, mixed> $transformations
     *
     * @return string[]
     * @psalm-return array{0: 0|positive-int}
     */
    private function setHex(array $transformations): array
    {
        if (isset($transformations['Hex']) && is_array($transformations['Hex'])) {
            if (isset($transformations['Hex'][0])) {
                $length = (int) $transformations['Hex'][0];
                if ($length >= 0) {
                    return [$length];
                }
            }
        }

        return [2];
    }

    /**
     * @param array<int|string, mixed> $transformations
     *
     * @return array<int, int|string>
     * @psalm-return array{0: 0|positive-int, 1: string, 2: 'local'|'utc'}
     */
    private function setDateFormat(array $transformations): array
    {
        $dateFormat = [0, '', 'local'];
        if (isset($transformations['DateFormat']) && is_array($transformations['DateFormat'])) {
            if (isset($transformations['DateFormat'][0])) {
                $offset = (int) $transformations['DateFormat'][0];
                if ($offset >= 1) {
                    $dateFormat[0] = $offset;
                }
            }

            if (isset($transformations['DateFormat'][1])) {
                $dateFormat[1] = (string) $transformations['DateFormat'][1];
            }

            if (isset($transformations['DateFormat'][2]) && $transformations['DateFormat'][2] === 'utc') {
                $dateFormat[2] = 'utc';
            }
        }

        return $dateFormat;
    }

    /**
     * @param array<int|string, mixed> $transformations
     *
     * @return array<(int|string), (int|string|array<string, string>|null)>
     * @psalm-return array{
     *   0: 0|positive-int,
     *   1: 0|positive-int,
     *   wrapper_link: string|null,
     *   wrapper_params: array<array-key, string>
     * }
     */
    private function setInline(array $transformations): array
    {
        $inline = [100, 100, 'wrapper_link' => null, 'wrapper_params' => []];
        if (isset($transformations['Inline']) && is_array($transformations['Inline'])) {
            if (isset($transformations['Inline'][0])) {
                $width = (int) $transformations['Inline'][0];
                if ($width >= 0) {
                    $inline[0] = $width;
                }
            }

            if (isset($transformations['Inline'][1])) {
                $height = (int) $transformations['Inline'][1];
                if ($height >= 0) {
                    $inline[1] = $height;
                }
            }

            if (isset($transformations['Inline']['wrapper_link'])) {
                $inline['wrapper_link'] = (string) $transformations['Inline']['wrapper_link'];
            }

            if (
                isset($transformations['Inline']['wrapper_params'])
                && is_array($transformations['Inline']['wrapper_params'])
            ) {
                /**
                 * @var int|string $key
                 * @var mixed $value
                 */
                foreach ($transformations['Inline']['wrapper_params'] as $key => $value) {
                    $inline['wrapper_params'][$key] = (string) $value;
                }
            }
        }

        return $inline;
    }

    /**
     * @param array<int|string, mixed> $transformations
     *
     * @return array<int, int|string|null>
     * @psalm-return array{0: string|null, 1: 0|positive-int, 2: 0|positive-int}
     */
    private function setTextImageLink(array $transformations): array
    {
        $textImageLink = [null, 100, 50];
        if (isset($transformations['TextImageLink']) && is_array($transformations['TextImageLink'])) {
            if (isset($transformations['TextImageLink'][0])) {
                $textImageLink[0] = (string) $transformations['TextImageLink'][0];
            }

            if (isset($transformations['TextImageLink'][1])) {
                $width = (int) $transformations['TextImageLink'][1];
                if ($width >= 0) {
                    $textImageLink[1] = $width;
                }
            }

            if (isset($transformations['TextImageLink'][2])) {
                $height = (int) $transformations['TextImageLink'][2];
                if ($height >= 0) {
                    $textImageLink[2] = $height;
                }
            }
        }

        return $textImageLink;
    }

    /**
     * @param array<int|string, mixed> $transformations
     *
     * @return array<int, string|null>
     * @psalm-return array{0: string|null, 1: string|null, 2: bool|null}
     */
    private function setTextLink(array $transformations): array
    {
        $textLink = [null, null, null];
        if (isset($transformations['TextLink']) && is_array($transformations['TextLink'])) {
            if (isset($transformations['TextLink'][0])) {
                $textLink[0] = (string) $transformations['TextLink'][0];
            }

            if (isset($transformations['TextLink'][1])) {
                $textLink[1] = (string) $transformations['TextLink'][1];
            }

            if (isset($transformations['TextLink'][2])) {
                $textLink[2] = (bool) $transformations['TextLink'][2];
            }
        }

        return $textLink;
    }
}

<?php

namespace Facebook\WebDriver;

/**
 * Representations of pressable keys that aren't text.
 * These are stored in the Unicode PUA (Private Use Area) code points.
 * @see https://w3c.github.io/webdriver/#keyboard-actions
 */
class WebDriverKeys
{
    const NULL = "\xEE\x80\x80";
    const CANCEL = "\xEE\x80\x81";
    const HELP = "\xEE\x80\x82";
    const BACKSPACE = "\xEE\x80\x83";
    const TAB = "\xEE\x80\x84";
    const CLEAR = "\xEE\x80\x85";
    const RETURN_KEY = "\xEE\x80\x86";
    const ENTER = "\xEE\x80\x87";
    const SHIFT = "\xEE\x80\x88";
    const CONTROL = "\xEE\x80\x89";
    const ALT = "\xEE\x80\x8A";
    const PAUSE = "\xEE\x80\x8B";
    const ESCAPE = "\xEE\x80\x8C";
    const SPACE = "\xEE\x80\x8D";
    const PAGE_UP = "\xEE\x80\x8E";
    const PAGE_DOWN = "\xEE\x80\x8F";
    const END = "\xEE\x80\x90";
    const HOME = "\xEE\x80\x91";
    const ARROW_LEFT = "\xEE\x80\x92";
    const ARROW_UP = "\xEE\x80\x93";
    const ARROW_RIGHT = "\xEE\x80\x94";
    const ARROW_DOWN = "\xEE\x80\x95";
    const INSERT = "\xEE\x80\x96";
    const DELETE = "\xEE\x80\x97";
    const SEMICOLON = "\xEE\x80\x98";
    const EQUALS = "\xEE\x80\x99";
    const NUMPAD0 = "\xEE\x80\x9A";
    const NUMPAD1 = "\xEE\x80\x9B";
    const NUMPAD2 = "\xEE\x80\x9C";
    const NUMPAD3 = "\xEE\x80\x9D";
    const NUMPAD4 = "\xEE\x80\x9E";
    const NUMPAD5 = "\xEE\x80\x9F";
    const NUMPAD6 = "\xEE\x80\xA0";
    const NUMPAD7 = "\xEE\x80\xA1";
    const NUMPAD8 = "\xEE\x80\xA2";
    const NUMPAD9 = "\xEE\x80\xA3";
    const MULTIPLY = "\xEE\x80\xA4";
    const ADD = "\xEE\x80\xA5";
    const SEPARATOR = "\xEE\x80\xA6";
    const SUBTRACT = "\xEE\x80\xA7";
    const DECIMAL = "\xEE\x80\xA8";
    const DIVIDE = "\xEE\x80\xA9";
    const F1 = "\xEE\x80\xB1";
    const F2 = "\xEE\x80\xB2";
    const F3 = "\xEE\x80\xB3";
    const F4 = "\xEE\x80\xB4";
    const F5 = "\xEE\x80\xB5";
    const F6 = "\xEE\x80\xB6";
    const F7 = "\xEE\x80\xB7";
    const F8 = "\xEE\x80\xB8";
    const F9 = "\xEE\x80\xB9";
    const F10 = "\xEE\x80\xBA";
    const F11 = "\xEE\x80\xBB";
    const F12 = "\xEE\x80\xBC";
    const META = "\xEE\x80\xBD";
    const ZENKAKU_HANKAKU = "\xEE\x80\xC0";
    const RIGHT_SHIFT = "\xEE\x81\x90";
    const RIGHT_CONTROL = "\xEE\x81\x91";
    const RIGHT_ALT = "\xEE\x81\x92";
    const RIGHT_META = "\xEE\x81\x93";
    const NUMPAD_PAGE_UP = "\xEE\x81\x94";
    const NUMPAD_PAGE_DOWN = "\xEE\x81\x95";
    const NUMPAD_END = "\xEE\x81\x96";
    const NUMPAD_HOME = "\xEE\x81\x97";
    const NUMPAD_ARROW_LEFT = "\xEE\x81\x98";
    const NUMPAD_ARROW_UP = "\xEE\x81\x99";
    const NUMPAD_ARROW_RIGHT = "\xEE\x81\x9A";
    const NUMPAD_ARROW_DOWN = "\xEE\x81\x9B";
    const NUMPAD_ARROW_INSERT = "\xEE\x81\x9C";
    const NUMPAD_ARROW_DELETE = "\xEE\x81\x9D";
    // Aliases
    const LEFT_SHIFT = self::SHIFT;
    const LEFT_CONTROL = self::CONTROL;
    const LEFT_ALT = self::ALT;
    const LEFT = self::ARROW_LEFT;
    const UP = self::ARROW_UP;
    const RIGHT = self::ARROW_RIGHT;
    const DOWN = self::ARROW_DOWN;
    const COMMAND = self::META;

    /**
     * Encode input of `sendKeys()` to appropriate format according to protocol.
     *
     * @param string|array|int|float $keys
     * @param bool $isW3cCompliant
     * @return array|string
     */
    public static function encode($keys, $isW3cCompliant = false)
    {
        if (is_numeric($keys)) {
            $keys = (string) $keys;
        }

        if (is_string($keys)) {
            $keys = [$keys];
        }

        if (!is_array($keys)) {
            if (!$isW3cCompliant) {
                return [];
            }

            return '';
        }

        $encoded = [];
        foreach ($keys as $key) {
            if (is_array($key)) {
                // handle key modifiers
                $key = implode('', $key) . self::NULL; // the NULL clears the input state (eg. previous modifiers)
            }
            $encoded[] = (string) $key;
        }

        if (!$isW3cCompliant) {
            return $encoded;
        }

        return implode('', $encoded);
    }
}

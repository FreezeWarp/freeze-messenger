<?php
namespace Fim;

class Utilities {
    public static function luminance($red, $green, $blue)
    {
        $colors = [$red, $green, $blue];

        foreach ($colors AS &$color) {
            assert($color >= 0 && $color <= 255);

            $color /= 255;
            $color = ($color <= 0.03928
                ? $color / 12.92
                : pow(($color + 0.055) / 1.055, 2.4));
        }

        return $colors[0] * 0.2126 + $colors[1] * 0.7152 + $colors[2] * 0.0722;
    }

    public static function contrast($rgb1, $rgb2)
    {
        $rgb1luminance = self::luminance($rgb1[0], $rgb1[1], $rgb1[2]);
        $rgb2luminance = self::luminance($rgb2[0], $rgb2[1], $rgb2[2]);

        return $rgb1luminance > $rgb2luminance
            ? ($rgb1luminance + .05) / ($rgb2luminance + .05)
            : ($rgb2luminance + .05) / ($rgb1luminance + .05);
    }

    public static function encodeList(array $list) {
        return implode(',', $list);
    }

    public static function decodeList($listString) {
        return self::emptyExplode(",", $listString);
    }

    /**
     * Acts like PHP's explode, but will return an empty array ([] instead of [""]) if passed an empty string or otherwise falsey value.
     *
     * @param string $separator
     * @param string $list
     * @return array
     */
    public static function emptyExplode(string $separator, $list) {
        return $list ? explode($separator, $list) : [];
    }

}
<?php

/*
 * Forums addon for Bear CMS
 * https://github.com/bearcms/forums-addon
 * Copyright (c) Amplilabs Ltd.
 * Free to use under the MIT license.
 */

namespace BearCMS\Internal;

/**
 * @internal
 */
class ForumsUtilities
{

    /**
     * 
     * @param string $postID
     * @param string $text
     * @return string
     */
    static function getSlug(string $postID, string $text): string
    {
        if (function_exists('mb_strtolower') && function_exists('mb_substr')) {
            $maxLength = 120;
            $text = mb_strtolower($text);
            $text = preg_replace('/\s+/', '-', $text);
            $text = str_replace(['/', '\\', '%', '*', '^', '$', '#', '@', '?', '!', '&', '[', ']', '{', '}', '(', ')', '<', '>', '|', '\'', '"', '=', '+', ':', ';', '.', ','], '-', $text);
            while (strpos($text, '--') !== false) {
                $text = str_replace('--', '-', $text);
            }
            $text = trim(mb_substr($text, 0, $maxLength), '-');
        }
        return $text . '-' . $postID;
    }

    /**
     * 
     * @param string $slug
     * @return string
     */
    static function getIDFromSlug(string $slug): string
    {
        $temp = explode('-', $slug);
        return $temp[count($temp) - 1];
    }

}

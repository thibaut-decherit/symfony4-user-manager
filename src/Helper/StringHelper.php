<?php

namespace App\Helper;

/**
 * Class StringHelper.
 * Utility class for string formatting.
 *
 * @package App\Helper
 */
class StringHelper
{
    /**
     * Returns true if $string starts with $query, otherwise it returns false.
     * Supports extended charsets.
     *
     * @param string $string
     * @param string $query
     * @param string $encoding
     * @return bool
     */
    public static function startsWith(string $string, string $query, string $encoding = 'UTF-8'): bool
    {
        return mb_substr($string, 0, mb_strlen($query, $encoding), $encoding) === $query;
    }

    /**
     * Prevents potential slowdown or DoS caused by hashing very long passwords.
     * Supports extended charsets.
     *
     * @param string $string
     * @param int $length
     * @param string $encoding
     * @return string
     */
    public static function truncateToPasswordEncoderMaxLength(
        string $string,
        int $length = 100,
        string $encoding = 'UTF-8'
    ): string
    {
        return mb_substr($string, 0, $length, $encoding);
    }

    /**
     * Prevents potential slowdown or DoS caused by feeding an extremely long string to a MySQL query.
     * Supports extended charsets.
     *
     * @param string $string
     * @param int $length
     * @param string $encoding
     * @return string
     */
    public static function truncateToMySQLVarcharMaxLength(
        string $string,
        int $length = 255,
        string $encoding = 'UTF-8'
    ): string
    {
        return mb_substr($string, 0, $length, $encoding);
    }

    /**
     * Supports extended charsets, unlike native strtolower().
     *
     * @param string $string
     * @param string $encoding
     * @return string
     */
    public static function strToLower(string $string, string $encoding = 'UTF-8'): string
    {
        return mb_strtolower($string, $encoding);
    }

    /**
     * Supports extended charsets, unlike native strtoupper().
     *
     * @param string $string
     * @param string $encoding
     * @return string
     */
    public static function strToUpper(string $string, string $encoding = 'UTF-8'): string
    {
        return mb_strtoupper($string, $encoding);
    }

    /**
     * Supports extended charsets, unlike native ucfirst().
     *
     * @param string $string
     * @param string $encoding
     * @return string
     */
    public static function ucFirst(string $string, ?string $encoding = 'UTF-8'): string
    {
        return mb_strtoupper(mb_substr($string, 0, 1, $encoding), $encoding) . mb_substr($string, 1, null, $encoding);
    }

    /**
     * Supports extended charsets, unlike native ucwords().
     *
     * @param string $string
     * @param string $encoding
     * @return string
     */
    public static function ucWords(string $string, ?string $encoding = 'UTF-8'): string
    {
        return mb_convert_case($string, MB_CASE_TITLE, $encoding);
    }
}

<?php

namespace App\Helper;

use Exception;

/**
 * Class StringHelper
 * Utility class for string formatting and manipulation.
 *
 * @package App\Helper
 */
class StringHelper
{
    /**
     * Returns an URI safe base64 encoded cryptographically secure pseudo-random string that does not contain
     * "+", "/" or "=" which need to be URL encoded and make URLs unnecessarily longer.
     * With 512 bits of entropy this method will return a string of 86 characters, with 256 bits of entropy it will
     * return 43 characters, and so on.
     * String length is ceil($entropy / 6).
     *
     * @param int $entropy
     * @return string
     * @throws Exception
     */
    public static function generateRandomString(int $entropy = 512): string
    {
        $bytes = random_bytes($entropy / 8);

        return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
    }

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
        int $length = 4096,
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

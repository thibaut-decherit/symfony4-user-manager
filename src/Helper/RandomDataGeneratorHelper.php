<?php

namespace App\Helper;

use Exception;

/**
 * Class RandomDataGeneratorHelper
 * Utility class for cryptographically secure pseudo-random data generation.
 *
 * @package App\Helper
 */
class RandomDataGeneratorHelper
{
    /**
     * Generates cryptographically secure pseudo-random floats.
     *
     * @param int $min
     * @param int $max
     * @param int $maxDecimalNbr
     * @return float
     * @throws Exception
     */
    public static function randomFloat(int $min = 0, int $max = 2147483647, int $maxDecimalNbr = 1): float
    {
        while (true) {
            $decimal = random_int(0, 2147483647);
            $decimal = $decimal / pow(10, strlen((string)$decimal));

            $result = round(random_int($min, $max) + $decimal, $maxDecimalNbr);

            if ($result <= $max) {
                return $result;
            }
        }

        throw new Exception('While loop should not have broken');
    }

    /**
     * Generates cryptographically secure pseudo-random integers.
     *
     * @param int $min
     * @param int $max
     * @return int
     * @throws Exception
     */
    public static function randomInteger(int $min = 0, int $max = 2147483647): int
    {
        return random_int($min, $max);
    }

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
    public static function randomString(int $entropy = 512): string
    {
        $bytes = random_bytes($entropy / 8);

        return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
    }
}

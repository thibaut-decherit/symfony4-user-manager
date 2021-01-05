<?php

namespace App\Helper;

use Exception;
use RuntimeException;

/**
 * Class RandomDataGeneratorHelper
 * Utility class for cryptographically secure pseudo-random data generation.
 *
 * @package App\Helper
 */
class RandomDataGeneratorHelper
{
    /**
     * Generates a pseudo-random float.
     * Warning: May or may not be cryptographically secure. Uses a cryptographically secure native function (random_int)
     * but implementation could weaken randomness (e.g. because of trailing zeros removal in float's decimal and
     * discarding of results greater than $max).
     *
     * @param int $min
     * @param int $max
     * @param int $maxDecimalNbr
     * @return float
     * @throws Exception
     */
    public static function randomFloat(int $min = 0, int $max = 2147483647, int $maxDecimalNbr = 1): float
    {
        for ($i = 0; $i < 1000; $i++) {
            $decimalString = '.';

            for ($j = 0; $j < $maxDecimalNbr; $j++) {
                $decimalString .= (string)random_int(0, 9);
            }

            $resultString = (string)random_int($min, $max) . $decimalString;
            $result = (float)$resultString;

            if ($result <= $max) {
                return $result;
            }
        }

        throw new RuntimeException('For loop should not have broken.');
    }

    /**
     * Generates a cryptographically secure pseudo-random integer.
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
     * Generates an URI safe base64 encoded cryptographically secure pseudo-random string that does not contain
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

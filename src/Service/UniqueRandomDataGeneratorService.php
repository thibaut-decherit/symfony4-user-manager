<?php

namespace App\Service;

use App\Helper\RandomDataGeneratorHelper;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use RuntimeException;

/**
 * Class UniqueRandomDataGeneratorService
 *
 * Generates cryptographically secure pseudo-random unique data.
 * Unique meaning it does not exist yet in database for given entity and property.
 *
 * @package App\Service
 */
class UniqueRandomDataGeneratorService
{
    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * UniqueRandomDataGeneratorService constructor.
     * @param EntityManagerInterface $em
     */
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * @param string $entityClass
     * @param string $propertyName
     * @param $data
     * @return bool
     */
    private function isUnique(string $entityClass, string $propertyName, $data): bool
    {
        $duplicate = $this->em->getRepository($entityClass)->findOneBy([
            $propertyName => $data
        ]);

        return is_null($duplicate);
    }

    /**
     * @param string $entityClass
     * @param string $propertyName
     * @param int $min
     * @param int $max
     * @param int $maxDecimalNbr
     * @return float
     * @throws Exception
     */
    public function uniqueRandomFloat(
        string $entityClass,
        string $propertyName,
        int $min = 0,
        int $max = 2147483647,
        int $maxDecimalNbr = 1
    ): float
    {
        for ($i = 0; $i < 1000; $i++) {
            $randomFloat = RandomDataGeneratorHelper::randomFloat($min, $max, $maxDecimalNbr);

            if ($this->isUnique($entityClass, $propertyName, $randomFloat)) {
                return $randomFloat;
            }
        }

        throw new RuntimeException('For loop should not have broken.');
    }

    /**
     * @param string $entityClass
     * @param string $propertyName
     * @param int $min
     * @param int $max
     * @return int
     * @throws Exception
     */
    public function uniqueRandomInteger(
        string $entityClass,
        string $propertyName,
        int $min = 0,
        int $max = 2147483647
    ): int
    {
        for ($i = 0; $i < 1000; $i++) {
            $randomInt = RandomDataGeneratorHelper::randomInteger($min, $max);

            if ($this->isUnique($entityClass, $propertyName, $randomInt)) {
                return $randomInt;
            }
        }

        throw new RuntimeException('For loop should not have broken.');
    }

    /**
     * @param string $entityClass
     * @param string $propertyName
     * @param int $entropy
     * @return string
     * @throws Exception
     */
    public function uniqueRandomString(string $entityClass, string $propertyName, int $entropy = 512): string
    {
        for ($i = 0; $i < 1000; $i++) {
            $randomString = RandomDataGeneratorHelper::randomString($entropy);

            if ($this->isUnique($entityClass, $propertyName, $randomString)) {
                return $randomString;
            }
        }

        throw new RuntimeException('For loop should not have broken.');
    }
}

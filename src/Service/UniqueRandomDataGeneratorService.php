<?php

namespace App\Service;

use App\Helper\StringHelper;
use Doctrine\ORM\EntityManagerInterface;
use Exception;

/**
 * Class UniqueRandomDataGeneratorService
 *
 * Generates random unique data. Unique meaning it does not exist yet in database for given entity and property.
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
     * @param int $min
     * @param int $max
     * @return int
     * @throws Exception
     */
    public function generateUniqueRandomInteger(
        string $entityClass,
        string $propertyName,
        int $min = 0,
        int $max = 2147483647
    ): int
    {
        while (true) {
            $randomInt = random_int($min, $max);

            if ($this->isUnique($entityClass, $propertyName, $randomInt)) {
                return $randomInt;
            }
        }

        throw new Exception('While loop should not have broken');
    }

    /**
     * @param string $entityClass
     * @param string $propertyName
     * @param int $entropy
     * @return string
     * @throws Exception
     */
    public function generateUniqueRandomString(string $entityClass, string $propertyName, int $entropy = 512): string
    {
        while (true) {
            $randomString = StringHelper::generateRandomString($entropy);

            if ($this->isUnique($entityClass, $propertyName, $randomString)) {
                return $randomString;
            }
        }

        throw new Exception('While loop should not have broken');
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
}

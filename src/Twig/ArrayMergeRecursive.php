<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Class ArrayMergeRecursive
 * @package App\Twig
 */
class ArrayMergeRecursive extends AbstractExtension
{
    /**
     * @return array
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('array_merge_recursive', [$this, 'arrayMergeRecursive'])
        ];
    }

    /**
     * @param array $array1
     * @param array $array2
     * @return array
     */
    public function arrayMergeRecursive(array $array1, array $array2): array
    {
        return array_merge_recursive($array1, $array2);
    }
}

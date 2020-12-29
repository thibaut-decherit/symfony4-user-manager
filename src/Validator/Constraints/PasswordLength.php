<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Class PasswordLength
 * @package App\Validator\Constraints
 *
 * @Annotation
 */
class PasswordLength extends Constraint
{
    /**
     * @var string
     */
    public $message = '';

    /**
     * @return string
     */
    public function validateBy(): string
    {
        return get_class($this) . 'Validator';
    }

    /**
     * @param string $message
     */
    public function setMessage(string $message): void
    {
        $this->message = $message;
    }
}

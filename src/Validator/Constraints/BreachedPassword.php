<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Class BreachedPassword
 * @Annotation
 */
class BreachedPassword extends Constraint
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

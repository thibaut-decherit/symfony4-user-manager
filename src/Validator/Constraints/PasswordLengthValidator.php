<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class PasswordLengthValidator
 * @package App\Validator\Constraints
 *
 * Used instead of Symfony\Component\Validator\Constraints\Length to be able to use centralized min/max length
 * parameters (app.password_min_length and app.password_max_length in config/services.yaml)
 */
class PasswordLengthValidator extends ConstraintValidator
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var int
     */
    private $minLength;

    /**
     * @var int
     */
    private $maxLength;

    /**
     * PasswordLengthValidator constructor
     *
     * @param TranslatorInterface $translator
     * @param int $minLength
     * @param int $maxLength
     */
    public function __construct(TranslatorInterface $translator, int $minLength, int $maxLength)
    {
        $this->translator = $translator;
        $this->minLength = $minLength;
        $this->maxLength = $maxLength;
    }

    /**
     * @param mixed $plainPassword
     * @param Constraint $constraint
     */
    public function validate($plainPassword, Constraint $constraint): void
    {
        if (mb_strlen($plainPassword, 'UTF-8') < $this->minLength) {
            $constraint->message = $this->translator->trans(
                'form_errors.user.password_min_length',
                [
                    '{{ limit }}' => $this->minLength
                ],
                'validators'
            );
            $this->context->buildViolation($constraint->message)->addViolation();
        } elseif (mb_strlen($plainPassword, 'UTF-8') > $this->maxLength) {
            $constraint->message = $this->translator->trans(
                'form_errors.user.password_max_length',
                [
                    '{{ limit }}' => $this->maxLength
                ],
                'validators'
            );
            $this->context->buildViolation($constraint->message)->addViolation();
        }
    }
}

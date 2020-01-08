<?php

namespace App\Validator\Constraints;

use Exception;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class BreachedPasswordValidator extends ConstraintValidator
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * BreachedPasswordValidator constructor.
     * @param TranslatorInterface $translator
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * Calls haveibeenpwned API (see https://haveibeenpwned.com/API/v2#SearchingPwnedPasswordsByRange) to check if
     * password has been compromised in a data breach.
     *
     * How it works :
     *      - user password is hashed with SHA-1
     *      - the first 5 characters of the hash (prefix) are sent to the API
     *      - API returns SHA-1 hashes "suffixes" of exposed passwords beginning with the same 5 characters
     *      - API suffixes are compared to user password hash suffix with strpos()
     *      - constraint violation is added if a match is found
     *
     * @param mixed $plainPassword
     * @param Constraint $constraint
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function validate($plainPassword, Constraint $constraint): void
    {
        $plainPasswordSHA1 = strtoupper(sha1($plainPassword));
        $plainPasswordSHA1Prefix = substr($plainPasswordSHA1, 0, 5);
        $plainPasswordSHA1Suffix = substr($plainPasswordSHA1, 5);

        /*
         * Try catch to avoid HttpClient exceptions (e.g. if the API is unreachable HttpClient will throw 500
         * TransportException, without try catch it will crash the whole request and break the associated form)
         */
        try {
            $client = HttpClient::create();
            $response = $client->request(
                'GET',
                'https://api.pwnedpasswords.com/range/' . $plainPasswordSHA1Prefix
            );

            $breachedPasswordsSuffixes = $response->getContent();
        } catch (Exception $e) {
            $breachedPasswordsSuffixes = '';
        }

        // Constraint violation if hashes match (strpos returns an integer if there is a match and false otherwise)
        if (is_int(mb_strpos($breachedPasswordsSuffixes, $plainPasswordSHA1Suffix, 0, 'UTF-8'))) {
            $constraint->message = $this->translator->trans('form_errors.user.breached_password', [], 'validators');
            $this->context->buildViolation($constraint->message)->addViolation();
        }
    }
}

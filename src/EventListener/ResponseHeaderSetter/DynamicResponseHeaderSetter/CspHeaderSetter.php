<?php

namespace AppBundle\EventListener\ResponseHeaderSetter\DynamicResponseHeaderSetter;

use Exception;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\RouterInterface;

/**
 * Class CspHeaderSetter
 *
 * Adds Content Security Policy header to a response.
 * See https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Content-Security-Policy
 *
 * @package AppBundle\EventListener\ResponseHeaderSetter\DynamicResponseHeaderSetter
 */
class CspHeaderSetter
{
    /**
     * @var string
     */
    private $kernelEnvironment;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var ResponseHeaderBag
     */
    private $responseHeaders;

    /**
     * @var array
     */
    private $cspConfig;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var array
     */
    private $directives;

    /**
     * CspHeaderSetter constructor.
     * @param string $kernelEnvironment
     * @param RequestStack $requestStack
     * @param ResponseHeaderBag $responseHeaders
     * @param array $cspConfig
     * @param RouterInterface $router
     */
    public function __construct(
        string $kernelEnvironment,
        RequestStack $requestStack,
        ResponseHeaderBag $responseHeaders,
        array $cspConfig,
        RouterInterface $router
    )
    {
        $this->kernelEnvironment = $kernelEnvironment;
        $this->requestStack = $requestStack;
        $this->responseHeaders = $responseHeaders;
        $this->cspConfig = $cspConfig;
        $this->router = $router;
        $this->directives = [];
    }

    /**
     * @throws Exception
     */
    public function set(): void
    {
        $this->responseHeaders->set('Content-Security-Policy', $this->generate());
    }

    /**
     * Generates Content Security Policy header value.
     * See https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Content-Security-Policy
     * @return string
     * @throws Exception
     */
    private function generate(): string
    {
        $this->parseDirectivesConfig();

        $this->addReportUri();

        $this->addDevDirectivesIfDevEnvironment();

        $headerValue = '';

        $directives = $this->getDirectives();

        foreach ($directives as $directiveName => $directiveContent) {

            if (empty($directiveContent)) {
                throw new Exception("$directiveName: Directives cannot be empty");
            } elseif (!is_array($directiveContent)) {
                throw new Exception("$directiveName: Directives must be of type array");
            } elseif (in_array(null, $directiveContent) || in_array("", $directiveContent)) {
                throw new Exception("$directiveName: Directives cannot contain null or empty values");
            }

            $directiveContentString = implode(" ", $directiveContent);

            $directive = "$directiveName $directiveContentString; ";

            $headerValue .= $directive;
        }

        return $headerValue;
    }

    /**
     * Sets $this->directives with lax or strict directives, depending on strict_routes contents and requested route.
     * @throws Exception
     */
    private function parseDirectivesConfig(): void
    {
        // Routes where strict policy must be used.
        $strictRoutes = [];

        $hasStrictPolicy = false;

        if (!empty($this->cspConfig['strict_routes'])) {

            if (!is_array($this->cspConfig['strict_routes'])) {
                throw new Exception('content_security_policy.strict_routes parameter must be of type array');
            } elseif (empty($this->cspConfig['directives']['strict'])) {
                throw new Exception(
                    'content_security_policy.directives.strict: At least one strict directive must be defined'
                );
            }

            $strictRoutes = $this->cspConfig['strict_routes'];
            $hasStrictPolicy = true;
        }

        if (empty($this->cspConfig['strict_routes']) && !empty($this->cspConfig['directives']['strict'])) {
            throw new Exception(
                'content_security_policy.directives.strict: strict_routes must be defined and contain at least one route because directives.strict is defined and not empty'
            );
        }

        switch ($hasStrictPolicy) {
            case false:
                if (empty($this->cspConfig['directives']['lax'])) {
                    throw new Exception(
                        'content_security_policy.directives.lax: At least one lax directive must be defined'
                    );
                }

                $this->setDirectives($this->cspConfig['directives']['lax']);

                break;

            case true:
                $requestedRoute = $this->requestStack->getMasterRequest()->get('_route');

                if (in_array($requestedRoute, $strictRoutes)) {
                    $this->setDirectives($this->cspConfig['directives']['strict']);
                } else {

                    if (empty($this->cspConfig['directives']['lax'])) {
                        throw new Exception(
                            'content_security_policy.directives.lax: At least one lax directive must be defined'
                        );
                    }

                    $this->setDirectives($this->cspConfig['directives']['lax']);
                }

                break;
        }
    }

    /**
     * @throws Exception
     */
    private function addReportUri(): void
    {
        if (!isset($this->cspConfig['report_uri'])) {
            return;
        }

        if (empty($this->cspConfig['report_uri']['mode'])) {
            throw new Exception('report_uri.mode is undefined or empty');
        } elseif (empty($this->cspConfig['report_uri']['data'])) {
            throw new Exception('report_uri.data is undefined or empty');
        }

        $reportUri = '';

        switch ($this->cspConfig['report_uri']['mode']) {
            case 'plain':
                $reportUri = $this->cspConfig['report_uri']['data'];

                break;

            case 'match':
                $reportUri = $this->router->generate($this->cspConfig['report_uri']['data']);

                break;

            default:
                throw new Exception("report_uri.mode must be of type string and contain 'plain' or 'match'");

                break;
        }

        $directives = $this->getDirectives();

        $directives['report-uri'][] = $reportUri;

        $this->setDirectives($directives);
    }

    /**
     * Adds dev only directives if app is running in dev environment.
     */
    private function addDevDirectivesIfDevEnvironment(): void
    {
        if ($this->kernelEnvironment !== 'dev') {
            return;
        }

        $directives = $this->getDirectives();

        /*
         * In dev env 'self' === http://localhost:port, NOT 127.0.0.1. You need to whitelist this IP if you dev at
         * http://127.0.0.1:port and not at http://localhost:port.
         */
        $baseUrl = $this->requestStack->getMasterRequest()->getSchemeAndHttpHost();

        $scriptSrcDevDirectiveContent = [
            $baseUrl,
            "'unsafe-eval'",
            "'unsafe-inline'"
        ];

        $styleSrcDevDirectiveContent = [
            $baseUrl,
            "'unsafe-inline'"
        ];

        $directives['connect-src'][] = $baseUrl;
        $directives['font-src'][] = $baseUrl;
        $directives['form-action'][] = $baseUrl;

        /*
         * Allows Symfony Profiler to work properly as it relies on inline JS and CSS.
         * array_unique() prevents CSP duplicate source (e.g. 'unsafe-inline' is already in your script-src policy)
         * error on certain browsers (e.g. Firefox).
         */
        $directives['script-src'] = array_unique(array_merge($directives['script-src'], $scriptSrcDevDirectiveContent));
        $directives['style-src'] = array_unique(array_merge($directives['style-src'], $styleSrcDevDirectiveContent));

        $this->setDirectives($directives);
    }

    /**
     * @return array
     */
    private function getDirectives(): array
    {
        return $this->directives;
    }

    /**
     * @param array $directives
     * @return CspHeaderSetter
     */
    private function setDirectives(array $directives): CspHeaderSetter
    {
        $this->directives = $directives;

        return $this;
    }
}

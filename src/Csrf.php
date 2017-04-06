<?php
/**
 * Slim CSRF Protection
 *
 * @author  Riku Särkinen <riku@adbar.io>
 * @link    https://github.com/adbario/slim-csrf
 * @license https://github.com/adbario/slim-csrf/blob/master/LICENSE.md (MIT License)
 */
namespace Adbar\Slim;

use Adbar\Session;
use RuntimeException;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

/**
 * CSRF Protection
 *
 * This class creates and validates CSRF tokens. It should be injected in application
 * container, and also as a middleware if CSRF token check is needed on all routes.
 * Class supports Twig-View and PHP-View as a template engine, hidden HTML form input
 * field will be automatically created for them.
 */
class Csrf
{
    /** @var object View object */
    protected $view;

    /** @var object Session */
    protected $session;

    /** @var callable Token validation error callable */
    protected $tokenError;

    /** @var string Token validation error message */
    protected $errorMessage = 'Invalid security token.';

    /**
     * Constructor
     *
     * @param object $view View object
     */
    public function __construct(Session $session, $view = null)
    {
        $this->session = $session;
        $this->view = $view;
    }

    /**
     * Invoke middleware
     *
     * @param  Request  $request  PSR7 request
     * @param  Response $response PSR7 response
     * @param  callable $next     Next middleware
     * @return ResponseInterface
     */
    public function __invoke(Request $request, Response $response, callable $next)
    {
        // Check if session is started
        if (!$this->session->isActive()) {
             throw new RuntimeException('CSRF middleware failed. Session is not started.');
        }

        // Validate token
        if (in_array($request->getMethod(), ['POST', 'PUT', 'DELETE', 'PATCH'])) {
            $body = (array)$request->getParsedBody();
            $token = isset($body['csrf_token']) ? $body['csrf_token'] : false;
            if ($token === false || $this->validateToken($token) === false) {
                // Generate new token
                $request = $this->generateToken($request);

                // Create token error
                $tokenError = $this->getTokenError();

                return $tokenError($request, $response, $next);
            }
        }

        // Generate new token
        $request = $this->generateToken($request);

        // Generate HTML form input field for token
        $form = $this->generateForm();

        // Add token input field to views
        if (!is_null($this->view)) {
            if ($this->view instanceof \Slim\Views\Twig) {
                // Twig-View
                $this->view->getEnvironment()->addGlobal('csrf', $form);
            } elseif ($this->view instanceof \Slim\Views\PhpRenderer) {
                // PHP-View
                $this->view->addAttribute('csrf', $form);
            }
        }

        return $next($request, $response);
    }

    /**
     * Validate CSRF token
     *
     * @param  string $token CSRF token
     * @return bool
     */
    protected function validateToken($token)
    {
        return is_string($token) && $token === $this->session->getFrom('csrf', 'token', null);
    }

    /**
     * Generate new token and inject it to request
     *
     * @param  Request $request PSR7 request
     * @return PSR7 request
     */
    protected function generateToken(Request $request)
    {
        $token = bin2hex(random_bytes(16));
        $this->session->setTo('csrf', 'token', $token);
        $request = $request->withAttribute('csrf_token', $token);

        return $request;
    }

    /**
     * Generate HTML form input for CSRF token
     *
     * @return string
     */
    public function generateForm()
    {
        return '<input type="hidden" name="csrf_token" value="' . $this->session->getFrom('csrf', 'token') . '">';
    }

    /**
     * Generate token validation error
     *
     * @return callable
     */
    protected function getTokenError()
    {
        if (empty($this->tokenError)) {
            $this->tokenError = function (Request $request, Response $response, callable $next) {
                $body = new \Slim\Http\Body(fopen('php://temp', 'r+'));
                $body->write($this->errorMessage);
                return $response->withStatus(400)->withHeader('Content-type', 'text/plain')->withBody($body);
            };
        }
        return $this->tokenError;
    }

    /**
     * Set token validation error
     *
     * @param callable $tokenError Token validation error
     */
    public function setTokenError($tokenError)
    {
        $this->tokenError = $tokenError;
    }

    /**
     * Set token validation error message
     *
     * @param string $errorMessage Error message
     */
    public function setTokenErrorMessage($errorMessage)
    {
        $this->errorMessage = $errorMessage;
    }
}

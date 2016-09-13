# Slim CSRF Protection
Protection against CSRF in [Slim 3 framework](http://www.slimframework.com/).
Uses [Slim Secure Session Middleware](https://github.com/adbario/slim-secure-session-middleware) to manage session and 
automatically creates HTML form hidden input for [Twig-View](https://github.com/slimphp/Twig-View) and [PHP-View](https://github.com/slimphp/PHP-View).

CSRF protection will be applied to POST, PUT, DELETE and PATCH requests.

## Installation
    composer require adbario/slim-csrf

## Usage

### Depency Container
Inject CSRF protection in application container:

    $container['csrf'] = function () {
        return new \AdBar\Csrf;
    };

If you use Twig-View or PHP-View:

    $container['csrf'] = function ($c) {
        return new \AdBar\Csrf($c->view);
    };

### Middleware
CSRF protection needs Slim Secure Session Middleware.
[Inject settings](https://github.com/adbario/slim-secure-session-middleware) for session middleware and register it:
    
    $app->add(new \AdBar\SessionMiddleware(
        $app->getContainer(),
        $settings['session']
    ));

### Register for all routes
To use CSRF protection on all routes, register it as a middleware before session middleware:
    
    /** Csrf */
    $app->add($container->get('csrf'));
    
    /** Session */
    $app->add(new \AdBar\SessionMiddleware(
        $app->getContainer(),
        $settings['session']
    ));

### Register per route
To use CSRF protection on specific routes, add it like this:

    $app->get('/form', function ($request, $response) {
        // CSRF token will be added
        return $this->view->render($response, 'form.twig');
    })->add($container->get('csrf'));
    
    $app->post('/form', function ($request, $response) {
        // If CSRF token was valid, code after this will run
    })->add($container->get('csrf'));

### Twig-View
Ready-to-use HTML form hidden input will be injected in Twig-View, to use it in your view:

    <form method="post">
        {{ csrf|raw }}
        Username
        <input type="text" name="username">
        <input type="submit" value="Send">
    </form>

### PHP-View
Ready-to-use HTML form hidden input will be injected also in Twig-View, to use it in your view:

    <form method="post">
        <?= $csrf ?>
        Username
        <input type="text" name="username">
        <input type="submit" value="Send">
    </form>

### Other template engines
You can easily use CSRF protection on other template engines as well. Inject to container without view:
    
    $container['csrf'] = function () {
        return new \AdBar\Csrf;
    };
    
Generate HTML hidden input field:

    $app->get('/form', function ($request, $response) {
        // Generate form field
        $csrf = $this->csrf->generateForm();
        // Inject form field to your view...
    });

### Custom error on CSRF token failure
By default, CSRF protection shows simple message on failure:
    
    Invalid security token.
    
You can render a custom template if CSRF token isn't valid, edit container:

    $container['csrf'] = function ($c) {
        $csrf = new \AdBar\Csrf($c->view);
        $csrf->setTokenError(function ($request, $response, $next) use ($c) {
            return $c->view->render($response->withStatus(400), 'csrf_error.twig');
        });
        return $csrf;
    };

If you just want to edit simple message:
    
    $container['csrf'] = function ($c) {
        $csrf = new \AdBar\Csrf($c->view);
        $csrf->setTokenError(function ($request, $response, $next) use ($c) {
            $body = new \Slim\Http\Body(fopen('php://temp', 'r+'));
            $body->write('This is my custom error message.');
            return $response->withStatus(400)->withHeader('Content-type', 'text/plain')->withBody($body);
        });
        return $csrf;
    };

# Slim CSRF Protection
Protection against CSRF in [Slim 3 framework](http://www.slimframework.com/).
Uses [Slim Secure Session Middleware](https://github.com/adbario/slim-secure-session-middleware) to manage session and
automatically creates HTML form hidden input for [Twig-View](https://github.com/slimphp/Twig-View) and [PHP-View](https://github.com/slimphp/PHP-View).

CSRF protection will be applied to POST, PUT, DELETE and PATCH requests.

## Installation

```
composer require adbario/slim-csrf
```

## Usage

### Depency Container
Inject session helper to application container ([read more about session helper](https://github.com/adbario/slim-secure-session-middleware)):
```php
$container['session'] = function ($container) {
    return new \Adbar\Session(
        $container->get('settings')['session']['namespace']
    );
};
```

Inject CSRF protection in application container:

```php
$container['csrf'] = function ($c) {
    return new \Adbar\Slim\Csrf($c->get('session'));
};
```

If you use Twig-View or PHP-View:

```php
$container['csrf'] = function ($c) {
    return new \Adbar\Slim\Csrf(
        $c->get('session'),
        $c->get('view')
    );
};
```

### Other dependencies
CSRF protection needs Slim Secure Session Middleware.
[Inject settings](https://github.com/adbario/slim-secure-session-middleware) for session middleware and register it:

```php
$app->add(new \Adbar\SessionMiddleware($container->get('settings')['session']));
```

### Register for all routes
To use CSRF protection on all routes, register it as a middleware before session middleware:

```php
/** Csrf */
$app->add($app->getContainer()->get('csrf'));

/** Session */
$app->add(new \Adbar\SessionMiddleware($container->get('settings')['session']));
```

### Register per route
To use CSRF protection on specific routes, add it like this:

```php
$app->get('/form', function ($request, $response) {
    // CSRF token will be added
    return $this->view->render($response, 'form.twig');
})->add($container->get('csrf'));

$app->post('/form', function ($request, $response) {
    // If CSRF token was valid, code after this will run
})->add($container->get('csrf'));
```

### Twig-View
Ready-to-use HTML form hidden input will be injected in Twig-View, to use it in your view:

```twig
<form method="post">
    {{ csrf|raw }}
    Username
    <input type="text" name="username">
    <input type="submit" value="Send">
</form>
```

### PHP-View
Ready-to-use HTML form hidden input will be injected also in Twig-View, to use it in your view:

```php
<form method="post">
    <?= $csrf ?>
    Username
    <input type="text" name="username">
    <input type="submit" value="Send">
</form>
```

### Other template engines
You can easily use CSRF protection on other template engines as well. Inject to container without view:

```php
$container['csrf'] = function () {
    return new \Adbar\Slim\Csrf;
};
```

Generate HTML hidden input field:

```php
$app->get('/form', function ($request, $response) {
    // Generate form field
    $csrf = $this->csrf->generateForm();
    // Inject form field to your view...
});
```

### Custom error on CSRF token failure
By default, CSRF protection shows simple message on failure:

```
Invalid security token.
```

You can render a custom template if CSRF token isn't valid, edit container:

```php
$container['csrf'] = function ($c) {
    $csrf = new \Adbar\Slim\Csrf(
        $c->get('session'),
        $c->get('view')
    );
    $csrf->setTokenError(function ($request, $response, $next) use ($c) {
        return $c->view->render($response->withStatus(400), 'csrf_error.twig');
    });
    return $csrf;
};
```

If you just want to edit simple message:

```php
$container['csrf'] = function ($c) {
    $csrf = new \Adbar\Slim\Csrf(
        $c->get('session'),
        $c->get('view')
    );
    $csrf->setTokenErrorMessage('This is my custom error message.');
    return $csrf;
};
```

## License

[MIT license](LICENSE.md)

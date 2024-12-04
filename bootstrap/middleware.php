<?php

use Slim\App;
use Slim\Middleware\MethodOverrideMiddleware;
use Slim\Views\TwigMiddleware;
use Plyn\Core\ErrorRenderer;
use Plyn\Core\TwigCsrfExtension;
use Twig\Extension\DebugExtension;

return function (App $app) {
    // Разбор json, данных формы и xml
    $app->addBodyParsingMiddleware();

    // Добавляем маршрутизацию
    $app->addRoutingMiddleware();

    // Добавляем переопределение метода (PUT, DELETE и т.д.)
    $app->add(new MethodOverrideMiddleware());

    // Обработка исключений
    $app->addErrorMiddleware(true, true, true)
        ->getDefaultErrorHandler()
        ->registerErrorRenderer('text/html', new ErrorRenderer($app));

    // Добавляем сессии и флеш сообщения из них
    $app->add(
        function ($request, $next) {
            // Изменяем хранилище флэш-сообщений
            $this->get('flash')->__construct($_SESSION);

            return $next->handle($request);
        }
    );
    // Добавляем csrf
    $app->add('csrf');
    // Добавляем расширения для Twig
    $app->add(
        function ($request, $next) {
            $this->get('view')->addExtension(new TwigCsrfExtension($this->get('csrf')));
            $this->get('view')->addExtension(new DebugExtension());
            return $next->handle($request);
        }
    );

    // Добавляем промежуточное программное обеспечение базовой аутентификации HTTP.
    $app->add('auth');


    // Добавляем twig
    $app->add(
        TwigMiddleware::createFromContainer($app)
    );
};

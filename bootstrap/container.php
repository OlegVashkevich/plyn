<?php

use Psr\Container\ContainerInterface;
use Slim\Factory\AppFactory;
use Slim\Views\Twig;
use Slim\Csrf\Guard;
use Slim\Flash\Messages;
use Tuupola\Middleware\HttpBasicAuthentication;

return [
    //общие настройки и переменные приложения
    'settings' => function () {
        return require __DIR__ . '/settings.php';
    },

    //приложение
    'app' => function (ContainerInterface $container) {
        $app = AppFactory::createFromContainer($container);
        // регистрируем маршруты
        (require __DIR__ . '/routes.php')($app);
        // регистрируем промежуточный слой
        (require __DIR__ . '/middleware.php')($app);

        return $app;
    },

    //шаблонизатор
    'view' => function (ContainerInterface $container) {
        return Twig::create(
            __DIR__ . '/../views',
            [
                'cache' => false,//__DIR__ . '/../storage/cache',
                'debug' => false
            ]
        );
    },

    //csrf - защита от межсайтовой подделки запроса
    'csrf' => function (ContainerInterface $container) {
        $responseFactory = $container->get('app')->getResponseFactory();
        return new Guard($responseFactory);
    },

    //flash сообщения
    'flash' => function () {
        $storage = [];
        return new Messages($storage);
    },

    //базовая авторизация для админки
    'auth' => function (ContainerInterface $container) {
        return new HttpBasicAuthentication([
          'path' => ['/admin'],
          "realm" => "Protected",
          "secure" => false,
          'users' => $container->get('settings')['users']
        ]);
    },
];

<?php

use Slim\App;
use Slim\Routing\RouteCollectorProxy;
use Plyn\Controllers\Site;
use Plyn\Controllers\Admin;
use Plyn\Controllers\Api;

return function (App $app) {

    /**
     * Маршруты для административного раздела
     */

    // Пользователям необходимо пройти аутентификацию с
    // помощью промежуточного программного обеспечения базовой аутентификации HTTP.
    $app->group('/admin', function (RouteCollectorProxy $admingroup) {

        $admingroup->get('[/]', Admin::class . ':main')->setName('admin');

        // Маршруты для сущностей
        $admingroup->group('/{beantype}', function (RouteCollectorProxy $beantypegroup) {
            // Список
            $beantypegroup->get('[/]', Admin::class . ':listbeans')->setName('listbeans');

            // Форма добавления
            $beantypegroup->get('/add', Admin::class . ':addbean')->setName('addbean');

            // Просмотр
            $beantypegroup->get('/{id}', Admin::class . ':getbean')->setName('getbean');

            // Создание
            $beantypegroup->post('[/]', Admin::class . ':postbean')->setName('postbean');

            // Обновление
            $beantypegroup->put('/{id}', Admin::class . ':putbean')->setName('putbean');

            // Удаление
            $beantypegroup->delete('/{id}', Admin::class . ':deletebean')->setName('deletebean');
        });
    });


    /**
     * Маршруты для общего раздела.
     *
     */
    // Главная
    $app->get('/', Site::class . ':main');

    // Поиск
    $app->get('/book/search', Site::class . ':search');

    // Просмотр статьи
    $app->get('/book/{slug}', Site::class . ':book')->setName('book');


    /**
     * The JSON API routes.
     */

    // Users need to authenticate with HTTP Basic Authentication middleware
    $app->group('/api', function (RouteCollectorProxy $apigroup) {

        // Read requires no authentication
        $apigroup->group('/read', function (RouteCollectorProxy $apiread) {

            $apiread->get('[/]', Api::class . ':readmain');

            // Route of a certain type of bean
            $apiread->group('/{beantype}', function (RouteCollectorProxy $apireadtype) {

                // List
                $apireadtype->get('[/]', Api::class . ':readtype');

                // Existing bean
                $apireadtype->get('/{id}', Api::class . ':readone');
            });
        });

        // Write does require HTTP Basic Authentication middleware authentication, set in index.phph
        $apigroup->group('/write', function (RouteCollectorProxy $apiwrite) {

            // Route of a certian type of bean
            $apiwrite->group('/{beantype}', function (RouteCollectorProxy $apiwritetype) {

                // Add
                $apiwritetype->post('[/]', Api::class . 'Api:add');

                // Update
                $apiwritetype->put('/{id}', Api::class . 'Api:update');

                // Delete
                $apiwritetype->delete('/{id}', Api::class . 'Api:delete');
            });
        });
    });

     /**
     * Статические страницы.
     *
     */

    $app->get('/{slug}', Site::class . ':staticpage');
};

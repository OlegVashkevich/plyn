<?php

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;
use Plyn\Core\RouteHelper;
use Slim\Routing\RouteContext;
use Plyn\Core\Search;

return function (App $app) {

	/**
	 * Маршруты для административного раздела
	 */

	// Пользователям необходимо пройти аутентификацию с помощью промежуточного программного обеспечения базовой аутентификации HTTP.
	$app->group('/admin',  function (RouteCollectorProxy $admingroup) {

		$admingroup->get('[/]', '\Plyn\Controllers\Admin:main')->setName('admin');

		// Маршруты для сущностей
		$admingroup->group('/{beantype}', function (RouteCollectorProxy $beantypegroup) {
			// Список
			$beantypegroup->get('[/]', '\Plyn\Controllers\Admin:listbeans')->setName('listbeans');

			// Форма добавления
			$beantypegroup->get('/add', '\Plyn\Controllers\Admin:addbean')->setName('addbean');

			// Просмотр 
			$beantypegroup->get('/{id}', '\Plyn\Controllers\Admin:getbean')->setName('getbean');

			// Создание
			$beantypegroup->post('[/]', '\Plyn\Controllers\Admin:postbean')->setName('postbean');

			// Обновление
			$beantypegroup->put('/{id}', '\Plyn\Controllers\Admin:putbean')->setName('putbean');

			// Удаление
			$beantypegroup->delete('/{id}', '\Plyn\Controllers\Admin:deletebean')->setName('deletebean');
			
		});

	});


    /**
     * Маршруты для общего раздела.
     *
     */
	 // Главная
     $app->get('/', '\Plyn\Controllers\Site:main');

     // Поиск
     $app->get('/book/search', '\Plyn\Controllers\Site:search');
 
     // Просмотр статьи 
     $app->get('/book/{slug}', '\Plyn\Controllers\Site:book')->setName('book');


	/**
	 * The JSON API routes.
	 */

	// Users need to authenticate with HTTP Basic Authentication middleware
	$app->group('/api',  function (RouteCollectorProxy $apigroup) {

		// Read requires no authentication
		$apigroup->group('/read', function (RouteCollectorProxy $apiread) {

			$apiread->get('[/]', '\Plyn\Controllers\Api:readmain');

			// Route of a certain type of bean
			$apiread->group('/{beantype}', function (RouteCollectorProxy $apireadtype) {

				// List
				$apireadtype->get('[/]', '\Plyn\Controllers\Api:readtype');

				// Existing bean
				$apireadtype->get('/{id}', '\Plyn\Controllers\Api:readone');

			});

		});

		// Write does require HTTP Basic Authentication middleware authentication, set in index.phph
		$apigroup->group('/write', function (RouteCollectorProxy $apiwrite) {

			// Route of a certian type of bean
			$apiwrite->group('/{beantype}', function (RouteCollectorProxy $apiwritetype) {

				// Add
				$apiwritetype->post('[/]', '\Plyn\Controllers\Api:add');

				// Update
				$apiwritetype->put('/{id}', '\Plyn\Controllers\Api:update');

				// Delete
				$apiwritetype->delete('/{id}', '\Plyn\Controllers\Api:delete');

			});

		});

	});

     /**
     * Статические страницы.
     *
     */

	 $app->get('/{slug}', '\Plyn\Controllers\Site:staticpage');
};

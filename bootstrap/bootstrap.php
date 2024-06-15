<?php

use DI\ContainerBuilder;
use Slim\App;
use RedBeanPHP\R as R;


// Автозагрузчик composer
require __DIR__.'/../vendor/autoload.php';

// Подключение RedBean ORM базы данных, используется статично без контейнера
R::setup(
	/*'mysql:host='.$db_config['servername'].';dbname='.$db_config['database'],
	$db_config['username'],
	$db_config['password']*/
	'sqlite:'.__DIR__.'/../storage/db/sqlite.db'
);


// запускаем сессию
if (session_status() !== PHP_SESSION_ACTIVE) {
	session_start();
}

//включить боевой режим 
//R::freeze(true); 

/**
 * Автозагрузчик для Плыни
 *
 * Загружаем модели Плыни
 *
 * @param string $class_name Имя класса для загрузки.
 */
/*function plynAutoload($class_name) {

	// Загружаем модели и контроллеры
	$paths = array(
		'/models/',
		'/models/plyn/',
		'/controllers/'
	);

	foreach ($paths as $path) {
		// Обработка обратных косых черт в пространствах имен
		if ( strpos( $class_name, '\\' ) ) {
			$file = __DIR__.'/..'.$path.substr( $class_name, strrpos( $class_name, '\\' )+1 ).'.php';
		} else {
			$file = __DIR__.'/..'.$path.$class_name.'.php';
		}
		if (file_exists($file)) {
			require_once $file;
			return;
		}
	}
}
spl_autoload_register('plynAutoload');

session_start();*/

// Создание экземпляра DI-контейнера
$container = (new ContainerBuilder())
    ->addDefinitions(__DIR__ . '/container.php')
    ->build();

// Создаем экземпляр приложения
return $container->get('app');
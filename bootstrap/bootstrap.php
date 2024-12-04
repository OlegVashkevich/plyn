<?php

use RedBeanPHP\R as R;
use DI\ContainerBuilder;

// Автозагрузчик composer
require __DIR__ . '/../vendor/autoload.php';

// Подключение RedBean ORM базы данных, используется статично без контейнера
R::setup(
    /*'mysql:host='.$db_config['servername'].';dbname='.$db_config['database'],
    $db_config['username'],
    $db_config['password']*/
    'sqlite:' . __DIR__ . '/../storage/db/sqlite.db'
);


// запускаем сессию
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Создание экземпляра DI-контейнера
$containerBuilder = new ContainerBuilder();
//включить боевой режим
//R::freeze(true);
//$containerBuilder->enableCompilation(__DIR__ . '/../storage/cache/di');
$containerBuilder->addDefinitions(__DIR__ . '/container.php');
$container = $containerBuilder->build();

// Создаем экземпляр приложения
return $container->get('app');

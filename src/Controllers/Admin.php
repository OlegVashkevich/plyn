<?php

namespace Plyn\Controllers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Container\ContainerInterface;
use RedBeanPHP\R as R;
use Plyn\Core\EntityProvider;
use Slim\Routing\RouteContext;
use Plyn\Core\Search;

class Admin
{
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Перенаправление на нужную страницу после сохранения bean-компонента.
     *
     * @var object $container Slim контейнер
     * @var object $bean RedBean bean
     * @var array $data Post data
     * @var object $response объект ответа Slim
     * @var string[] $args Массив с аргументами из маршрута Slim
     *
     * @return object Slim ответ
     */
    private function redirectAfterSave($request, $bean, $data, $response, $args)
    {
        if ($data['submit'] == 'saveandclose') {
            return $response->withStatus(302)->withHeader(
                'Location',
                RouteContext::fromRequest($request)->getRouteParser()
                    ->urlFor('listbeans', [ 'beantype' => $args['beantype'] ])
            );
        } else {
            return $response->withStatus(302)->withHeader(
                'Location',
                RouteContext::fromRequest($request)
                    ->getRouteParser()->urlFor('getbean', [ 'beantype' => $args['beantype'], 'id' => $bean->id ])
            );
        }
    }

    public function main(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $beantypes = EntityProvider::getBeantypes($this->container->get('settings')['path'] . '/src', 'Example');

        foreach ($beantypes as $beantype) {
            $dashboard[$beantype]['name'] = $beantype;
            $dashboard[$beantype]['total'] = R::count($beantype);
            $dashboard[$beantype]['created'] = R::findOne($beantype, ' ORDER BY created DESC ');
            $dashboard[$beantype]['modified'] = R::findOne($beantype, ' ORDER BY modified DESC ');

            $c = EntityProvider::setupBeanModel($this->container
                ->get('settings')['path'] . '/src', 'Example', $beantype);
            $dashboard[$beantype]['description'] = $c->description;
        }

        return $this->container->get('view')->render($response, 'admin/index.html', [
            'dashboard' => $dashboard,
            'beantypes' => $beantypes
        ]);
    }

    public function listbeans(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface {
        $data['flash'] = $this->container->get('flash')->getMessages();

        try {
            $c = EntityProvider::setupBeanModel($this->container
                ->get('settings')['path'] . '/src', 'Example', $args['beantype']);

            $data['beantype'] = $args['beantype'];
            $data['description'] = $c->description;
            $data['beantypes'] = EntityProvider::getBeantypes($this->container
                ->get('settings')['path'] . '/src', 'Example');
            $data['properties'] = $c->properties;

            $query = $request->getQueryParams();

            foreach ($c->properties as $property) {
                // Сортировать «абсолютные» позиции, не связанные с родительским элементом manytoone.
                if ($property['type'] === '\\Plyn\\Property\\Position' && !isset($property['manytoone'])) {
                    // Устанавливаем сортировку по умолчанию
                    if (!isset($query['sort'])) {
                        $query['sort'] = $property['name'] . '*asc';
                    }

                    // Устанавливаем сортировку в веб-интерфейсе
                    $data['position'] = $property;

                    break;
                }
            }

            // Устанавливаем сортировку по умолчанию, если она еще не установлена
            if (!isset($query['sort'])) {
                $query['sort'] = 'title*asc';
            }

            // Поиск
            $search = new Search('Example', $args['beantype']);
            $data['search'] = $search->find($query);

            if (isset($request->getQueryParams()['*has']) && $request->getQueryParams()['*has']) {
                // Вывод в заголовке, требуется работа над совместимостью со всеми видами поисковых запросов
                $data['query'] = $request->getQueryParams()['*has'];
            }
        } catch (\Exception $e) {
            $data['flash']['error'][] = $e->getMessage();
        }

        // Показываем список элементов
        return $this->container->get('view')->render($response, 'admin/beans.html', $data);
    }

    public function addbean(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface {
        $c = EntityProvider::setupBeanModel($this->container
            ->get('settings')['path'] . '/src', 'Example', $args['beantype']);
        $c->populateProperties();
        // Показываем форму
        return $this->container->get('view')->render($response, 'admin/bean.html', [
            'method' => 'POST',
            'beantype' => $args['beantype'],
            'beanproperties' => $c->properties,
            'flash' => $this->container->get('flash')->getMessages(),
            'beantypes' => EntityProvider::getBeantypes($this->container
                ->get('settings')['path'] . '/src', 'Example')
        ]);
    }

    public function getbean(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface {
        $c = EntityProvider::setupBeanModel($this->container
            ->get('settings')['path'] . '/src', 'Example', $args['beantype']);
        $c->populateProperties($args['id']);
        // Показываем заполненную форму
        return $this->container->get('view')->render($response, 'admin/bean.html', [
            'method' => 'PUT',
            'beantype' => $args['beantype'],
            'beanproperties' => $c->properties,
            'bean' => $c->read($args['id']),
            'flash' => $this->container->get('flash')->getMessages(),
            'beantypes' => EntityProvider::getBeantypes($this->container->get('settings')['path'] . '/src', 'Example')
        ]);
    }

    public function postbean(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface {
        $c = EntityProvider::setupBeanModel($this->container
            ->get('settings')['path'] . '/src', 'Example', $args['beantype']);
        $data = $request->getParsedBody();
        try {
            $bean = $c->create($data);
            // Перенаправление к обзору или заполненной форме
            $this->container->get('flash')->addMessage('success', $bean->title . ' is added.');
            return $this->redirectAfterSave($request, $bean, $data, $response, $args);
        } catch (\Exception $e) {
            $this->container->get('flash')->addMessage('error', $e->getMessage());
            return $response->withStatus(302)->withHeader(
                'Location',
                RouteContext::fromRequest($request)
                    ->getRouteParser()->urlFor('addbean', [ 'beantype' => $args['beantype']])
            );
        }
    }

    public function putbean(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface {
        $c = EntityProvider::setupBeanModel($this->container
            ->get('settings')['path'] . '/src', 'Example', $args['beantype']);
        $data = $request->getParsedBody();
        try {
            $bean = $c->update($data, $args['id']);

            // Перенаправление к обзору или заполненной форме
            $this->container->get('flash')->addMessage('success', $bean->title . ' is updated.');
            return $this->redirectAfterSave($request, $bean, $data, $response, $args);
        } catch (\Exception $e) {
            $this->container->get('flash')->addMessage('error', $e->getMessage());
            return $response->withStatus(302)->withHeader(
                'Location',
                RouteContext::fromRequest($request)->getRouteParser()
                    ->urlFor('getbean', [ 'beantype' => $args['beantype'], 'id' => $args['id'] ])
            );
        }
    }

    public function deletebean(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface {
        $c = EntityProvider::setupBeanModel($this->container
            ->get('settings')['path'] . '/src', 'Example', $args['beantype']);
        try {
            $c->delete($args['id']);
            $this->container->get('flash')->addMessage('success', 'The ' . $args['beantype'] . ' is deleted.');
        } catch (\Exception $e) {
            $this->container->get('flash')->addMessage('error', $e->getMessage());
        }
        return $response->withStatus(302)->withHeader(
            'Location',
            RouteContext::fromRequest($request)
                ->getRouteParser()->urlFor('listbeans', [ 'beantype' => $args['beantype']])
        );
    }
}

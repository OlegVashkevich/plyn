<?php

namespace Plyn\Controllers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as SRequestInterface;
use Psr\Container\ContainerInterface;
use RedBeanPHP\R as R;
use Plyn\Core\EntityProvider;
use Plyn\Core\Search;

class Api
{
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function setResponse(ResponseInterface $response, int $code, mixed $message, array $data): ResponseInterface
    {

        $return = [];

        if (!$code) {
            $code = 200;
        }
        $return['meta']['code'] = $code;

        if ($message) {
            $return['meta']['message'] = $message;
        }

        if (!$data) {
            $data = [];
        }

        $return['response'] = $data;
        $response->getBody()->write(json_encode($data));

        return $response->withHeader('Content-Type', 'application/json');
    }

    public function readmain(SRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $data = [
            'beantypes' => EntityProvider::getBeantypes($this->container->get('settings')['path'] . '/src', 'Example')
        ];
        return $this->setResponse($response, 200, false, $data);
    }

    public function readtype(SRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        try {
            $c = EntityProvider::setupBeanModel($this->container
                ->get('settings')['path'] . '/src', 'Example', $args['beantype']);
            if (isset($args['id'])) {
                $c->populateProperties($args['id']);
            }

            $data = [
                'beantype' => $args['beantype'],
                'beanproperties' => $c->properties
            ];

            // Search
            $search = new Search('Example', $args['beantype']);
            $data['search'] = $search->find($request->getQueryParams());
            //R::exportAll( $search->find( $request->getQueryParams() ) );

            return $this->setResponse($response, 200, false, $data);
        } catch (\Exception $e) {
            // Error
            return $this->setResponse($response, 400, $e->getMessage(), []);
        }
    }

    public function readone(SRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $c = EntityProvider::setupBeanModel($this->container
            ->get('settings')['path'] . '/src', 'Example', $args['beantype']);
        $c->populateProperties($args['id']);

        $data = [
            'beantype' => $args['beantype'],
            'beanproperties' => $c->properties,
            'bean' => R::exportAll($c->read($args['id'])) // Используем exportAll для отображения связанных компонентов
        ];

        return $this->setResponse($response, 200, false, $data);
    }

    public function add(SRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        try {
            $c = EntityProvider::setupBeanModel($this->container
                ->get('settings')['path'] . '/src', 'Example', $args['beantype']);
            $input = $request->getParsedBody();
            $bean = $c->create($input);
            $data = [
                'beantype' => $args['beantype'],
                'beanproperties' => $c->properties,
                'bean' => R::exportAll($bean) // Используем exportAll для отображения связанных компонентов
            ];
            return $this->setResponse($response, 200, $bean->title . ' added', $data);
        } catch (\Exception $e) {
            // Error
            return $this->setResponse($response, 400, $e->getMessage(), []);
        }
    }

    public function update(SRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        try {
            $c = EntityProvider::setupBeanModel($this->container
                ->get('settings')['path'] . '/src', 'Example', $args['beantype']);
            $input = $request->getParsedBody();
            $bean = $c->update($input, $args['id']);
            $data = [
                'beantype' => $args['beantype'],
                'beanproperties' => $c->properties,
                'bean' => R::exportAll($bean) // Используем exportAll для отображения связанных компонентов
            ];
            return $this->setResponse($response, 200, $bean->title . ' updated', $data);
        } catch (\Exception $e) {
            // Error
            return $this->setResponse($response, 400, $e->getMessage(), []);
        }
    }

    public function delete(SRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        try {
            $c = EntityProvider::setupBeanModel($this->container
                ->get('settings')['path'] . '/src', 'Example', $args['beantype']);
            $c->delete($args['id']);
            return $this->setResponse($response, 200, $args['beantype'] . ' deleted', []);
        } catch (\Exception $e) {
            // Error
            return $this->setResponse($response, 400, $e->getMessage(), []);
        }
    }
}

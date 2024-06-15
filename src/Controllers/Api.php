<?php

namespace Plyn\Controllers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Container\ContainerInterface;
use RedBeanPHP\R as R;
use Plyn\Core\RouteHelper;
use Slim\Routing\RouteContext;
use Plyn\Core\Search;

class Api
{
    private $container;
 
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Create JSON response
     *
     * @var object $response Slim response object.
     * @var string $code Error code.
     * @var string $message Response message.
     * @var array $data Data of beantype and one or more beans.
     *
     * @return string JSON response.
     */
    function setResponse($response, $code, $message, $data) {

        $return = [];

        if ( !$code ) $code = 200;
        $return['meta']['code'] = $code;

        if ( $message ) $return['meta']['message'] = $message;

        if ( !$data ) $data = [];

        $return['response'] = $data;
        $response->getBody()->write(json_encode($data));

        return $response->withHeader('Content-Type', 'application/json');

    }

    public function readmain(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $data = [
            'beantypes' => RouteHelper::getBeantypes($this->container->get('settings')['path'].'/src', 'Example')
        ];
        return $this->setResponse($response, 200, false, $data);
    }

    public function readtype(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        try {

            $c = RouteHelper::setupBeanModel( $this->container->get('settings')['path'].'/src', 'Example', $args['beantype'] );
            if(isset($args['id']))
                $c->populateProperties( $args['id'] );

            $data = [
                'beantype' => $args['beantype'],
                'beanproperties' => $c->properties
            ];

            // Search
            $search = new \Plyn\Core\Search( 'Example', $args['beantype'] );
            $data['search'] = $search->find( $request->getQueryParams() );//R::exportAll( $search->find( $request->getQueryParams() ) );

            return $this->setResponse($response, 200, false, $data);

        } catch (Exception $e) {

            // Error
            return $this->setResponse($response, 400, $e->getMessage(), []);

        }
    }

    public function readone(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $c = RouteHelper::setupBeanModel( $this->container->get('settings')['path'].'/src', 'Example', $args['beantype'] );
        $c->populateProperties( $args['id'] );

        $data = [
            'beantype' => $args['beantype'],
            'beanproperties' => $c->properties,
            'bean' => R::exportAll( $c->read( $args['id'] ) ) // Use exportAll to show related beans
        ];

        return $this->setResponse($response, 200, false, $data);
    }

    public function add(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        try {

            $c = RouteHelper::setupBeanModel( $this->container->get('settings')['path'].'/src', 'Example', $args['beantype'] );
            $input = $request->getParsedBody();
            $bean = $c->create( $input );
            $data = [
                'beantype' => $args['beantype'],
                'beanproperties' => $c->properties,
                'bean' => R::exportAll( $bean ) // Use exportAll to show related beans
            ];
            return $this->setResponse($response, 200, $bean->title.' added', $data);

        } catch (Exception $e) {

            // Error
            return $this->setResponse($response, 400, $e->getMessage(), []);

        }
    }

    public function update(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        try {

            $c = RouteHelper::setupBeanModel( $this->container->get('settings')['path'].'/src', 'Example', $args['beantype'] );
            $input = $request->getParsedBody();
            $bean = $c->update( $input , $args['id'] );
            $data = [
                'beantype' => $args['beantype'],
                'beanproperties' => $c->properties,
                'bean' => R::exportAll( $bean ) // Use exportAll to show related beans
            ];
            return $this->setResponse($response, 200, $bean->title.' updated', $data);

        } catch (Exception $e) {

            // Error
            return $this->setResponse($response, 400, $e->getMessage(), []);

        }
    }

    public function delete(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        try {

            $c = RouteHelper::setupBeanModel( $this->container->get('settings')['path'].'/src', 'Example', $args['beantype'] );
            $c->delete( $args['id'] );
            return $this->setResponse($response, 200, $args['beantype'].' deleted', []);

        } catch (Exception $e) {

            // Error
            return $this->setResponse($response, 400, $e->getMessage(), []);

        }
    }
}
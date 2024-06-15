<?php

namespace Plyn\Controllers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Container\ContainerInterface;
use RedBeanPHP\R as R;
use Plyn\Core\RouteHelper;
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
	 * Redirect to the right page after saving a bean.
	 *
	 * @var object $container Slim container
	 * @var object $bean RedBean bean
	 * @var array $data Post data
	 * @var object $response Slim response object
	 * @var string[] $args Array with arguments from the Slim route
	 *
	 * @return object Slim response
	 */
	private function redirectAfterSave($request, $bean, $data, $response, $args) {
		if ( $data['submit'] == 'saveandclose' ) {
			return $response->withStatus(302)->withHeader(
				'Location',
				RouteContext::fromRequest($request)->getRouteParser()->urlFor( 'listbeans', [ 'beantype' => $args['beantype'] ] )
			);
		} else {
			return $response->withStatus(302)->withHeader(
				'Location',
				RouteContext::fromRequest($request)->getRouteParser()->urlFor( 'getbean', [ 'beantype' => $args['beantype'], 'id' => $bean->id ] )
			);
		}
	}
 
    public function main(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $beantypes = RouteHelper::getBeantypes($this->container->get('settings')['path'].'/src', 'Example');

        foreach ($beantypes as $beantype) {
            $dashboard[$beantype]['name'] = $beantype;
            $dashboard[$beantype]['total'] = R::count( $beantype );
            $dashboard[$beantype]['created'] = R::findOne( $beantype, ' ORDER BY created DESC ' );
            $dashboard[$beantype]['modified'] = R::findOne( $beantype, ' ORDER BY modified DESC ' );

            $c = RouteHelper::setupBeanModel( $this->container->get('settings')['path'].'/src', 'Example', $beantype );
            $dashboard[$beantype]['description'] = $c->description;
        }

        return $this->container->get('view')->render( $response, 'admin/index.html', [
            'dashboard' => $dashboard,
            'beantypes' => $beantypes
        ] );
    }

    public function listbeans(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $data['flash'] = $this->container->get('flash')->getMessages();

        try {

            $c = RouteHelper::setupBeanModel(  $this->container->get('settings')['path'].'/src', 'Example', $args['beantype'] );

            $data['beantype'] = $args['beantype'];
            $data['description'] = $c->description;
            $data['beantypes'] = RouteHelper::getBeantypes($this->container->get('settings')['path'].'/src', 'Example');
            $data['properties'] = $c->properties;

            $query = $request->getQueryParams();

            foreach($c->properties as $property) {
                // Sort "absolute" positions, not related to manytoone parent.
                if ( $property['type'] === '\\Plyn\\Property\\Position' && !isset( $property['manytoone'] ) ) {

                    // Set default sorting
                    if ( !isset($query['sort']) ) {
                        $query['sort'] = $property['name'].'*asc';
                    }

                    // Set sorting in web interface
                    $data['position'] = $property;

                    break;
                }
            }

            // Set default sorting if not set yet
            if ( !isset($query['sort']) ) {
                $query['sort'] = 'title*asc';
            }

            // Search
            $search = new Search( 'Example', $args['beantype'] );
            $data['search'] = $search->find( $query );

            if ( isset($request->getQueryParams()['*has']) && $request->getQueryParams()['*has'] ) {
                $data['query'] = $request->getQueryParams()['*has']; // Output in title, needs work to work with all kinds of search queries
            }

        } catch (Exception $e) {
            $data['flash']['error'][] = $e->getMessage();
        }

        // Show list of items
        return $this->container->get('view')->render($response, 'admin/beans.html', $data);
    }

    public function addbean(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $c = RouteHelper::setupBeanModel( $this->container->get('settings')['path'].'/src', 'Example', $args['beantype'] );
        $c->populateProperties();
        // Show form
        return $this->container->get('view')->render($response, 'admin/bean.html', [
            'method' => 'POST',
            'beantype' => $args['beantype'],
            'beanproperties' => $c->properties,
            'flash' => $this->container->get('flash')->getMessages(),
            'beantypes' => RouteHelper::getBeantypes($this->container->get('settings')['path'].'/src', 'Example')
        ]);
    }

    public function getbean(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $c = RouteHelper::setupBeanModel( $this->container->get('settings')['path'].'/src', 'Example', $args['beantype'] );
        $c->populateProperties( $args['id'] );
        // Show populated form
        return $this->container->get('view')->render($response, 'admin/bean.html', [
            'method' => 'PUT',
            'beantype' => $args['beantype'],
            'beanproperties' => $c->properties,
            'bean' => $c->read( $args['id'] ),
            'flash' => $this->container->get('flash')->getMessages(),
            'beantypes' => RouteHelper::getBeantypes($this->container->get('settings')['path'].'/src', 'Example')
        ]);
    }

    public function postbean(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $c = RouteHelper::setupBeanModel( $this->container->get('settings')['path'].'/src', 'Example', $args['beantype'] );
        $data = $request->getParsedBody();
        try {
            $bean = $c->create( $data );
            
            // Redirect to overview or populated form
            $this->container->get('flash')->addMessage( 'success', $bean->title.' is added.' );
            return $this->redirectAfterSave($request, $bean, $data, $response, $args);
        } catch (Exception $e) {
            $this->container->get('flash')->addMessage( 'error', $e->getMessage() );
            return $response->withStatus(302)->withHeader(
                'Location',
                RouteContext::fromRequest($request)->getRouteParser()->urlFor( 'addbean', [ 'beantype' => $args['beantype'] ])
            );
        }  
    }

    public function putbean(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $c = RouteHelper::setupBeanModel( $this->container->get('settings')['path'].'/src', 'Example', $args['beantype'] );
        $data = $request->getParsedBody();
        try {
            $bean = $c->update( $data , $args['id'] );

            // Redirect to overview or populated form
            $this->container->get('flash')->addMessage( 'success', $bean->title.' is updated.' );
            return $this->redirectAfterSave($request, $bean, $data, $response, $args);
        } catch (Exception $e) {
            $this->container->get('flash')->addMessage( 'error', $e->getMessage() );
            return $response->withStatus(302)->withHeader(
                'Location',
                RouteContext::fromRequest($request)->getRouteParser()->urlFor( 'getbean', [ 'beantype' => $args['beantype'], 'id' => $args['id'] ] )
            );
        }
    }

    public function deletebean(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $c = RouteHelper::setupBeanModel( $this->container->get('settings')['path'].'/src', 'Example', $args['beantype'] );
				
        try {
            $c->delete( $args['id'] );
            $this->container->get('flash')->addMessage( 'success', 'The '.$args['beantype'].' is deleted.' );
        } catch (Exception $e) {
            $this->container->get('flash')->addMessage( 'error', $e->getMessage() );
        }
        return $response->withStatus(302)->withHeader(
            'Location',
            RouteContext::fromRequest($request)->getRouteParser()->urlFor( 'listbeans', [ 'beantype' => $args['beantype'] ])
        );
    }
}
 
<?php

namespace Plyn\Controllers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Container\ContainerInterface;

class Site
{
    private $container;
 
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function main(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $book = new \Plyn\Models\Example\Book;
        // показать список книг
        return $this->container->get('view')->render(
            $response, 
            'public/index.html', 
            [ 'books' => $book->read() ]
        );
    }

    public function book(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $book = new \Plyn\Models\Example\Book;

        return $this->container->get('view')->render(
            $response, 
            'public/book.html',
            [ 'book' => $book->read( $args['slug'], 'slug' ) ]
        );
    }

    public function search(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $search = new \Plyn\Core\Search('Example', 'book');
        return $this->container->get('view')->render(
            $response, 
            'public/search.html', 
            [
                'search' => $search->find( $request->getQueryParams() ),
                'query' => $request->getQueryParams()['*has']
            ]
        );
    }

    public function staticpage(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $slug = str_replace(array('../','./'), '', $args['slug']); // remove parent path components if request is trying to be sneaky
	
        if (file_exists( $this->container->get('view')->getLoader()->getPaths()[0] .'/static/'.$slug.'.html')) {
            return $this->container->get('view')->render($response, 'static/'.$slug.'.html');
        } 
    }
}
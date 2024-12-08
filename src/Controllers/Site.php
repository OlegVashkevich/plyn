<?php

namespace Plyn\Controllers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as SRequestInterface;
use Psr\Container\ContainerInterface;
use Plyn\Core\Search;
use Plyn\Models\Example\Book;

class Site
{
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function main(SRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $book = new Book();

        // показать список книг
        return $this->container->get('view')->render(
            $response,
            'public/index.html',
            [ 'books' => $book->read() ]
        );
    }

    public function book(SRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $book = new Book();

        return $this->container->get('view')->render(
            $response,
            'public/book.html',
            [ 'book' => $book->read($args['slug'], 'slug') ]
        );
    }

    public function search(SRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $search = new Search('Example', 'book');
        return $this->container->get('view')->render(
            $response,
            'public/search.html',
            [
                'search' => $search->find($request->getQueryParams()),
                'query' => $request->getQueryParams()['*has']
            ]
        );
    }

    public function staticpage(SRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        // удаляем компоненты родительского пути, если запрос пытается быть скрытным
        $slug = str_replace(array('../','./'), '', $args['slug']);

        if (file_exists($this->container->get('view')->getLoader()->getPaths()[0] . '/static/' . $slug . '.html')) {
            return $this->container->get('view')->render($response, 'static/' . $slug . '.html');
        }
    }
}

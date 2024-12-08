<?php

namespace Plyn\Core;

use Slim\Interfaces\ErrorRendererInterface;
use Throwable;
use Slim\App;
use Slim\Views\Twig;
use Psr\Http\Message\ResponseFactoryInterface;

class ErrorRenderer implements ErrorRendererInterface
{
    private Twig $twig;
    private ResponseFactoryInterface $responseFactory;

    public function __construct(App $app)
    {
        $this->twig = $app->getContainer()->get('view');
        $this->responseFactory = $app->getResponseFactory();
    }

    public function __invoke(Throwable $exception, bool $displayErrorDetails): string
    {
        $viewData = [
            'error' => [
                'message' => $exception->getMessage() ,
                'code' => $exception->getCode(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTrace(),
            ],
        ];

        $response = $this->responseFactory->createResponse();

        if ($exception->getCode() == 404) {
            $result = (string)$this->twig->render($response, 'static/404.html', $viewData)->getBody();
        } else {
            $result = (string)$this->twig->render($response, 'static/error.html', $viewData)->getBody();
        }

        return $result;
    }
}

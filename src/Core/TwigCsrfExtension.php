<?php

namespace Plyn\Core;

use Slim\Csrf\Guard;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

class TwigCsrfExtension extends AbstractExtension implements GlobalsInterface
{
    /**
     * @var Guard
     */
    protected $csrf;

    public function __construct(Guard $csrf)
    {
        $this->csrf = $csrf;
    }

    public function getGlobals(): array
    {
        // CSRF token name and value
        $csrfNameKey = $this->csrf->getTokenNameKey();
        $csrfValueKey = $this->csrf->getTokenValueKey();
        $csrfName = $this->csrf->getTokenName();
        $csrfValue = $this->csrf->getTokenValue();
        return [
            'csrf'   => [
                'keys' => [
                    'name'  => $csrfNameKey,
                    'value' => $csrfValueKey
                ],
                'name'  => $csrfName,
                'value' => $csrfValue
            ]
        ];
    }
}

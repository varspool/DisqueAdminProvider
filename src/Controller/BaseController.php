<?php

namespace Varspool\DisqueAdmin\Controller;

use Disque\Client;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Silex\Application\TwigTrait;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Twig_Environment;

abstract class BaseController implements LoggerAwareInterface
{
    use LoggerAwareTrait;
    use TwigTrait;

    public const TWIG_NAMESPACE = 'disque_admin';

    /**
     * @var Client
     */
    protected $disque;

    /**
     * @var Twig_Environment
     */
    protected $twig;

    public function __construct(Client $disque, Twig_Environment $twig)
    {
        $this->disque = $disque;
        $this->twig = $twig;
        $this->logger = new NullLogger();
    }

    /**
     * Ripped from TwigTrait
     *
     * @param               $view
     * @param array         $parameters
     * @param Response|null $response
     * @return Response
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    public function render($view, array $parameters = [], Response $response = null)
    {
        $twig = $this->twig;
        $view = '@' . self::TWIG_NAMESPACE . '/' . $view;

        if ($response instanceof StreamedResponse) {
            $response->setCallback(function () use ($twig, $view, $parameters) {
                $twig->display($view, $parameters);
            });
        } else {
            if (null === $response) {
                $response = new Response();
            }
            $response->setContent($twig->render($view, $parameters));
        }

        return $response;
    }
}
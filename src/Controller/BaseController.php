<?php

namespace Varspool\DisqueAdmin\Controller;

use Predisque\Client as PredisqueClient;
use Predisque\Client;
use Predisque\Connection\Aggregate\ClusterInterface;
use Predisque\Connection\Aggregate\DisqueCluster;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use RuntimeException;
use Silex\Application\TwigTrait;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Twig_Environment;
use Varspool\DisqueAdmin\FormatTrait;

abstract class BaseController implements LoggerAwareInterface
{
    use FormatTrait;
    use LoggerAwareTrait;
    use TwigTrait;

    public const TWIG_NAMESPACE = 'disque_admin';

    /**
     * @var callable
     */
    protected $disqueFactory;

    /**
     * @var Client
     */
    protected $disque;

    /**
     * @var Twig_Environment
     */
    protected $twig;

    /**
     * @var UrlGenerator
     */
    protected $url;

    public function __construct(callable $disqueFactory, Twig_Environment $twig, UrlGenerator $url)
    {
        $this->disqueFactory = $disqueFactory;
        $this->twig = $twig;
        $this->url = $url;
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

    public function redirect(string $url, int $status = 301, array $headers = [])
    {
        return new RedirectResponse($url, $status, $headers);
    }

    /**
     * @param Request $request
     * @return PredisqueClient
     */
    public function getDisque(Request $request): PredisqueClient
    {
        if (!$this->disque) {
            $factory = $this->disqueFactory;

            $this->disque = $factory(
                $request->attributes->get('prefix', null)
            );

            if (!$this->disque->getConnection() instanceof DisqueCluster) {
                throw new \RuntimeException('Expected to have a disque cluster connection');
            }
        }

        return $this->disque;
    }

    /**
     * @param Request $request
     * @return ClusterInterface
     */
    public function getDisqueConnection(Request $request): ClusterInterface
    {
        $connection = $this->getDisque($request)->getConnection();

        if (!$connection instanceof ClusterInterface) {
            throw new RuntimeException('Expected a ' . ClusterInterface::class . ' connection');
        }

        return $connection;
    }
}

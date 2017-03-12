<?php

namespace Varspool\DisqueAdmin;

use Disque\Connection\Credentials;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use RuntimeException;
use Silex\Api\BootableProviderInterface;
use Silex\Api\ControllerProviderInterface;
use Silex\Application;
use Silex\ControllerCollection;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Twig_Loader_Filesystem;
use Varspool\DisqueAdmin\Connection\Manager;
use Varspool\DisqueAdmin\Connection\NodePrioritizer;
use Varspool\DisqueAdmin\Controller\BaseController;
use Varspool\DisqueAdmin\Controller\JobController;
use Varspool\DisqueAdmin\Controller\NodeController;
use Varspool\DisqueAdmin\Controller\OverviewController;
use Varspool\DisqueAdmin\Controller\QueueController;

class DisqueAdminProvider implements ServiceProviderInterface, ControllerProviderInterface, BootableProviderInterface
{
    public const ID_REGEX = 'D-\S+';

    public function register(Container $pimple)
    {
        if (!isset($pimple['twig'])) {
            throw new RuntimeException(__CLASS__ . ' requires Twig provider to be registered');
        }

        $pimple['disque_admin.mount_prefix'] = '/';
        $pimple['disque_admin.host'] = '127.0.0.1';
        $pimple['disque_admin.port'] = 7711;
        $pimple['disque_admin.password'] = null;
        $pimple['disque_admin.connect_timeout'] = 5;
        $pimple['disque_admin.timeout'] = 5;

        $pimple['disque_admin.credentials'] = function (Application $app): array {
            return [
                new Credentials(
                    $app['disque_admin.host'],
                    $app['disque_admin.port'],
                    $app['disque_admin.password'],
                    $app['disque_admin.connect_timeout'],
                    $app['disque_admin.timeout']
                ),
            ];
        };

        $pimple['disque_admin.client_factory'] = $pimple->factory(function (Application $app): callable {
            $credentials = $app['disque_admin.credentials'];

            return function (?string $prefix = null) use ($credentials): Client {
                $client = new Client($credentials);

                $manager = new Manager();
                $manager->setPriorityStrategy(new NodePrioritizer());

                if ($prefix) {
                    $manager->setPrefix($prefix);
                }

                $client->setConnectionManager($manager);

                $client->connect();
                $client->connect();

                return $client;
            };
        });

        // Controllers

        $pimple['disque_admin.controller.node'] = function (Application $app) {
            return new NodeController($app['disque_admin.client_factory'], $app['twig'], $app['url_generator']);
        };

        $pimple['disque_admin.controller.overview'] = function (Application $app) {
            return new OverviewController($app['disque_admin.client_factory'], $app['twig'], $app['url_generator']);
        };

        $pimple['disque_admin.controller.queue'] = function (Application $app) {
            return new QueueController($app['disque_admin.client_factory'], $app['twig'], $app['url_generator']);
        };

        $pimple['disque_admin.controller.job'] = function (Application $app) {
            return new JobController($app['disque_admin.client_factory'], $app['twig'], $app['url_generator']);
        };

        // Views

        $pimple->extend('twig.loader.filesystem', function (Twig_Loader_Filesystem $loader) {
            $loader->addPath(__DIR__ . '/../resources/views', BaseController::TWIG_NAMESPACE);
            return $loader;
        });
    }

    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];

        // Overview

        $controllers->get('/', function (Application $app) {
            $url = $app['url_generator']->generate('disque_admin_overview_index', ['prefix' => '*']);
            return new RedirectResponse($url, 302);
        });

        $controllers->mount('/{prefix}', function (ControllerCollection $prefix) {
            $prefix->before([$this, 'processPrefix']);

            $prefix->get('/', 'disque_admin.controller.overview:indexAction')
                ->bind('disque_admin_overview_index');

            $prefix->get('/node', 'disque_admin.controller.node:indexAction')
                ->bind('disque_admin_node_index');

            $prefix->get('/node/self', 'disque_admin.controller.node:showAction')
                ->bind('disque_admin_node_show');

            $prefix->get('/queue', 'disque_admin.controller.queue:indexAction')
                ->bind('disque_admin_queue_index');

            $prefix->get('/queue/{name}', 'disque_admin.controller.queue:showAction')
                ->bind('disque_admin_queue_show');

            $prefix->get('/job', 'disque_admin.controller.job:indexAction')
                ->bind('disque_admin_job_index');

            $prefix->get('/job/{id}', 'disque_admin.controller.job:showAction')
                ->bind('disque_admin_job_show')
                ->assert('id', self::ID_REGEX);

            $prefix->post('/job/{id}/enqueue', 'disque_admin.controller.job:enqueueAction')
                ->bind('disque_admin_job_enqueue')
                ->assert('id', self::ID_REGEX);

            $prefix->post('/job/{id}/dequeue', 'disque_admin.controller.job:dequeueAction')
                ->bind('disque_admin_job_dequeue')
                ->assert('id', self::ID_REGEX);

            $prefix->match('/job/{id}/delete', 'disque_admin.controller.job:deleteAction')
                ->method('POST|DELETE')
                ->bind('disque_admin_job_delete')
                ->assert('id', self::ID_REGEX);
        });

        $controllers->after(function (Request $request, Response $response) {
            $response->headers->addCacheControlDirective('private');
        });

        return $controllers;
    }

    public function boot(Application $app)
    {
        if ($app['disque_admin.mount_prefix']) {
            $app->mount($app['disque_admin.mount_prefix'], $this);
        }
    }

    public function processPrefix(Request $request, Application $app)
    {
        $prefix = $request->attributes->get('prefix', '*');

        if ($request->query->has('prefix') && $request->query->get('prefix') !== $prefix) {
            $route = $request->attributes->get('_route');
            $routeParams = array_merge($request->attributes->get('_route_params', []), [
                'prefix' => $request->query->get('prefix') ?: '*',
            ]);

            $url = $app['url_generator']->generate($route, $routeParams);
            return new RedirectResponse($url, 302);
        }

        if ($prefix === '*') {
            $client = ($app['disque_admin.client_factory'])(null);
            $id = array_rand($client->getConnectionManager()->getNodes());
            $prefix = substr($id, 0, 8);
            $request->attributes->set('prefix', $prefix);
            $request->attributes->set('random', true);
        }

        return null;
    }
}

<?php

namespace Varspool\DisqueAdmin;

use Disque\Connection\Credentials;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Predisque\Client;
use RuntimeException;
use Silex\Api\BootableProviderInterface;
use Silex\Api\ControllerProviderInterface;
use Silex\Application;
use Silex\ControllerCollection;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Twig_Loader_Filesystem;
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
        $pimple['disque_admin.connection'] = ['tcp://127.0.0.1:7711'];
        $pimple['disque_admin.options'] = [];

        $pimple['disque_admin.client'] = function (Application $app) {
            return $app['disque_admin.client_factory'];
        };
        
        $pimple['disque_admin.client_factory'] = $pimple->factory(function (Application $app) {
            return new Client($app['disque_admin.connection'], $app['disque_admin.options']);
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
            $url = $app['url_generator']->generate('disque_admin_overview_index');
            return new RedirectResponse($url, 302);
        });

        $controllers->mount('/', function (ControllerCollection $root) {
            // $prefix->before([$this, 'processPrefix']);

            $root->get('/', 'disque_admin.controller.overview:indexAction')
                ->bind('disque_admin_overview_index');

            // Nodes

            $root->get('/node', 'disque_admin.controller.node:indexAction')
                ->bind('disque_admin_node_index');

            $root->get('/node/self', 'disque_admin.controller.node:showAction')
                ->bind('disque_admin_node_show');

            // Queue

            $root->get('/queue', 'disque_admin.controller.queue:indexAction')
                ->bind('disque_admin_queue_index');

            $root->get('/queue/{name}', 'disque_admin.controller.queue:showAction')
                ->bind('disque_admin_queue_show');

            $root->post('/queue/{name}/pause/{type}', 'disque_admin.controller.queue:pauseAction')
                ->bind('disque_admin_queue_pause')
                ->assert('type', '(in|out|bcast)');

            $root->post('/queue/{name}/unpause/{type}', 'disque_admin.controller.queue:unpauseAction')
                ->bind('disque_admin_queue_unpause')
                ->assert('type', '(in|out)');

            // Job

            $root->get('/job', 'disque_admin.controller.job:indexAction')
                ->bind('disque_admin_job_index');

            $root->get('/job/{id}', 'disque_admin.controller.job:showAction')
                ->bind('disque_admin_job_show')
                ->assert('id', self::ID_REGEX);

            $root->post('/job/{id}/enqueue', 'disque_admin.controller.job:enqueueAction')
                ->bind('disque_admin_job_enqueue')
                ->assert('id', self::ID_REGEX);

            $root->post('/job/{id}/dequeue', 'disque_admin.controller.job:dequeueAction')
                ->bind('disque_admin_job_dequeue')
                ->assert('id', self::ID_REGEX);

            $root->match('/job/{id}/delete', 'disque_admin.controller.job:deleteAction')
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

    // public function processPrefix(Request $request, Application $app)
    // {
    //     $prefix = $request->attributes->get('prefix', '*');
    //
    //     if ($request->query->has('prefix') && $request->query->get('prefix') !== $prefix) {
    //         $route = $request->attributes->get('_route');
    //         $routeParams = array_merge($request->attributes->get('_route_params', []), [
    //             'prefix' => $request->query->get('prefix') ?: '*',
    //         ]);
    //
    //         $url = $app['url_generator']->generate($route, $routeParams);
    //         return new RedirectResponse($url, 302);
    //     }
    //
    //     return null;
    // }
}

<?php

namespace Varspool\DisqueAdmin;

use Disque\Client;
use Disque\Connection\Credentials;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use RuntimeException;
use Silex\Api\BootableProviderInterface;
use Silex\Api\ControllerProviderInterface;
use Silex\Application;
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
    private const PREFIX_REGEX = '^(\*|[0-9a-f]{8})$';

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

        $pimple['disque_admin.client'] = function (Application $app) {
            $client = new Client($app['disque_admin.credentials']);
            $client->setConnectionManager($app['disque_admin.connection_manager']);
//            $client->connect();

            return $client;
        };

        $pimple['disque_admin.connection_manager'] = function (Application $app) {
            $manager = new Manager();
            $manager->setPriorityStrategy(new NodePrioritizer());
            return $manager;
        };

        // Controllers

        $pimple['disque_admin.controller.node'] = function (Application $app) {
            return new NodeController($app['disque_admin.client'], $app['twig'], $app['url_generator']);
        };

        $pimple['disque_admin.controller.overview'] = function (Application $app) {
            return new OverviewController($app['disque_admin.client'], $app['twig'], $app['url_generator']);
        };

        $pimple['disque_admin.controller.queue'] = function (Application $app) {
            return new QueueController($app['disque_admin.client'], $app['twig'], $app['url_generator']);
        };

        $pimple['disque_admin.controller.job'] = function (Application $app) {
            return new JobController($app['disque_admin.client'], $app['twig'], $app['url_generator']);
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

        $controllers->get('/{prefix}/', 'disque_admin.controller.overview:indexAction')
            ->bind('disque_admin_overview_index')
            ->assert('prefix', self::PREFIX_REGEX)
            ->value('prefix', '*')
            ->convert('prefix', function ($v) { return $v === '*' ? null : $v; });

        // Node

        $controllers->get('/{prefix}/node', 'disque_admin.controller.node:indexAction')
            ->bind('disque_admin_node_index');

        $controllers->get('/{prefix}/{id}/node', 'disque_admin.controller.node:showAction')
            ->bind('disque_admin_node_show')
            ->value('prefix', '*')
            ->convert('prefix', function ($v) { return $v === '*' ? null : $v; });

        // Queue

        $controllers->get('/{prefix}/queue', 'disque_admin.controller.queue:indexAction')
            ->bind('disque_admin_queue_index');

        $controllers->get('/queue/{name}', 'disque_admin.controller.queue:showAction')
            ->bind('disque_admin_queue_show')
            ->assert('name', '\S+');

        // Job

        $controllers->get('/{prefix}/job', 'disque_admin.controller.job:indexAction')
            ->bind('disque_admin_job_index');

        $controllers->get('/{prefix}/job/{id}', 'disque_admin.controller.job:showAction')
            ->bind('disque_admin_job_show')
            ->assert('prefix', self::PREFIX_REGEX)
            ->assert('id', self::ID_REGEX);

        $controllers->post('/job/{id}/enqueue', 'disque_admin.controller.job:enqueueAction')
            ->bind('disque_admin_job_enqueue')
            ->assert('id', self::ID_REGEX);

        $controllers->post('/job/{id}/dequeue', 'disque_admin.controller.job:dequeueAction')
            ->bind('disque_admin_job_dequeue')
            ->assert('id', self::ID_REGEX);

        $controllers->match('/job/{id}/delete', 'disque_admin.controller.job:deleteAction')
            ->method('POST|DELETE')
            ->bind('disque_admin_job_delete')
            ->assert('id', self::ID_REGEX);

        // Middleware

        $controllers->before(function (Request $request, Application $app) {
            $prefix = $request->query->get('prefix', null);

            if (!$prefix) {
                return null;
            }

            $client = $app['disque_admin.client'];

            $manager = $client->getConnectionManager();
            if ($manager instanceof Manager) {
                $manager->setPrefix($prefix);
            }

            // Connect once to get node information, second connect() will call our NodePrioritizer
            $client->connect();
        });

        $controllers->before(function (Request $request, Application $app) {
            $client = $app['disque_admin.client'];
            $client->connect();
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
}

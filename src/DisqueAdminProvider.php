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
use Varspool\DisqueAdmin\Controller\BaseController;
use Varspool\DisqueAdmin\Controller\JobController;
use Varspool\DisqueAdmin\Controller\OverviewController;
use Varspool\DisqueAdmin\Controller\QueueController;

class DisqueAdminProvider implements ServiceProviderInterface, ControllerProviderInterface, BootableProviderInterface
{
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
            $client->connect();
            return $client;
        };

        // Controllers

        $pimple['disque_admin.controller.overview'] = function (Application $app) {
            return new OverviewController($app['disque_admin.client'], $app['twig']);
        };

        $pimple['disque_admin.controller.queue'] = function (Application $app) {
            return new QueueController($app['disque_admin.client'], $app['twig']);
        };

        $pimple['disque_admin.controller.job'] = function (Application $app) {
            return new JobController($app['disque_admin.client'], $app['twig']);
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

        $controllers->get('/', 'disque_admin.controller.overview:indexAction')
            ->bind('disque_admin_overview_index');

        // Queue

        $controllers->get('/queue', 'disque_admin.controller.queue:indexAction')
            ->bind('disque_admin_queue_index');

        $controllers->get('/queue/{name}', 'disque_admin.controller.queue:showAction')
            ->bind('disque_admin_queue_show')
            ->assert('name', '\S+');

        // Job

        $controllers->get('/job', 'disque_admin.controller.job:indexAction')
            ->bind('disque_admin_job_index');

        $controllers->get('/job/{id}', 'disque_admin.controller.job:showAction')
            ->bind('disque_admin_job_show')
            ->assert('id', 'D-\S+');

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

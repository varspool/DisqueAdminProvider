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
use Twig_Loader_Filesystem;
use Varspool\DisqueAdmin\Controller\BaseController;
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
            return new Client($app['disque_admin.credentials']);
        };

        // Controllers

        $pimple['disque_admin.controller.overview'] = function (Application $app) {
            return new OverviewController($app['disque_admin.client'], $app['twig']);
        };

        $pimple['disque_admin.controller.queue'] = function (Application $app) {
            return new QueueController($app['disque_admin.client'], $app['twig']);
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

        $controllers->get('/', 'disque_admin.controller.overview:indexAction')
            ->bind('disque_admin_overview_index');

        $controllers->get('/queues', 'disque_admin.controller.queue:indexAction')
            ->bind('disque_admin_queue_index');

        return $controllers;
    }

    public function boot(Application $app)
    {
        if ($app['disque_admin.mount_prefix']) {
            $app->mount($app['disque_admin.mount_prefix'], $this);
        }
    }
}

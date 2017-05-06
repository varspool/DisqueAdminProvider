<?php

namespace Varspool\DisqueAdmin\Test;

use Silex\Application;
use Silex\Provider\HttpFragmentServiceProvider;
use Silex\Provider\ServiceControllerServiceProvider;
use Silex\Provider\TwigServiceProvider;
use Silex\WebTestCase as BaseWebTestCase;
use Symfony\Component\BrowserKit\Client;
use Varspool\DisqueAdmin\DisqueAdminProvider;

abstract class WebTestCase extends BaseWebTestCase
{
    public function createApplication()
    {
        $app = new Application([
            'debug' => true,
        ]);

        $app->register(new TwigServiceProvider());
        $app->register(new HttpFragmentServiceProvider());
        $app->register(new ServiceControllerServiceProvider());
        $app->register(new DisqueAdminProvider(), [
            'disque_admin.mount_prefix' => '/_disque/',
        ]);

        return $app;
    }

    protected function assertResponseOk(Client $client)
    {
        if (!$client->getResponse()->isOk()) {
            $this->fail('Error response received: ' . $client->getResponse()->getContent());
        }

        $this->addToAssertionCount(1);
    }
}

<?php

namespace Varspool\DisqueAdmin\Overview;

use Varspool\DisqueAdmin\Test\WebTestCase;

class IndexTest extends WebTestCase
{
    public function testIndex()
    {
        $client = $this->createClient();
        $crawler = $client->request('GET', '/_disque/');
        $this->assertResponseOk($client);

        $this->assertContains('Overview', $crawler->filter('h2')->text());
    }
}

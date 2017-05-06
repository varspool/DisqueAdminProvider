<?php

namespace Varspool\DisqueAdmin\Controller;

use Disque\Connection\Node\Node;
use Predisque\Command\ServerHello;
use Predisque\Connection\Aggregate\ClusterInterface;
use Symfony\Component\HttpFoundation\Request;

class OverviewController extends BaseController
{
    public function indexAction(Request $request)
    {
        return $this->render('overview/index.html.twig', [
        ]);
    }

    public function nodesComponent(Request $request)
    {
        $connection = $this->getDisqueConnection($request);

        $hello = $connection->executeCommandOnCluster(new ServerHello());

        return $this->render('overview/_nodes.html.twig', [
            'hello' => $hello,
        ]);
    }

    public function navComponent(bool $random = false, string $route, Request $request)
    {
        return $this->render('overview/_nav.html.twig', [
            'route' => $route,
            'random' => $random,
        ]);
    }
}

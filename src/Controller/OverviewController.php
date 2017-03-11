<?php

namespace Varspool\DisqueAdmin\Controller;

use Disque\Connection\Node\Node;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class OverviewController extends BaseController
{
    public function indexAction(Request $request)
    {
        return $this->render('overview/index.html.twig');
    }

    public function nodesComponent(Request $request)
    {
        $node = $this->disque->getConnectionManager()->getCurrentNode();
        /**
         * @var Node $node
         */
        $hello = $node->getHello();

        return $this->render('overview/_nodes.html.twig', [
            'hello' => $hello,
        ]);
    }

    public function navComponent(string $route, Request $request)
    {
        $node = $this->disque->getConnectionManager()->getCurrentNode();

        return $this->render('_nav.html.twig', [
            'route' => $route,
            'node' => $node,
        ]);
    }
}

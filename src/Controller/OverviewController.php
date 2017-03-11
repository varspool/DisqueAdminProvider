<?php

namespace Varspool\DisqueAdmin\Controller;

use Disque\Connection\Node\Node;
use Symfony\Component\HttpFoundation\Request;

class OverviewController extends BaseController
{
    public function indexAction(Request $request)
    {
        return $this->render('overview/index.html.twig', [
            'prefix' => $request->query->get('prefix')
        ]);
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
            'prefix' => $request->query->get('prefix')
        ]);
    }

    public function navComponent(string $route, ?string $prefix, Request $request)
    {
        return $this->render('overview/_nav.html.twig', [
            'route' => $route,
            'prefix' => $prefix
        ]);
    }

    public function prefixComponent(?string $prefix, Request $request)
    {
        $node = $this->disque->getConnectionManager()->getCurrentNode();

        return $this->render('overview/_prefix.html.twig', [
            'node' => $node,
            'prefix' => $prefix
        ]);
    }
}

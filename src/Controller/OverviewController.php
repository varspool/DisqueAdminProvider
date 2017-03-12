<?php

namespace Varspool\DisqueAdmin\Controller;

use Disque\Connection\Node\Node;
use Symfony\Component\HttpFoundation\Request;

class OverviewController extends BaseController
{
    public function indexAction(Request $request)
    {
        return $this->render('overview/index.html.twig', [
            'prefix' => $request->get('prefix'),
        ]);
    }

    public function nodesComponent(Request $request)
    {
        $node = $this->getDisque($request)->getConnectionManager()->getCurrentNode();

        /**
         * @var Node $node
         */
        $hello = $node->getHello();

        return $this->render('overview/_nodes.html.twig', [
            'hello' => $hello,
            'prefix' => $request->get('prefix'),
        ]);
    }

    public function navComponent(string $route, Request $request)
    {
        $prefix = $this->getDisque($request)->getConnectionManager()->getPrefix();

        return $this->render('overview/_nav.html.twig', [
            'route' => $route,
            'prefix' => $prefix,
        ]);
    }

    public function prefixComponent(?string $prefix, Request $request)
    {
        $currentPrefix = $prefix;
        $manager = $this->getDisque($request)->getConnectionManager();

        if ($prefix === null || $prefix === '*') {
            $currentId = $manager->getCurrentNode()->getId();
            $currentPrefix = substr($currentId, 0, 8);
        }

        $nodes = $manager->getNodes();

        return $this->render('overview/_prefix.html.twig', [
            'prefix' => $prefix,
            'currentPrefix' => $currentPrefix,
            'nodes' => $nodes,
        ]);
    }
}

<?php

namespace Varspool\DisqueAdmin\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class OverviewController extends BaseController
{
    public function indexAction(Request $request)
    {
        return $this->render('overview/index.html.twig');
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

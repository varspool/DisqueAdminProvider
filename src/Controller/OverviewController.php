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
}

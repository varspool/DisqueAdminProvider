<?php

namespace Varspool\DisqueAdmin\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class QueueController extends BaseController
{
    public function indexAction(Request $request)
    {
        return new Response('OK!');
    }

}

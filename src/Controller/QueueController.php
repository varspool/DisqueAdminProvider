<?php

namespace Varspool\DisqueAdmin\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class QueueController extends BaseController
{
    public function indexAction(Request $request)
    {
        $response = $this->disque->qscan(0, [
            'busyloop' => true,
        ]);

        return $this->render('queue.html.twig');
    }
}

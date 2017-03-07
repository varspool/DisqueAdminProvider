<?php

namespace Varspool\DisqueAdmin\Controller;

use Symfony\Component\HttpFoundation\Request;

class JobController extends BaseController
{
    public function indexAction(Request $request)
    {
        $response = $this->disque->jscan(0, [
            'count' => 10,
            'reply' => 'all'
        ]);

        return $this->render('job/index.html.twig', [
            'jobs' => $response['jobs'],
            'columns' => [
                'id',
                'queue',
                'state',
                'repl',
                'ttl',
                'ctime',
                'delay',
                'retry',
                'nacks',
                'additional-deliveries',
                'nodes-delivered',
                'nodes-confirmed',
                'next-requeue-within',
                'next-awake-within'
            ],
        ]);
    }

    public function showAction(string $id, Request $request)
    {
        $show = $this->disque->show($id);
        $body = $show['body'];
        unset($show['body']);

        return $this->render('job/show.html.twig', [
            'id' => $id,
            'show' => $show,
            'body' => json_encode($body, JSON_PRETTY_PRINT)
        ]);
    }

}

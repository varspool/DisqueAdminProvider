<?php

namespace Varspool\DisqueAdmin\Controller;

use Symfony\Component\HttpFoundation\Request;

class QueueController extends BaseController
{
    public function indexAction(Request $request)
    {
        $response = $this->disque->qscan(0, [
            'busyloop' => true,
            'minlen' => 1,
        ]);

        $queues = [];

        foreach ($response['queues'] as $queue) {
            $queues[$queue] = $this->disque->qstat($queue);
        }

        return $this->render('queue/index.html.twig', [
            'queues' => $queues,
            'columns' => [
                'name',
                'len',
                'age',
                'idle',
                'blocked',
                'import-from',
                'import-rate',
                'jobs-in',
                'jobs-out',
                'pause'
            ],
        ]);
    }

    public function showAction(string $name, Request $request)
    {
        $stat = $this->disque->qstat($name);
        $jobs = $this->disque->qpeek($name, 10);

        return $this->render('queue/show.html.twig', [
            'name' => $name,
            'stat' => $stat,
            'jobs' => $jobs,
        ]);
    }

    public function countsComponent(Request $request)
    {
        $response = $this->disque->qscan(0, [
            'busyloop' => true,
            'minlen' => 1,
        ]);

        $queues = [];

        foreach ($response['queues'] as $queue) {
            $queues[$queue] = $this->disque->qlen($queue);
        }

        return $this->render('queue/_counts.html.twig', [
            'queues' => $queues,
        ]);
    }
}

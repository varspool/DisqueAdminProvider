<?php

namespace Varspool\DisqueAdmin\Controller;

use Symfony\Component\HttpFoundation\Request;

class QueueController extends BaseController
{
    protected $columns = [
        'name',
        'len',
        'age',
        'idle',
        'blocked',
        'import-from',
        'import-rate',
        'jobs-in',
        'jobs-out',
        'pause',
    ];

    protected $format = [
        'idle' => 'formatIntervalSeconds',
        'age' => 'formatIntervalSeconds',
        'len' => 'formatJobCount',
        'jobs-in' => 'formatJobCount',
        'jobs-out' => 'formatJobCount',
    ];

    public function indexAction(Request $request)
    {
        $response = $this->disque->qscan(0, [
            'busyloop' => true,
            'minlen' => 1,
        ]);

        $queues = [];

        foreach ($response['queues'] as $queue) {
            $queues[$queue] = $this->formatObject($this->disque->qstat($queue));
        }

        return $this->render('queue/index.html.twig', [
            'queues' => $queues,
            'columns' => $this->columns,
            'prefix' => $request->attributes->get('prefix')
        ]);
    }

    public function showAction(string $name, Request $request)
    {
        $stat = $this->disque->qstat($name);
        unset($stat['pause']);

        $jobs = $this->disque->qpeek($name, 10);

        return $this->render('queue/show.html.twig', [
            'name' => $name,
            'stat' => $this->formatObject($stat),
            'jobs' => $jobs,
            'prefix' => $request->attributes->get('prefix')
        ]);
    }

    public function pauseComponent(?string $prefix, Request $request)
    {
        $states = [];

        return $this->render('queue/_pause.html.twig', [
            'prefix' => $prefix,
        ]);
    }

    public function countsComponent(?string $prefix, Request $request)
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
            'prefix' => $prefix
        ]);
    }
}

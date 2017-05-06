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
        $client = $this->getDisque($request);

        $response = $client->qscan(0, [
            'busyloop' => true,
            'minlen' => 1,
        ]);

        $queues = [];

        foreach ($response['queues'] as $queue) {
            $queues[$queue] = $this->formatObject($client->qstat($queue));
        }

        return $this->render('queue/index.html.twig', [
            'queues' => $queues,
            'columns' => $this->columns,
        ]);
    }

    public function showAction(string $name, Request $request)
    {
        $client = $this->getDisque($request);

        $stat = $client->qstat($name);
        unset($stat['pause']);

        $jobs = $client->qpeek($name, 10);

        return $this->render('queue/show.html.twig', [
            'name' => $name,
            'stat' => $this->formatObject($stat),
            'jobs' => $jobs,
        ]);
    }

    public function pauseAction(string $name, string $type, Request $request)
    {
        $client = $this->getDisque($request);

        if ($type === 'bcast') {
            $valueToSet = 'bcast';
        } else {
            $current = $client->pause($name, 'state');

            if ($current === 'all') {
                return $this->redirect($this->url->generate('disque_admin_queue_show', ['name' => $name, 'prefix' => $prefix]), 302);
            } else {
                $valueToSet = $current === 'none' ? $type : 'all';
            }
        }

        $client->pause($name, $valueToSet);

        return $this->redirect($this->url->generate('disque_admin_queue_show', ['name' => $name, 'prefix' => $prefix]), 302);
    }

    public function unpauseAction(string $name, string $type, string $prefix, Request $request)
    {
        $client = $this->getDisque($request);
        $current = $client->pause($name, 'state');

        $valueToSet = $current === $type ? 'none' : ($type === 'in' ? 'out' : 'in');
        $client->pause($name, $valueToSet);

        return $this->redirect($this->url->generate('disque_admin_queue_show', ['name' => $name, 'prefix' => $prefix]), 302);
    }

    public function pauseComponent(Request $request)
    {
        /**
         * @var
         */
        $cluster = $this->getDisque($request)->getConnection();

        $nodes = $manager->getNodes();
        $currentId = $manager->getCurrentNode()->getId();

        $states = [];

        foreach ($nodes as $id => $node) {
            $client = ($this->disqueFactory)($id);
            $stat = $client->qstat($name);
            $states[$id] = $stat['pause'];
        }

        return $this->render('queue/_pause.html.twig', [
            'states' => $states,
            'currentId' => $currentId,
            'name' => $name,
        ]);
    }

    public function countsComponent(Request $request)
    {
        $client = $this->getDisque($request);

        $response = $client->qscan(0, [
            'busyloop' => true,
            'minlen' => 1,
        ]);

        $queues = [];

        foreach ($response['queues'] as $queue) {
            $queues[$queue] = $client->qlen($queue);
        }

        return $this->render('queue/_counts.html.twig', [
            'queues' => $queues,
            'prefix' => $prefix
        ]);
    }
}

<?php

namespace Varspool\DisqueAdmin\Controller;

use Symfony\Component\HttpFoundation\Request;

class JobController extends BaseController
{
    protected $columns = [
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
        'next-awake-within',
    ];

    protected $format = [
        'nacks' => 'formatCount',
        'additional-deliveries' => 'formatCount',
        'ctime' => 'formatCTime',
        'ttl' => 'formatIntervalSeconds',
        'retry' => 'formatIntervalSeconds',
        'delay' => 'formatIntervalSeconds',
        'next-requeue-within' => 'formatIntervalMillis',
        'next-awake-within' => 'formatIntervalMillis',
    ];

    public function indexAction(Request $request)
    {
        $response = $this->getDisque($request)->jscan(0, [
            'count' => 10,
            'reply' => 'all',
        ]);

        $jobs = array_map([$this, 'formatObject'], $response['jobs']);

        return $this->render('job/index.html.twig', [
            'jobs' => $jobs,
            'columns' => $this->columns,
            'prefix' => $request->attributes->get('prefix')
        ]);
    }

    public function showAction(string $id, Request $request)
    {
        $show = $this->getDisque($request)->show($id);

        if ($show) {
            $show = $this->formatObject($show);

            $body = $show['body'];
            unset($show['body']);
        } else {
            $body = null;
        }

        return $this->render('job/show.html.twig', [
            'id' => $id,
            'show' => $show,
            'body' => json_encode(json_decode($body), JSON_PRETTY_PRINT),
            'prefix' => $request->attributes->get('prefix')
        ]);
    }

    /**
     * @todo CSRF protection
     * @param string  $id
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function enqueueAction(string $id, Request $request)
    {
        $this->getDisque($request)->enqueue($id);
        return $this->redirect($this->url->generate('disque_admin_job_show', ['id' => $id]));
    }

    /**
     * @todo CSRF protection
     * @param string  $id
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function dequeueAction(string $id, Request $request)
    {
        $this->getDisque($request)->dequeue($id);
        return $this->redirect($this->url->generate('disque_admin_job_show', ['id' => $id]));
    }

    /**
     * @todo CSRF protection
     * @param string  $id
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function deleteAction(string $id, Request $request)
    {
        $this->getDisque($request)->delJob($id);
        return $this->redirect($this->url->generate('disque_admin_job_index', [
            'prefix' => $request->attributes->get('prefix')
        ]));
    }
}

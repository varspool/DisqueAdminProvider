<?php

namespace Varspool\DisqueAdmin\Controller;

use Disque\Connection\Node\Node;
use Symfony\Component\HttpFoundation\Request;

class NodeController extends BaseController
{
    public function indexAction(Request $request)
    {
        return $this->render('node/index.html.twig', [
            'prefix' => $request->query->get('prefix')
        ]);
    }

    public function showAction(string $id, ?string $prefix, Request $request)
    {
        if ($prefix !== substr($id, 0, 8)) {
            return $this->redirect($this->url->generate('disque_admin_node_show', [
                'id' => $id,
                'prefix' => substr($id, 0, 8)
            ]), 302);
        }

        $info = $this->disque->info();

        return $this->render('node/show.html.twig', [
            'prefix' => $prefix,
            'id' => $id,
            'info' => $info,
        ]);
    }

    public function tableComponent(?string $prefix, Request $request)
    {
        $node = $this->disque->getConnectionManager()->getCurrentNode();

        /**
         * @var Node $node
         */
        $hello = $node->getHello();

        return $this->render('node/_table.html.twig', [
            'hello' => $hello,
            'prefix' => $prefix
        ]);
    }
}

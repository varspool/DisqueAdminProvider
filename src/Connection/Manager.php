<?php

namespace Varspool\DisqueAdmin\Connection;

use Disque\Connection\Manager as BaseManager;

class Manager extends BaseManager
{
    private $prefix;

    public function setPrefix(string $prefix)
    {
        $this->prefix = $prefix;

        $strategy = $this->getPriorityStrategy();

        if ($strategy instanceof NodePrioritizer) {
            $strategy->setPrefix($prefix);
        }
    }

    protected function findAvailableConnection()
    {
        if (empty($this->nodePrefixes)) {
            return parent::findAvailableConnection();
        }

        throw new \LogicException('Not implemented yet');
    }
}

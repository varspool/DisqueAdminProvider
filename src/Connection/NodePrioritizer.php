<?php

namespace Varspool\DisqueAdmin\Connection;

use Disque\Connection\Node\NodePrioritizerInterface;
use Disque\Connection\Node\NullPrioritizer;
use Disque\Connection\Node\RandomPrioritizer;

/**
 * This prioritizer always targets the given prefix
 */
class NodePrioritizer implements NodePrioritizerInterface
{
    /**
     * @var string
     */
    private $prefix;

    /**
     * @var NodePrioritizerInterface
     */
    private $random;

    /**
     * @var NodePrioritizerInterface
     */
    private $null;

    public function __construct()
    {
        $this->random = new RandomPrioritizer();
        $this->null = new NullPrioritizer();
    }

    public function setPrefix(string $prefix)
    {
        $this->prefix = $prefix;
    }

    public function sort(array $nodes, $currentNodeId)
    {
        if (!$this->prefix) {
            return $this->random->sort($nodes, $currentNodeId);
        }

        if ($this->prefix === substr($currentNodeId, 0, 8)) {
            return $this->null->sort($nodes, $currentNodeId);
        }

        $filtered = array_filter($nodes, function ($id) {
            return $this->prefix === substr($id, 0, 8);
        }, ARRAY_FILTER_USE_KEY);

        if (empty($filtered)) {
            return $this->random->sort($nodes, $currentNodeId);
        }

        return $filtered;
    }
}


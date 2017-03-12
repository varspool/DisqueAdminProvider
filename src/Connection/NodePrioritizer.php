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

    /**
     * @var bool
     */
    private $throwNotFound = true;

    public function __construct()
    {
        $this->random = new RandomPrioritizer();
        $this->null = new NullPrioritizer();
    }

    public function setPrefix(string $prefix, bool $throwNotFound = true)
    {
        $this->prefix = $prefix;
        $this->throwNotFound = $throwNotFound;
    }

    public function sort(array $nodes, $currentNodeId)
    {
        if (!$this->prefix || $this->prefix === '*') {
            return $this->random->sort($nodes, $currentNodeId);
        }

        if (strpos($currentNodeId, $this->prefix) === 0) {
            return $this->null->sort($nodes, $currentNodeId);
        }

        $filtered = array_filter($nodes, function ($id) {
            return strpos($id, $this->prefix) === 0;
        }, ARRAY_FILTER_USE_KEY);

        if (empty($filtered)) {
            if ($this->throwNotFound) {
                throw new NodeNotFoundException('Could not find ' . $this->prefix);
            } else {
                return $this->random->sort($nodes, $currentNodeId);
            }
        }

        return $filtered;
    }
}


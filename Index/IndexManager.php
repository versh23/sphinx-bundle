<?php

namespace Versh\SphinxBundle\Index;

class IndexManager
{
    /**
     * @var Index[]
     */
    private $indexes;

    public function __construct(array $indexes)
    {
        $this->indexes = $indexes;
    }

    public function getAllIndexes()
    {
        return $this->indexes;
    }

    public function getIndex(string $name)
    {
        return $this->indexes[$name];
    }
}

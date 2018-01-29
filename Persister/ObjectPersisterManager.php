<?php

namespace Versh\SphinxBundle\Persister;

class ObjectPersisterManager
{
    /**
     * @var ObjectPersister[]
     */
    private $persisters;

    public function __construct(array $persisters)
    {
        $this->persisters = $persisters;
    }

    public function getPersister(string $name)
    {
        return $this->persisters[$name];
    }
}
